<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\MaintenanceSchedule;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\MaintenanceScheduleService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceScheduleController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('maintenance.view'), 403);
        $schedules = MaintenanceSchedule::with(['asset', 'category', 'assignee'])->latest()->paginate(15, ['*'], 'schedules_page');
        $workOrders = WorkOrder::with(['asset', 'technician', 'maintenanceSchedule'])->latest()->paginate(20, ['*'], 'orders_page');
        $assets = Asset::whereNotIn('status', ['retired', 'lost', 'disposed'])->orderBy('asset_tag')->get();
        $categories = AssetCategory::where('type', 'asset')->orderBy('name')->get();
        $technicians = User::where('is_active', true)->orderBy('name')->get();

        return view('maintenance-schedules.index', compact('schedules', 'workOrders', 'assets', 'categories', 'technicians'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $data = $request->validate([
            'asset_id' => ['nullable', 'integer', 'exists:assets,id'],
            'category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'interval_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'next_due' => ['required', 'date'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);
        if ((bool) ($data['asset_id'] ?? null) === (bool) ($data['category_id'] ?? null)) {
            throw ValidationException::withMessages(['asset_id' => 'Select exactly one target: a specific asset or an asset category.']);
        }

        $schedule = MaintenanceSchedule::create($data + ['is_active' => true]);
        Audit::record('maintenance_schedule_created', 'maintenance_schedules', MaintenanceSchedule::class, $schedule->id);

        return back()->with('success', 'Preventive maintenance schedule created.');
    }

    public function toggle(Request $request, MaintenanceSchedule $schedule)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $schedule->update(['is_active' => ! $schedule->is_active]);
        Audit::record('maintenance_schedule_toggled', 'maintenance_schedules', MaintenanceSchedule::class, $schedule->id, [], ['is_active' => $schedule->is_active]);

        return back()->with('success', 'Maintenance schedule status updated.');
    }

    public function generateDue(Request $request, MaintenanceScheduleService $service)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $result = $service->generateDue();
        Audit::record('maintenance_work_orders_generated', 'maintenance_schedules', MaintenanceSchedule::class, null, [], $result);

        return back()->with('success', $result['generated'].' preventive work order(s) generated; '.$result['skipped'].' schedule(s) skipped because an active work order already exists or no eligible asset was found.');
    }

    public function complete(Request $request, WorkOrder $workOrder)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $data = $request->validate([
            'cost' => ['required', 'numeric', 'min:0'],
            'downtime_minutes' => ['nullable', 'integer', 'min:0'],
            'findings' => ['required', 'string', 'max:5000'],
            'service_provider' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($workOrder, $request, $data) {
            $locked = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($locked->status, ['completed', 'cancelled'], true), 422, 'This work order is already closed.');
            $locked->update($data + ['status' => 'completed', 'completed_at' => now()]);

            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();
            $otherActiveOrders = WorkOrder::where('asset_id', $asset->id)->where('id', '!=', $locked->id)
                ->whereIn('status', ['pending', 'assigned', 'in_progress', 'waiting_parts', 'external_repair'])->exists();
            $activeLegacy = $asset->maintenanceRecords()->whereNotIn('status', ['completed', 'cancelled'])->exists();
            if (! $otherActiveOrders && ! $activeLegacy && $asset->status === 'maintenance') {
                $asset->update([
                    'status' => $asset->condition === 'Defective' ? 'defective' : 'available',
                ]);
            }

            if ($locked->maintenance_schedule_id) {
                $schedule = MaintenanceSchedule::whereKey($locked->maintenance_schedule_id)->lockForUpdate()->first();
                if ($schedule) {
                    $schedule->update(['last_done' => today(), 'next_due' => today()->addDays(max(1, (int) $schedule->interval_days))]);
                }
            }
        }, 3);

        Audit::record('preventive_work_order_completed', 'work_orders', WorkOrder::class, $workOrder->id);

        return back()->with('success', 'Work order completed and asset serviceability updated.');
    }

    public function cancel(Request $request, WorkOrder $workOrder)
    {
        abort_unless($request->user()->hasPermission('maintenance.manage'), 403);
        $data = $request->validate(['findings' => ['required', 'string', 'max:2000']]);
        DB::transaction(function () use ($workOrder, $data) {
            $locked = WorkOrder::whereKey($workOrder->id)->lockForUpdate()->firstOrFail();
            abort_unless(! in_array($locked->status, ['completed', 'cancelled'], true), 422, 'This work order is already closed.');
            $locked->update(['status' => 'cancelled', 'findings' => trim(($locked->findings ? $locked->findings."\n" : '').'Cancelled: '.$data['findings'])]);
            $asset = Asset::whereKey($locked->asset_id)->lockForUpdate()->firstOrFail();
            $otherActive = WorkOrder::where('asset_id', $asset->id)->where('id', '!=', $locked->id)->whereIn('status', ['pending', 'assigned', 'in_progress', 'waiting_parts', 'external_repair'])->exists();
            $legacyActive = $asset->maintenanceRecords()->whereNotIn('status', ['completed', 'cancelled'])->exists();
            if (! $otherActive && ! $legacyActive && $asset->status === 'maintenance') {
                $asset->update([
                    'status' => $asset->condition === 'Defective' ? 'defective' : 'available',
                ]);
            }
        }, 3);
        Audit::record('preventive_work_order_cancelled', 'work_orders', WorkOrder::class, $workOrder->id);

        return back()->with('success', 'Work order cancelled.');
    }
}
