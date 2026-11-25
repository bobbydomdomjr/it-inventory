<?php
namespace App\Http\Controllers;

use App\Models\{Asset,AssetDisposal,Employee,Procurement,SoftwareLicense,SoftwareLicenseAssignment,SupportTicket,TicketComment};
use App\Notifications\SystemAlert;
use App\Services\AssetTagService;
use App\Models\SystemSetting;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeatureWorkflowController extends Controller
{


    public function registerProcurementAsset(Request $request, Procurement $procurement)
    {
        abort_unless($request->user()->hasPermission('assets.manage') && $request->user()->hasPermission('procurements.manage'), 403);
        $automaticNumbering = (bool) data_get(SystemSetting::where('key', 'automatic_asset_numbering')->first()?->value, 'value', true);
        if ($automaticNumbering && ! $request->filled('asset_tag')) {
            $request->merge(['asset_tag' => app(AssetTagService::class)->next()]);
        }
        $data = $request->validate([
            'asset_tag' => 'required|string|max:80|unique:assets,asset_tag',
            'name' => 'required|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'brand' => 'nullable|string|max:120',
            'model' => 'nullable|string|max:120',
            'specifications' => 'nullable|string|max:5000',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'warranty_expiration' => 'nullable|date|after_or_equal:purchase_date',
            'department_id' => 'nullable|exists:departments,id',
            'location_id' => 'nullable|exists:locations,id',
            'condition' => 'required|in:New,Good,Fair,Defective',
            'remarks' => 'nullable|string|max:3000',
        ]);
        $category = \App\Models\AssetCategory::query()->where('type', 'asset')->where('is_active', true)->findOrFail($data['category_id']);
        $defaultUsefulLife = (int) data_get(SystemSetting::where('key', 'default_useful_life_months')->first()?->value, 'value', 48);
        $defaultDepreciationMethod = (string) data_get(SystemSetting::where('key', 'default_depreciation_method')->first()?->value, 'value', 'straight_line');
        $defaultSalvagePercent = (float) data_get(SystemSetting::where('key', 'default_salvage_percent')->first()?->value, 'value', 0);
        $usefulLife = $category->useful_life_months ?: ($category->default_useful_life_months ?: $defaultUsefulLife);
        $depreciationMethod = ! in_array($category->depreciation_method, [null, '', 'not_configured'], true) ? $category->depreciation_method : $defaultDepreciationMethod;
        $salvagePercent = (float) $category->salvage_percent > 0 ? (float) $category->salvage_percent : $defaultSalvagePercent;
        $defaultSalvageValue = round(((float) $data['purchase_cost']) * $salvagePercent / 100, 2);
        $asset = DB::transaction(function () use ($procurement, $data, $usefulLife, $depreciationMethod, $defaultSalvageValue) {
            $locked = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['received', 'partially_received'], true), 422, 'Assets may only be registered from accepted deliveries.');
            $registered = Asset::withTrashed()->where('procurement_id', $locked->id)->count();
            abort_if($registered >= (int) $locked->quantity_received, 422, 'All accepted units have already been registered as assets.');
            $supplier = $locked->supplier_name
                ? \App\Models\Supplier::where('company_name', $locked->supplier_name)->first()
                : null;
            $asset = Asset::create($data + [
                'procurement_id' => $locked->id,
                'supplier_id' => $supplier?->id,
                'acquisition_method' => 'procurement',
                'useful_life_months' => $usefulLife,
                'depreciation_method' => $depreciationMethod,
                'salvage_value' => $defaultSalvageValue,
                'status' => 'available',
            ]);
            app(\App\Services\AssetHistoryWriter::class)->record($asset, 'procurement_registered', [], $asset->getAttributes());

            return $asset;
        });
        Audit::record('asset_registered_from_procurement', 'assets', Asset::class, $asset->id, [], ['procurement_id' => $procurement->id]);
        return redirect()->route('inventory.show', ['assets', $asset->id])->with('success', 'Asset registered from accepted procurement delivery.');
    }

    public function placeProcurementOrder(Request $request, Procurement $procurement)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $data = $request->validate([
            'purchase_order_no' => 'required|string|max:120|unique:procurements,purchase_order_no,' . $procurement->id,
            'expected_delivery_at' => 'required|date|after_or_equal:today',
            'remarks' => 'nullable|string|max:2000',
        ]);
        DB::transaction(function () use ($procurement, $data) {
            $locked = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'approved', 422, 'Only approved procurement requests may be ordered.');
            $locked->update([
                'purchase_order_no' => $data['purchase_order_no'],
                'expected_delivery_at' => $data['expected_delivery_at'],
                'status' => 'ordered',
                'remarks' => trim(($locked->remarks ?? '') . "\n" . ($data['remarks'] ?? '')),
            ]);
        });
        Audit::record('procurement_order_placed', 'procurements', Procurement::class, $procurement->id);
        return back()->with('success', 'Purchase order recorded.');
    }

    public function receiveProcurement(Request $request, Procurement $procurement)
    {
        abort_unless($request->user()->hasPermission('procurements.manage'), 403);
        $data = $request->validate([
            'received_quantity' => 'required|integer|min:1',
            'received_at' => 'required|date',
            'inspection_result' => 'required|in:accepted,rejected',
            'inspection_remarks' => 'nullable|string|max:2000',
        ]);
        DB::transaction(function () use ($request, $procurement, $data) {
            $locked = Procurement::whereKey($procurement->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, ['ordered', 'partially_received'], true), 422, 'This procurement is not awaiting delivery.');
            $remaining = max(0, (int) $locked->quantity - (int) $locked->quantity_received);
            abort_if((int) $data['received_quantity'] > $remaining, 422, 'The received quantity exceeds the outstanding quantity.');

            if ($data['inspection_result'] === 'rejected') {
                $locked->update([
                    'received_at' => $data['received_at'],
                    'received_by_user_id' => $request->user()->id,
                    'inspection_result' => 'rejected',
                    'quantity_rejected' => (int) $locked->quantity_rejected + (int) $data['received_quantity'],
                    'inspection_remarks' => $data['inspection_remarks'] ?? null,
                    'status' => 'inspection_failed',
                ]);
                return;
            }

            $receivedTotal = (int) $locked->quantity_received + (int) $data['received_quantity'];
            $locked->update([
                'quantity_received' => $receivedTotal,
                'received_at' => $data['received_at'],
                'received_by_user_id' => $request->user()->id,
                'inspection_result' => 'accepted',
                'inspection_remarks' => $data['inspection_remarks'] ?? null,
                'status' => $receivedTotal >= (int) $locked->quantity ? 'received' : 'partially_received',
            ]);
        });
        Audit::record('procurement_received', 'procurements', Procurement::class, $procurement->id);
        return back()->with('success', 'Delivery and inspection result recorded.');
    }

    public function ticketComment(Request $request, SupportTicket $ticket)
    {
        abort_unless($request->user()->hasPermission('tickets.manage') || $request->user()->hasPermission('tickets.comment'),403);
        $data=$request->validate(['comment'=>'required|string|max:5000','is_internal'=>'nullable|boolean']);
        $comment=$ticket->comments()->create(['user_id'=>$request->user()->id,'comment'=>$data['comment'],'is_internal'=>$request->boolean('is_internal') && $request->user()->hasPermission('tickets.manage')]);
        if(!$ticket->first_response_at && $request->user()->hasPermission('tickets.manage'))$ticket->update(['first_response_at'=>now()]);
        Audit::record('ticket_commented','tickets',TicketComment::class,$comment->id);
        return back()->with('success','Ticket update added.');
    }
    public function assignSoftware(Request $request, SoftwareLicense $license)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'asset_id' => 'nullable|exists:assets,id',
            'assigned_to' => 'nullable|string|max:150',
            'assigned_at' => 'required|date',
        ]);
        if (empty($data['employee_id']) && empty($data['asset_id']) && empty($data['assigned_to'])) {
            throw ValidationException::withMessages(['assigned_to' => 'Select an employee, asset, or enter an assignee.']);
        }

        DB::transaction(function () use ($data, $license, $request) {
            $locked = SoftwareLicense::whereKey($license->id)->lockForUpdate()->firstOrFail();
            if ($locked->quantity_assigned >= $locked->quantity_purchased) {
                throw ValidationException::withMessages(['employee_id' => 'No unassigned license seats are available.']);
            }
            if (! empty($data['employee_id'])) {
                Employee::whereKey($data['employee_id'])->where('status', 'active')->firstOrFail();
            }
            SoftwareLicenseAssignment::create([
                'software_license_id' => $locked->id,
                'employee_id' => $data['employee_id'] ?? null,
                'asset_id' => $data['asset_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'assigned_at' => $data['assigned_at'],
                'status' => 'active',
                'assigned_by' => $request->user()->id,
            ]);
            $locked->increment('quantity_assigned');
        });

        Audit::record('license_assigned', 'software', SoftwareLicense::class, $license->id);
        return back()->with('success', 'Software license assigned.');
    }

    public function returnSoftware(Request $request, SoftwareLicenseAssignment $assignment)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        DB::transaction(function () use ($assignment) {
            $lockedAssignment = SoftwareLicenseAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedAssignment->status === 'active', 422, 'License allocation is already closed.');
            $license = SoftwareLicense::whereKey($lockedAssignment->software_license_id)->lockForUpdate()->firstOrFail();
            $lockedAssignment->update(['status' => 'returned', 'returned_at' => today()]);
            $license->decrement('quantity_assigned');
        });
        Audit::record('license_returned', 'software', SoftwareLicenseAssignment::class, $assignment->id);
        return back()->with('success', 'Software license returned to available pool.');
    }

    public function requestDisposal(Request $request, Asset $asset)
    {
        abort_unless($request->user()->hasPermission('disposals.manage'), 403);
        $data = $request->validate([
            'proposed_method' => 'required|in:sale,donation,recycling,destruction,return_to_supplier',
            'justification' => 'required|string|max:3000',
            'technical_condition' => 'nullable|string|max:500',
            'estimated_salvage_value' => 'nullable|numeric|min:0',
            'requested_at' => 'required|date',
            'remarks' => 'nullable|string|max:2000',
        ]);
        $disposal = DB::transaction(function () use ($asset, $data, $request) {
            $lockedAsset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($lockedAsset->status, ['assigned', 'lost', 'retired'], true), 422, 'Return assigned assets and resolve lost or retired status before disposal.');
            $existing = AssetDisposal::where('asset_id', $lockedAsset->id)
                ->whereIn('status', ['pending_approval', 'approved'])->lockForUpdate()->exists();
            abort_if($existing, 422, 'An active disposal request already exists for this asset.');

            $disposal = AssetDisposal::create($data + [
                'disposal_no' => 'DSP-' . now()->format('ymd') . '-' . str_pad((string) (AssetDisposal::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT),
                'asset_id' => $lockedAsset->id,
                'status' => 'pending_approval',
                'requested_by' => $request->user()->id,
            ]);
            app(\App\Services\AssetHistoryWriter::class)->record(
                $lockedAsset,
                'disposal_requested',
                ['status' => $lockedAsset->status],
                ['disposal_id' => $disposal->id, 'disposal_no' => $disposal->disposal_no, 'status' => $disposal->status]
            );
            return $disposal;
        });
        Audit::record('disposal_requested', 'disposals', AssetDisposal::class, $disposal->id);
        return redirect()->route('inventory.show', ['disposals', $disposal->id])->with('success', 'Disposal request created. Submit it for approval.');
    }

    public function completeDisposal(Request $request, AssetDisposal $disposal)
    {
        abort_unless($request->user()->hasPermission('disposals.manage'), 403);
        $data = $request->validate(['disposed_at' => 'required|date', 'remarks' => 'nullable|string|max:2000']);
        DB::transaction(function () use ($data, $disposal) {
            $locked = AssetDisposal::whereKey($disposal->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'approved', 422, 'Only approved disposal requests may be completed.');
            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($asset->status, ['assigned', 'lost', 'retired'], true), 422, 'The asset is not eligible for disposal completion.');
            $locked->update([
                'status' => 'disposed',
                'disposed_at' => $data['disposed_at'],
                'remarks' => trim(($locked->remarks ?? '') . "\n" . ($data['remarks'] ?? '')),
            ]);
            $oldAsset = $asset->only(['status', 'disposed_at', 'salvage_value']);
            $asset->update(['status' => 'retired', 'disposed_at' => $data['disposed_at'], 'salvage_value' => $locked->estimated_salvage_value]);
            app(\App\Services\AssetHistoryWriter::class)->record($asset, 'disposed', $oldAsset, $asset->only(['status', 'disposed_at', 'salvage_value']));
        });
        Audit::record('asset_disposed', 'disposals', AssetDisposal::class, $disposal->id);
        return back()->with('success', 'Asset disposal completion recorded.');
    }

    public function completeMaintenance(Request $request, \App\Models\MaintenanceRecord $maintenance)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $data = $request->validate([
            'resolution' => 'required|string|max:3000',
            'completed_at' => 'required|date',
            'cost' => 'nullable|numeric|min:0',
            'parts_used' => 'nullable|string|max:3000',
            'next_maintenance_at' => 'nullable|date|after:completed_at',
        ]);
        DB::transaction(function () use ($maintenance, $data) {
            $locked = \App\Models\MaintenanceRecord::whereKey($maintenance->id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($locked->status, ['completed', 'cancelled'], true), 422, 'This work order is already closed.');
            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();
            abort_unless($asset->status === 'maintenance', 422, 'The asset is not currently marked for maintenance.');
            $oldAsset = $asset->only(['status', 'condition']);
            $locked->update($data + ['status' => 'completed']);
            $otherOpenWorkOrders = \App\Models\MaintenanceRecord::where('asset_id', $asset->id)
                ->whereKeyNot($locked->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();
            if (! $otherOpenWorkOrders) {
                $asset->update(['status' => $asset->condition === 'Defective' ? 'defective' : 'available']);
            }
            app(\App\Services\AssetHistoryWriter::class)->record($asset, 'maintenance_completed', $oldAsset, $asset->only(['status', 'condition']));
        });
        Audit::record('maintenance_completed', 'maintenance', \App\Models\MaintenanceRecord::class, $maintenance->id);
        return back()->with('success', 'Maintenance work order completed.');
    }

    public function cancelMaintenance(Request $request, \App\Models\MaintenanceRecord $maintenance)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $data = $request->validate(['remarks' => 'required|string|max:2000']);
        DB::transaction(function () use ($maintenance, $data) {
            $locked = \App\Models\MaintenanceRecord::whereKey($maintenance->id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($locked->status, ['completed', 'cancelled'], true), 422, 'This work order is already closed.');
            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();
            $locked->update([
                'status' => 'cancelled',
                'resolution' => trim(($locked->resolution ?? '') . "\nCancelled: " . $data['remarks']),
            ]);
            $otherOpenWorkOrders = \App\Models\MaintenanceRecord::where('asset_id', $asset->id)
                ->whereKeyNot($locked->id)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->exists();
            if (! $otherOpenWorkOrders && $asset->status === 'maintenance') {
                $asset->update(['status' => $asset->condition === 'Defective' ? 'defective' : 'available']);
            }
        });
        Audit::record('maintenance_cancelled', 'maintenance', \App\Models\MaintenanceRecord::class, $maintenance->id);
        return back()->with('success', 'Maintenance work order cancelled.');
    }
}
