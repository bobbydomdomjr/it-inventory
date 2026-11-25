<?php

namespace App\Http\Controllers;

use App\Models\{ApprovalFlow, ApprovalFlowStep, AssetDisposal, Procurement, PurchaseRequest, Role, User};
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalFlowController extends Controller
{
    private const SUBJECTS = [
        Procurement::class => 'Legacy procurement records',
        PurchaseRequest::class => 'Purchase requests',
        AssetDisposal::class => 'Asset disposal requests',
    ];

    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        $flows = ApprovalFlow::with(['steps.approverUser', 'steps.approverRole'])->orderBy('subject_type')->orderByDesc('id')->paginate(20);
        $users = User::where('is_active', true)->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        return view('admin.approval-flows', ['flows' => $flows, 'users' => $users, 'roles' => $roles, 'subjects' => self::SUBJECTS]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'subject_type' => ['required', 'string', 'in:'.implode(',', array_keys(self::SUBJECTS))],
            'mode' => 'required|in:sequential,parallel',
        ]);

        $flow = ApprovalFlow::create($data + ['is_active' => false]);
        Audit::record('approval_flow_created', 'approval_flows', ApprovalFlow::class, $flow->id);
        return back()->with('success', 'Approval flow created. Add at least one approval step before activating it.');
    }

    public function addStep(Request $request, ApprovalFlow $flow)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        abort_if($flow->is_active, 422, 'Deactivate this approval flow before changing its steps.');
        $data = $request->validate([
            'sequence' => ['required', 'integer', 'min:1', 'max:50', 'unique:approval_flow_steps,sequence,NULL,id,approval_flow_id,'.$flow->id],
            'approver_type' => 'required|in:user,role',
            'approver_user_id' => 'required_if:approver_type,user|nullable|exists:users,id',
            'approver_role_id' => 'required_if:approver_type,role|nullable|exists:roles,id',
            'mode' => 'required|in:sequential,parallel',
            'allow_return_for_revision' => 'nullable|boolean',
            'escalate_after_hours' => 'nullable|integer|min:1|max:8760',
        ]);
        if ($data['approver_type'] === 'user') {
            $user = User::whereKey($data['approver_user_id'])->where('is_active', true)->firstOrFail();
            if (! $user->hasPermission('approvals.approve')) {
                throw ValidationException::withMessages(['approver_user_id' => 'The selected user does not have approval authority.']);
            }
            $approverUserId = $user->id;
            $approverRoleId = null;
        } else {
            $role = Role::with('users')->findOrFail($data['approver_role_id']);
            if (! $role->users->contains(fn (User $user) => $user->is_active && $user->hasPermission('approvals.approve'))) {
                throw ValidationException::withMessages(['approver_role_id' => 'The selected role has no active member with approval authority.']);
            }
            $approverUserId = null;
            $approverRoleId = $role->id;
        }

        $step = $flow->steps()->create([
            'sequence' => $data['sequence'],
            'approver_user_id' => $approverUserId,
            'approver_role_id' => $approverRoleId,
            'mode' => $data['mode'],
            'allow_return_for_revision' => (bool) ($data['allow_return_for_revision'] ?? true),
            'escalate_after_hours' => $data['escalate_after_hours'] ?? null,
        ]);
        Audit::record('approval_flow_step_created', 'approval_flows', ApprovalFlowStep::class, $step->id);
        return back()->with('success', 'Approval step added.');
    }

    public function destroyStep(Request $request, ApprovalFlow $flow, ApprovalFlowStep $step)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        abort_if($flow->is_active, 422, 'Deactivate this approval flow before changing its steps.');
        abort_unless((int) $step->approval_flow_id === (int) $flow->id, 404);
        abort_if($step->approvals()->exists(), 422, 'This step has approval history and cannot be removed.');
        $step->forceDelete();
        Audit::record('approval_flow_step_archived', 'approval_flows', ApprovalFlowStep::class, $step->id);
        return back()->with('success', 'Approval step removed.');
    }

    public function toggle(Request $request, ApprovalFlow $flow)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        if (! $flow->is_active && ! $flow->steps()->exists()) {
            throw ValidationException::withMessages(['approval_flow' => 'Add at least one approval step before activation.']);
        }

        DB::transaction(function () use ($flow) {
            $locked = ApprovalFlow::whereKey($flow->id)->lockForUpdate()->firstOrFail();
            $activate = ! $locked->is_active;
            if ($activate) {
                ApprovalFlow::where('subject_type', $locked->subject_type)->where('id', '!=', $locked->id)->update(['is_active' => false]);
            }
            $locked->update(['is_active' => $activate]);
        });
        Audit::record('approval_flow_toggled', 'approval_flows', ApprovalFlow::class, $flow->id, [], ['is_active' => ! $flow->is_active]);
        return back()->with('success', $flow->fresh()->is_active ? 'Approval flow activated.' : 'Approval flow deactivated.');
    }

    public function destroy(Request $request, ApprovalFlow $flow)
    {
        abort_unless($request->user()->hasPermission('approvals.manage'), 403);
        abort_if($flow->is_active, 422, 'Deactivate this approval flow before archiving it.');
        abort_if(ApprovalFlowStep::whereIn('id', $flow->steps()->pluck('id'))->whereHas('approvals')->exists(), 422, 'This flow has approval history and cannot be deleted. Deactivate it instead.');
        $flow->delete();
        Audit::record('approval_flow_archived', 'approval_flows', ApprovalFlow::class, $flow->id);
        return back()->with('success', 'Approval flow archived.');
    }
}
