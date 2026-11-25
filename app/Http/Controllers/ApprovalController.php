<?php

namespace App\Http\Controllers;

use App\Models\{Asset, Approval, ApprovalFlow, ApprovalFlowStep, AssetDisposal, Procurement, PurchaseRequest, User};
use App\Notifications\SystemAlert;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('approvals.view'), 403);

        $query = Approval::with(['approver', 'approvable'])
            ->whereIn('status', ['pending', 'waiting']);
        if (! $request->user()->hasPermission('approvals.override')) {
            $query->where('approver_id', $request->user()->id);
        }

        $approvals = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('sequence')->latest()->paginate(25);

        return view('admin.approvals', compact('approvals'));
    }

    public function submit(Request $request, string $resource, int $id)
    {
        $map = ['procurements' => Procurement::class, 'purchase-requests' => PurchaseRequest::class, 'disposals' => AssetDisposal::class];
        abort_unless(isset($map[$resource]), 404);
        $permissionModule = $resource === 'purchase-requests' ? 'procurements' : $resource;
        $definition = config("inventory.resources.$permissionModule");
        abort_unless($definition && $request->user()->hasPermission($permissionModule.'.manage'), 403);

        $record = $map[$resource]::findOrFail($id);
        $flow = ApprovalFlow::query()->with(['steps.approverUser', 'steps.approverRole.users'])
            ->where('subject_type', $record::class)
            ->where('is_active', true)
            ->latest('id')->first();

        $approverIds = [];
        if (! $flow) {
            $approverIds = collect($request->input('approver_ids', []))
                ->filter(fn ($approverId) => filled($approverId))->values()->all();
            $request->merge(['approver_ids' => $approverIds]);
            $data = $request->validate([
                'approver_ids' => 'required|array|min:1|max:10',
                'approver_ids.*' => ['required', 'integer', 'distinct', \Illuminate\Validation\Rule::exists('users', 'id')->where('is_active', true)],
                'remarks' => 'nullable|string|max:2000',
            ]);
            foreach ($data['approver_ids'] as $approverId) {
                $approver = User::findOrFail($approverId);
                if (! $approver->hasPermission('approvals.approve')) {
                    throw ValidationException::withMessages(['approver_ids' => 'Every approver must have approval authority.']);
                }
            }
        } else {
            $data = $request->validate(['remarks' => 'nullable|string|max:2000']);
            if ($flow->steps->isEmpty()) {
                throw ValidationException::withMessages(['approval_flow' => 'The active approval flow has no configured steps. Deactivate it or add at least one step.']);
            }
            foreach ($flow->steps as $step) {
                $users = $this->usersForStep($step);
                if ($users->isEmpty()) {
                    throw ValidationException::withMessages(['approval_flow' => 'Approval step '.$step->sequence.' has no active approver with approval authority.']);
                }
            }
        }

        DB::transaction(function () use ($map, $resource, $id, $data, $request, $flow) {
            $lockedRecord = $map[$resource]::whereKey($id)->lockForUpdate()->firstOrFail();
            $allowedStatuses = in_array($resource, ['procurements', 'purchase-requests'], true)
                ? ['draft', 'submitted', 'rejected', 'revision_requested']
                : ['pending_approval', 'rejected', 'revision_requested'];
            if (! in_array($lockedRecord->status, $allowedStatuses, true)) {
                throw ValidationException::withMessages(['approval' => 'This record is not eligible for approval in its current status.']);
            }

            $active = $lockedRecord->approvals()->whereIn('status', ['pending', 'waiting'])->exists();
            if ($active) {
                throw ValidationException::withMessages(['approval' => 'An approval workflow is already active for this record.']);
            }
            // Keep prior rounds as a permanent review history; each re-submission
            // receives a new round number and decisions are scoped to that round.
            $round = ((int) $lockedRecord->approvals()->max('round')) + 1;

            if ($flow) {
                foreach ($flow->steps as $stepIndex => $step) {
                    $users = $this->usersForStep($step);
                    foreach ($users as $user) {
                        Approval::create([
                            'approvable_type' => $lockedRecord::class,
                            'approvable_id' => $lockedRecord->id,
                            'sequence' => $step->sequence,
                            'round' => $round,
                            'step_id' => $step->id,
                            'approver_id' => $user->id,
                            'status' => $stepIndex === 0 ? 'pending' : 'waiting',
                            'remarks' => $data['remarks'] ?? null,
                        ]);
                        if ($stepIndex === 0) {
                            $user->notify(new SystemAlert('Approval requested', 'A record requires your review.', route('approvals.index'), 'warning'));
                        }
                    }
                }
            } else {
                foreach ($data['approver_ids'] as $index => $userId) {
                    Approval::create([
                        'approvable_type' => $lockedRecord::class,
                        'approvable_id' => $lockedRecord->id,
                        'sequence' => $index + 1,
                        'round' => $round,
                        'approver_id' => $userId,
                        'status' => $index === 0 ? 'pending' : 'waiting',
                        'remarks' => $data['remarks'] ?? null,
                    ]);
                    if ($index === 0) {
                        User::find($userId)?->notify(new SystemAlert('Approval requested', 'A record requires your review.', route('approvals.index'), 'warning'));
                    }
                }
            }

            $lockedRecord->update(['status' => 'pending_approval']);
        });

        $record = $map[$resource]::findOrFail($id);
        Audit::record('approval_submitted', $resource, $record::class, $record->id, [], ['flow_id' => $flow?->id]);

        return back()->with('success', $flow
            ? 'Record submitted using the configured approval flow.'
            : 'Sequential approval workflow submitted.');
    }

    public function decide(Request $request, Approval $approval)
    {
        abort_unless($request->user()->hasPermission('approvals.approve'), 403);
        $data = $request->validate([
            'decision' => 'required|in:approved,rejected,revision_requested',
            'remarks' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($approval, $data, $request) {
            // Lock the parent request before the approval row. This stable lock
            // order serializes parallel decisions and avoids sibling-row deadlocks.
            $type = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($approval->approvable_type) ?? $approval->approvable_type;
            abort_unless(is_string($type) && class_exists($type), 422, 'The approval target type is invalid.');
            $record = $type::whereKey($approval->approvable_id)->lockForUpdate()->firstOrFail();
            $locked = Approval::whereKey($approval->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->approvable_type === $approval->approvable_type && (int) $locked->approvable_id === (int) $record->getKey(), 409, 'The approval target changed. Reload the page.');
            abort_unless($locked->status === 'pending', 422, 'This approval is not awaiting action.');
            abort_unless((int) $locked->approver_id === (int) $request->user()->id || $request->user()->hasPermission('approvals.override'), 403);
            abort_unless($record->status === 'pending_approval', 422, 'The parent request is no longer awaiting approval.');

            $step = $locked->step_id ? ApprovalFlowStep::with('flow')->find($locked->step_id) : null;
            if ($data['decision'] === 'revision_requested' && $step && ! $step->allow_return_for_revision) {
                throw ValidationException::withMessages(['decision' => 'This approval step does not permit returning the request for revision.']);
            }

            $locked->update([
                'status' => $data['decision'],
                'decision' => $data['decision'],
                'remarks' => $data['remarks'] ?? $locked->remarks,
                'acted_at' => now(),
                'decided_at' => now(),
            ]);

            if ($data['decision'] !== 'approved') {
                $record->approvals()->where('round', $locked->round)->whereIn('status', ['pending', 'waiting'])->update(['status' => 'cancelled']);
                $record->update(['status' => $data['decision'] === 'rejected' ? 'rejected' : 'revision_requested']);
                if ($record instanceof AssetDisposal) {
                    $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->first();
                    if ($asset) {
                        app(\App\Services\AssetHistoryWriter::class)->record(
                            $asset,
                            'disposal_'.$data['decision'],
                            ['disposal_status' => 'pending_approval'],
                            ['disposal_id' => $record->id, 'disposal_no' => $record->disposal_no, 'disposal_status' => $record->status, 'remarks' => $data['remarks'] ?? null]
                        );
                    }
                }
                $this->notifyRequester($record, $data['decision']);
                return;
            }

            if ($step && $step->mode === 'sequential') {
                // A sequential role step is satisfied by the first authorized member to approve.
                $record->approvals()->where('round', $locked->round)->where('step_id', $step->id)->where('status', 'pending')->update(['status' => 'cancelled']);
            }

            if ($step && $step->mode === 'parallel') {
                $pendingInStep = $record->approvals()->where('round', $locked->round)->where('step_id', $step->id)->where('status', 'pending')->exists();
                if ($pendingInStep) {
                    return;
                }
            }

            $next = $record->approvals()->where('round', $locked->round)->where('status', 'waiting')->orderBy('sequence')->first();
            if ($next) {
                $nextStepId = $next->step_id;
                $nextSequence = $next->sequence;
                $nextApprovals = $record->approvals()->where('round', $locked->round)->where('status', 'waiting')->where('sequence', $nextSequence);
                if ($nextStepId) {
                    $nextApprovals->where('step_id', $nextStepId);
                } else {
                    $nextApprovals->limit(1);
                }
                $activated = $nextApprovals->get();
                foreach ($activated as $pending) {
                    $pending->update(['status' => 'pending']);
                    $pending->approver?->notify(new SystemAlert('Approval required', 'The previous approval stage is complete. Your action is now required.', route('approvals.index'), 'warning'));
                }
            } else {
                $record->update(['status' => 'approved']);
                if ($record instanceof \App\Models\AssetDisposal) {
                    $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->first();
                    if ($asset) {
                        app(\App\Services\AssetHistoryWriter::class)->record(
                            $asset,
                            'disposal_approved',
                            ['disposal_status' => 'pending_approval'],
                            ['disposal_id' => $record->id, 'disposal_no' => $record->disposal_no, 'disposal_status' => 'approved']
                        );
                    }
                }
                $this->notifyRequester($record, 'approved');
            }
        });

        Audit::record('approval_'.$data['decision'], 'approvals', Approval::class, $approval->id);

        return back()->with('success', match ($data['decision']) {
            'approved' => 'Approval decision recorded.',
            'rejected' => 'Request rejected and remaining approval tasks cancelled.',
            default => 'Request returned for revision and remaining approval tasks cancelled.',
        });
    }

    private function usersForStep(ApprovalFlowStep $step)
    {
        if ($step->approver_user_id) {
            $users = User::whereKey($step->approver_user_id)->where('is_active', true)->get();
        } elseif ($step->approver_role_id) {
            $users = $step->approverRole?->users()->where('is_active', true)->get() ?? collect();
        } else {
            return collect();
        }

        return $users->filter(fn (User $user) => $user->hasPermission('approvals.approve'))->values();
    }

    private function notifyRequester($record, string $decision): void
    {
        $requester = $record instanceof AssetDisposal ? $record->requester : ($record instanceof Procurement ? $record->requesterUser : ($record instanceof PurchaseRequest ? $record->requester : null));
        if (! $requester) {
            return;
        }

        [$title, $message, $level] = match ($decision) {
            'approved' => ['Request approved', 'Your request has completed its approval workflow.', 'success'],
            'rejected' => ['Request rejected', 'Your request was rejected. Review the approval remarks.', 'danger'],
            default => ['Revision requested', 'Your request was returned for revision. Review the approval remarks and resubmit.', 'warning'],
        };
        $url = $record instanceof PurchaseRequest
            ? route('procurement.requests.show', $record)
            : route('inventory.show', [$record instanceof AssetDisposal ? 'disposals' : 'procurements', $record->id]);
        $requester->notify(new SystemAlert($title, $message, $url, $level));
    }
}
