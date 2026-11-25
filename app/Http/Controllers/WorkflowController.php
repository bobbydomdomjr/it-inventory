<?php
namespace App\Http\Controllers;

use App\Models\{Asset,Assignment,AssetTransfer,InventoryItem,StockTransaction,PhysicalAudit,InventoryAuditItem};
use App\Notifications\SystemAlert;
use App\Support\Audit;
use App\Services\AssetHistoryWriter;
use App\Services\StockLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WorkflowController extends Controller
{
    public function receipt(Request $request, Assignment $assignment)
    {
        abort_unless($request->user()->hasPermission('assignments.view'), 403);

        $assignment->load(['asset', 'employee', 'employee.department', 'asset.department']);
        $organizationName = data_get(
            \App\Models\SystemSetting::query()->where('key', 'organization_name')->first()?->value,
            'value',
            config('app.name')
        );

        $documentTitle = $request->query('document_type') === 'issuance' ? 'Equipment Issuance Form' : 'Property Acknowledgment Receipt';
        return view('inventory.assignment-receipt', compact('assignment', 'organizationName', 'documentTitle'));
    }

    public function turnover(Request $request, AssetTransfer $transfer)
    {
        abort_unless($request->user()->hasPermission('transfers.view') || $request->user()->hasPermission('transfers.manage'), 403);
        $transfer->load(['asset','fromEmployee.department','toEmployee.department','approver']);
        $organizationName = data_get(\App\Models\SystemSetting::query()->where('key','organization_name')->first()?->value, 'value', config('app.name'));
        return view('inventory.asset-turnover', compact('transfer','organizationName'));
    }

    public function assign(Request $request, Asset $asset)
    {
        abort_unless($request->user()->hasPermission('assignments.manage'),403);
        $data=$request->validate(['employee_id'=>['required','exists:employees,id'],'department_id'=>['nullable','exists:departments,id'],'location_id'=>['nullable','exists:locations,id'],'assignment_type'=>['required','in:temporary,permanent'],'requires_approval'=>['nullable','boolean'],'issued_at'=>'required|date','due_date'=>'nullable|date|after_or_equal:issued_at','remarks'=>'nullable|string|max:2000','acknowledged_by'=>'nullable|string|max:150','signature_data'=>'nullable|string|max:1400000']);
        $assignment=DB::transaction(function()use($data,$asset,$request){
            $locked=Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status==='available',422,'Only available assets may be assigned.');
            $employee=\App\Models\Employee::whereKey($data['employee_id'])->where('status','active')->firstOrFail();
            if (!empty($data['department_id'])) \App\Models\Department::whereKey($data['department_id'])->firstOrFail();
            if (!empty($data['location_id'])) \App\Models\Location::whereKey($data['location_id'])->firstOrFail();
            $ref='ASN-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $signaturePath = null;
            if (!empty($data['signature_data']) && empty($data['requires_approval'])) {
                if (!preg_match('/^data:image\/png;base64,([A-Za-z0-9+\/]+=*)$/', $data['signature_data'], $matches)) {
                    throw ValidationException::withMessages(['signature_data' => 'The acknowledgment signature must be a valid PNG image.']);
                }
                $signatureBytes = base64_decode($matches[1], true);
                if ($signatureBytes === false || strlen($signatureBytes) > 1024 * 1024 || !str_starts_with($signatureBytes, "\x89PNG\r\n\x1a\n")) {
                    throw ValidationException::withMessages(['signature_data' => 'The signature image is invalid or exceeds 1 MB.']);
                }
                $signaturePath = 'signatures/assignments/'.Str::uuid().'.png';
                Storage::disk('local')->put($signaturePath, $signatureBytes);
            }
            $pending = (bool) ($data['requires_approval'] ?? false);
            $assignment=Assignment::create(['reference_no'=>$ref,'asset_id'=>$locked->id,'employee_id'=>$employee->id,'issued_by'=>$request->user()->id,'requested_by'=>$request->user()->id,'department_id'=>$data['department_id'] ?? $employee->department_id,'location_id'=>$data['location_id'] ?? $locked->location_id,'assignment_type'=>$data['assignment_type'],'issued_at'=>$data['issued_at'],'due_date'=>$data['due_date']??null,'due_back_at'=>!empty($data['due_date']) ? $data['due_date'].' 23:59:59' : null,'remarks'=>$data['remarks']??null,'acknowledged_by'=>$data['acknowledged_by']??$employee->full_name,'acknowledged_at'=>$pending ? null : now(),'signature_path'=>$pending ? null : $signaturePath,'status'=>$pending ? 'pending_approval' : 'issued']);
            if (! $pending) {
                $oldAsset = $locked->only(['status', 'current_employee_id', 'department_id', 'location_id']);
                $locked->update(['status'=>'assigned','current_employee_id'=>$employee->id,'department_id'=>$assignment->department_id,'location_id'=>$assignment->location_id]);
                app(AssetHistoryWriter::class)->record($locked, 'assigned', $oldAsset, $locked->only(['status', 'current_employee_id', 'department_id', 'location_id']));
            } else {
                app(AssetHistoryWriter::class)->record($locked, 'assignment_requested', [], ['assignment_id'=>$assignment->id,'employee_id'=>$employee->id]);
            }
            return $assignment;
        });
        Audit::record('assigned','assignments',Asset::class,$asset->id,[],['employee_id'=>$data['employee_id'],'assignment_id'=>$assignment->id]);
        if (!empty($data['requires_approval'])) { $this->notifyAssignmentApprovers('An asset assignment is awaiting approval.', $assignment->id); }
        return back()->with('success', !empty($data['requires_approval']) ? 'Assignment submitted for approval.' : 'Asset assigned successfully.');
    }


    private function notifyAssignmentApprovers(string $message, ?int $assignmentId): void
    {
        try {
            $url = route('inventory.index', 'assignments');
            $officeId = $assignmentId ? Assignment::whereKey($assignmentId)->value('office_id') : null;
            $approvers = \App\Models\User::where('is_active', true)
                ->whereHas('roles.permissions', fn ($query) => $query->where('name', 'approvals.approve'));
            if ($officeId) $approvers->where(fn ($query) => $query->where('office_id', $officeId)->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', 'super-admin')));
            foreach ($approvers->get() as $user) {
                $user->notify(new SystemAlert('Asset assignment approval', $message, $url, 'info', 'assignment-approval:'.($assignmentId ?? 'batch').':'.$user->id));
            }
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function bulkAssign(Request $request)
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);
        $data = $request->validate([
            'asset_ids' => ['required','array','min:1','max:100'],
            'asset_ids.*' => ['required','integer','distinct','exists:assets,id'],
            'employee_id' => ['required','exists:employees,id'],
            'department_id' => ['nullable','exists:departments,id'],
            'location_id' => ['nullable','exists:locations,id'],
            'assignment_type' => ['required','in:temporary,permanent'],
            'issued_at' => ['required','date'],
            'due_date' => ['nullable','date','after_or_equal:issued_at'],
            'remarks' => ['nullable','string','max:2000'],
        ]);
        $employee = \App\Models\Employee::whereKey($data['employee_id'])->where('status','active')->firstOrFail();
        if (!empty($data['department_id'])) \App\Models\Department::whereKey($data['department_id'])->firstOrFail();
        if (!empty($data['location_id'])) \App\Models\Location::whereKey($data['location_id'])->firstOrFail();
        $created = DB::transaction(function () use ($data, $employee, $request) {
            $created = [];
            foreach ($data['asset_ids'] as $assetId) {
                $asset = Asset::whereKey($assetId)->lockForUpdate()->firstOrFail();
                abort_unless($asset->status === 'available', 422, 'Every selected asset must be available. No assignments were submitted.');
                $assignment = Assignment::create([
                    'reference_no' => 'ASN-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                    'asset_id' => $asset->id, 'employee_id' => $employee->id,
                    'issued_by' => $request->user()->id, 'requested_by' => $request->user()->id,
                    'department_id' => $data['department_id'] ?? $employee->department_id,
                    'location_id' => $data['location_id'] ?? $asset->location_id,
                    'assignment_type' => $data['assignment_type'],
                    'issued_at' => $data['issued_at'], 'due_date' => $data['due_date'] ?? null,
                    'due_back_at' => !empty($data['due_date']) ? $data['due_date'].' 23:59:59' : null,
                    'remarks' => $data['remarks'] ?? null, 'status' => 'pending_approval',
                ]);
                app(AssetHistoryWriter::class)->record($asset, 'assignment_requested', [], ['assignment_id'=>$assignment->id,'employee_id'=>$employee->id]);
                $created[] = $assignment->id;
            }
            return $created;
        }, 3);
        Audit::record('bulk_assignment_requested', 'assignments', Assignment::class, null, [], ['assignment_ids'=>$created]);
        $this->notifyAssignmentApprovers(count($created).' asset assignment(s) are awaiting approval.', $created[0] ?? null);
        return back()->with('success', count($created).' assignment(s) submitted for approval.');
    }

    public function approveAssignment(Request $request, Assignment $assignment)
    {
        abort_unless($request->user()->hasPermission('approvals.approve'), 403);
        DB::transaction(function () use ($request, $assignment) {
            $record = Assignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_approval', 422, 'This assignment is no longer pending approval.');
            $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless($asset->status === 'available', 422, 'The asset is no longer available.');
            $employee = \App\Models\Employee::whereKey($record->employee_id)->where('status','active')->firstOrFail();
            $record->update(['status'=>'issued','approved_by'=>$request->user()->id,'approved_at'=>now()]);
            $old = $asset->only(['status','current_employee_id','department_id','location_id']);
            $asset->update(['status'=>'assigned','current_employee_id'=>$employee->id,'department_id'=>$record->department_id ?: $employee->department_id,'location_id'=>$record->location_id ?: $asset->location_id]);
            app(AssetHistoryWriter::class)->record($asset, 'assigned', $old, $asset->only(['status','current_employee_id','department_id','location_id']) + ['assignment_id'=>$record->id]);
        }, 3);
        Audit::record('assignment_approved','assignments',Assignment::class,$assignment->id);
        return back()->with('success','Assignment approved and asset accountability updated.');
    }

    public function rejectAssignment(Request $request, Assignment $assignment)
    {
        abort_unless($request->user()->hasPermission('approvals.approve'), 403);
        DB::transaction(function () use ($request, $assignment) {
            $record = Assignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'pending_approval', 422, 'This assignment is no longer pending approval.');
            $record->update(['status'=>'rejected','approved_by'=>$request->user()->id,'approved_at'=>now(),'remarks'=>trim(($record->remarks ?? '')."\nRejected: ".(string)$request->input('remarks'))]);
            $asset = Asset::whereKey($record->asset_id)->first();
            if ($asset) app(AssetHistoryWriter::class)->record($asset, 'assignment_rejected', [], ['assignment_id'=>$record->id]);
        });
        Audit::record('assignment_rejected','assignments',Assignment::class,$assignment->id);
        return back()->with('success','Assignment request rejected.');
    }

    public function signature(Request $request, Assignment $assignment)
    {
        abort_unless($request->user()->hasPermission('assignments.view'), 403);
        abort_unless($assignment->signature_path && Storage::disk('local')->exists($assignment->signature_path), 404);
        return Storage::disk('local')->response($assignment->signature_path, null, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function returnAsset(Request $request, Assignment $assignment)
    {
        abort_unless($request->user()->hasPermission('assignments.manage'), 403);
        $data = $request->validate([
            'condition' => 'required|in:New,Good,Fair,Defective',
            'remarks' => 'nullable|string|max:2000',
        ]);
        DB::transaction(function () use ($data, $assignment) {
            $record = Assignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($record->status === 'issued', 422, 'This assignment has already been returned.');
            $asset = Asset::whereKey($record->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $asset->current_employee_id === (int) $record->employee_id, 422, 'The current custodian no longer matches this assignment.');
            $record->update([
                'status' => 'returned',
                'returned_at' => today(),
                'remarks' => trim(($record->remarks ?? '') . "\nReturn: " . ($data['remarks'] ?? '')),
            ]);
            $oldAsset = $asset->only(['status', 'condition', 'current_employee_id']);
            $asset->update([
                'status' => $data['condition'] === 'Defective' ? 'defective' : 'available',
                'condition' => $data['condition'],
                'current_employee_id' => null,
            ]);
            app(AssetHistoryWriter::class)->record($asset, 'returned', $oldAsset, $asset->only(['status', 'condition', 'current_employee_id']));
        });
        Audit::record('returned', 'assignments', Assignment::class, $assignment->id);
        return back()->with('success', 'Asset return recorded.');
    }

    public function requestTransfer(Request $request, Asset $asset)
    {
        abort_unless($request->user()->hasPermission('transfers.manage'), 403);
        $data = $request->validate([
            'to_employee_id' => ['required', 'exists:employees,id'],
            'reason' => 'required|string|max:2000',
            'transfer_type' => 'required|in:temporary,permanent',
            'to_department_id' => 'nullable|exists:departments,id',
            'to_location_id' => 'nullable|exists:locations,id',
        ]);
        $transfer = DB::transaction(function () use ($asset, $data, $request) {
            $lockedAsset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedAsset->status === 'assigned' && $lockedAsset->current_employee_id, 422, 'Only currently assigned assets can be transferred.');
            abort_if((int) $lockedAsset->current_employee_id === (int) $data['to_employee_id'], 422, 'The selected employee is already the current custodian.');
            $target = \App\Models\Employee::whereKey($data['to_employee_id'])->where('status', 'active')->firstOrFail();
            if (!empty($data['to_department_id'])) \App\Models\Department::whereKey($data['to_department_id'])->firstOrFail();
            if (!empty($data['to_location_id'])) \App\Models\Location::whereKey($data['to_location_id'])->firstOrFail();
            abort_if(AssetTransfer::where('asset_id', $lockedAsset->id)->where('status', 'pending')->exists(), 422, 'A transfer request is already pending for this asset.');

            $transfer = AssetTransfer::create([
                'reference_no' => 'TRF-' . now()->format('ymd') . '-' . Str::upper(Str::random(6)),
                'asset_id' => $lockedAsset->id,
                'from_employee_id' => $lockedAsset->current_employee_id,
                'to_employee_id' => $target->id,
                'reason' => $data['reason'],
                'transfer_type' => $data['transfer_type'],
                'to_department_id' => $data['to_department_id'] ?? $target->department_id,
                'to_location_id' => $data['to_location_id'] ?? $lockedAsset->location_id,
                'status' => 'pending',
                'requested_by' => $request->user()->id,
            ]);

            // Keep the transfer request and asset timeline entry atomic. A
            // history-write failure must not leave a committed request while
            // returning an error to the user.
            app(AssetHistoryWriter::class)->record($lockedAsset, 'transfer_requested', [], [
                'transfer_id' => $transfer->id,
                'to_employee_id' => $transfer->to_employee_id,
            ]);

            return $transfer;
        }, 3);
        Audit::record('transfer_requested', 'transfers', AssetTransfer::class, $transfer->id);
        return back()->with('success', 'Transfer request submitted for approval.');
    }

    public function approveTransfer(Request $request, AssetTransfer $transfer)
    {
        abort_unless($request->user()->hasPermission('transfers.approve'),403);
        abort_unless($transfer->status==='pending',422,'Transfer is no longer pending.');
        DB::transaction(function()use($request,$transfer){
            $current=AssetTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($current->status==='pending',422,'Transfer is no longer pending.');
            $asset=Asset::whereKey($current->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless($asset->status==='assigned' && (int)$asset->current_employee_id===(int)$current->from_employee_id,422,'The asset custodian changed after this transfer was requested. Create a new transfer request.');
            $employee=\App\Models\Employee::whereKey($current->to_employee_id)->where('status','active')->firstOrFail();
            $oldAsset = $asset->only(['status', 'current_employee_id', 'department_id', 'location_id']);
            $current->update(['status'=>'completed','approved_by'=>$request->user()->id,'approved_at'=>now()]);
            $asset->update(['current_employee_id'=>$employee->id,'department_id'=>$current->to_department_id ?: $employee->department_id,'location_id'=>$current->to_location_id ?: $asset->location_id,'status'=>'assigned']);
            app(AssetHistoryWriter::class)->record($asset, 'transferred', $oldAsset, $asset->only(['status', 'current_employee_id', 'department_id', 'location_id']) + ['transfer_id' => $current->id]);
        });
        Audit::record('transfer_approved','transfers',AssetTransfer::class,$transfer->id);
        return back()->with('success','Transfer approved and custodian updated.');
    }

    public function rejectTransfer(Request $request, AssetTransfer $transfer)
    {
        abort_unless($request->user()->hasPermission('transfers.approve'), 403);
        DB::transaction(function () use ($request, $transfer) {
            $locked = AssetTransfer::whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'Transfer request is no longer pending.');
            $locked->update(['status' => 'rejected', 'approved_by' => $request->user()->id, 'approved_at' => now()]);
            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->first();
            if ($asset) {
                app(AssetHistoryWriter::class)->record($asset, 'transfer_rejected', [], ['transfer_id' => $locked->id]);
            }
        }, 3);
        Audit::record('transfer_rejected', 'transfers', AssetTransfer::class, $transfer->id);
        return back()->with('success', 'Transfer request rejected.');
    }

    public function stockMovement(Request $request, InventoryItem $item)
    {
        abort_unless($request->user()->hasPermission('consumables.manage'), 403);
        $data = $request->validate([
            'type' => 'required|in:in,out,adjustment',
            'quantity' => ['required', 'integer', $request->input('type') === 'adjustment' ? 'min:0' : 'min:1'],
            'reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $movementType = match ($data['type']) {
            'in' => 'receive',
            'out' => 'issue',
            'adjustment' => 'adjust',
        };
        $updatedItem = app(StockLedgerService::class)->move(
            $item->id,
            $movementType,
            (int) $data['quantity'],
            $request->user()->id,
            $data['reference'] ?? null,
            $data['remarks'] ?? null,
        );

        if ((int) $updatedItem->quantity_on_hand <= (int) $updatedItem->reorder_level) {
            try {
                foreach (\App\Models\User::where('is_active', true)->whereHas('roles.permissions', fn ($q) => $q->where('name', 'consumables.manage'))->get() as $user) {
                    $user->notify(new SystemAlert(
                        'Low stock alert',
                        $updatedItem->name.' has '.$updatedItem->quantity_on_hand.' '.$updatedItem->unit.' remaining.',
                        route('inventory.show', ['consumables', $updatedItem->id]),
                        'warning'
                    ));
                }
            } catch (\Throwable $exception) {
                // Notification delivery must not make a committed stock ledger
                // movement appear to have failed. The movement remains saved;
                // notification errors are still recorded for administrators.
                report($exception);
            }
        }

        Audit::record('stock_'.$data['type'], 'consumables', InventoryItem::class, $item->id, [], [
            'quantity' => $data['quantity'],
            'resulting_balance' => $updatedItem->quantity_on_hand,
        ]);

        return back()->with('success', 'Stock movement recorded.');
    }

    public function verifyAudit(Request $request, PhysicalAudit $audit)
    {
        abort_unless($request->user()->hasPermission('audits.manage'), 403);
        $data = $request->validate([
            'asset_id' => 'nullable|exists:assets,id',
            'asset_tag' => 'nullable|string|max:80',
            'result' => 'required|in:verified,not_found,transferred,damaged,wrong_location,wrong_custodian,unregistered',
            'seen_location_id' => 'nullable|exists:locations,id',
            'seen_employee_id' => 'nullable|exists:employees,id',
            'condition' => 'nullable|in:New,Good,Fair,Defective',
            'photo' => 'nullable|image|max:5120',
            'notes' => 'nullable|string|max:2000',
        ]);

        $finding = DB::transaction(function () use ($request, $audit, $data) {
            $lockedAudit = PhysicalAudit::whereKey($audit->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedAudit->status, ['planned', 'in_progress'], true), 422, 'This physical inventory audit is already closed.');

            $tag = trim((string) ($data['asset_tag'] ?? ''));
            $asset = null;
            if (! empty($data['asset_id'])) {
                $asset = Asset::whereKey($data['asset_id'])->lockForUpdate()->firstOrFail();
                if ($tag === '') $tag = (string) $asset->asset_tag;
            } elseif ($tag !== '') {
                $asset = Asset::query()
                    ->where(fn ($query) => $query->where('asset_tag', $tag)->orWhere('serial_number', $tag))
                    ->lockForUpdate()
                    ->first();
            }

            if ($data['result'] === 'unregistered') {
                if ($tag === '' || $asset) {
                    throw ValidationException::withMessages(['asset_tag' => 'Enter an identifier that does not exist in the registered asset list.']);
                }
                if (Asset::query()->where(fn ($query) => $query->where('asset_tag', $tag)->orWhere('serial_number', $tag))->exists()) {
                    throw ValidationException::withMessages(['asset_tag' => 'This identifier belongs to a registered asset.']);
                }
            } elseif (! $asset) {
                throw ValidationException::withMessages(['asset_tag' => 'Select a checklist asset or enter a registered asset tag/serial number.']);
            }

            if ($asset) {
                $auditItem = InventoryAuditItem::query()
                    ->where('audit_id', $lockedAudit->id)
                    ->where('asset_id', $asset->id)
                    ->lockForUpdate()
                    ->first();

                if ($auditItem && $auditItem->result !== 'Pending') {
                    throw ValidationException::withMessages(['asset_id' => 'This asset has already been verified in this audit.']);
                }
            } else {
                $auditItem = InventoryAuditItem::query()
                    ->where('audit_id', $lockedAudit->id)
                    ->whereNull('asset_id')
                    ->where('unregistered_tag', $tag)
                    ->lockForUpdate()
                    ->first();

                if ($auditItem) {
                    throw ValidationException::withMessages(['asset_tag' => 'This unregistered identifier has already been recorded in this audit.']);
                }
            }

            $resultLabels = [
                'verified' => 'Verified',
                'not_found' => 'Not Found',
                'transferred' => 'Transferred',
                'damaged' => 'Damaged',
                'wrong_location' => 'Incorrect Location',
                'wrong_custodian' => 'Incorrect Custodian',
                'unregistered' => 'Unregistered',
            ];
            $photoPath = ! empty($data['photo']) ? $data['photo']->store('audit-photos', 'local') : $auditItem?->photo_path;
            $itemValues = [
                'audit_id' => $lockedAudit->id,
                'asset_id' => $asset?->id,
                'unregistered_tag' => $data['result'] === 'unregistered' ? $tag : null,
                'result' => $resultLabels[$data['result']],
                'seen_location_id' => $data['seen_location_id'] ?? null,
                'seen_employee_id' => $data['seen_employee_id'] ?? null,
                'condition' => $data['condition'] ?? $asset?->condition,
                'photo_path' => $photoPath,
                'scanned_at' => now(),
                'auditor_id' => $request->user()->id,
                'remarks' => $data['notes'] ?? null,
            ];

            if ($auditItem) {
                $auditItem->fill($itemValues)->save();
            } else {
                $auditItem = InventoryAuditItem::create($itemValues);
            }

            // Maintain the existing JSON findings field for compatibility with
            // earlier reports while the normalized audit-items table is primary.
            $finding = [
                'audit_item_id' => $auditItem->id,
                'asset_id' => $asset?->id,
                'asset_tag' => $asset?->asset_tag ?? ($tag ?: null),
                'result' => $data['result'],
                'notes' => $data['notes'] ?? null,
                'verified_by' => $request->user()->id,
                'verified_at' => now()->toDateTimeString(),
            ];
            $items = $lockedAudit->findings ?? [];
            $items[] = $finding;
            $lockedAudit->update([
                'findings' => $items,
                'verified_count' => $lockedAudit->items()->where('result', 'Verified')->count(),
                'status' => 'in_progress',
            ]);

            return $finding;
        }, 3);

        Audit::record('asset_verified', 'audits', PhysicalAudit::class, $audit->id, [], $finding);
        return back()->with('success', 'Inventory finding recorded.');
    }

    public function completeAudit(Request $request, PhysicalAudit $audit)
    {
        abort_unless($request->user()->hasPermission('audits.manage'), 403);
        DB::transaction(function () use ($audit) {
            $locked = PhysicalAudit::whereKey($audit->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['planned', 'in_progress'], true), 422, 'This audit is already closed.');
            abort_if(count($locked->findings ?? []) === 0, 422, 'Record at least one physical verification finding before completing this audit.');
            abort_if($locked->items()->where('result', 'Pending')->exists(), 422, 'Complete every asset in the generated audit checklist before closing this audit.');
            $locked->update(['status' => 'completed', 'completed_at' => now()]);
        });
        Audit::record('audit_completed', 'audits', PhysicalAudit::class, $audit->id);
        return back()->with('success', 'Physical inventory audit completed.');
    }
}

