<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OfficeController extends Controller
{
    public function index()
    {
        abort_unless(request()->user()->hasPermission('offices.view'), 403);
        $offices = Office::withCount([
            'users as users_count' => fn ($query) => $query->withoutGlobalScopes(),
            'departments as departments_count' => fn ($query) => $query->withoutGlobalScopes(),
            'assets as assets_count' => fn ($query) => $query->withoutGlobalScopes(),
        ])->orderBy('name')->paginate(20);
        return view('admin.offices', compact('offices'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('offices.manage'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', 'unique:offices,code'],
            'name' => ['required', 'string', 'max:180'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);
        $office = Office::create($data);
        Audit::record('office_created', 'offices', Office::class, $office->id, [], $office->only(['code','name','is_active']));
        return back()->with('success', 'Office created successfully.');
    }

    public function update(Request $request, Office $office)
    {
        abort_unless($request->user()->hasPermission('offices.manage'), 403);
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('offices', 'code')->ignore($office->id)],
            'name' => ['required', 'string', 'max:180'],
            'short_name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:254'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
        if (! $data['is_active']) {
            abort_if($office->users()->withoutGlobalScopes()->where('is_active', true)->exists(), 422, 'Reassign or deactivate active users before deactivating this office.');
        }
        $before = $office->only(['code','name','short_name','email','phone','address','is_active']);
        $office->update($data);
        Audit::record('office_updated', 'offices', Office::class, $office->id, $before, $office->only(array_keys($before)));
        return back()->with('success', 'Office updated successfully.');
    }

    public function destroy(Request $request, Office $office)
    {
        abort_unless($request->user()->hasPermission('offices.manage'), 403);
        $ownedTables = [
            'users', 'departments', 'locations', 'asset_categories', 'suppliers', 'employees', 'assets',
            'inventory_items', 'software_licenses', 'licenses', 'assignments', 'asset_transfers', 'stock_transactions',
            'maintenance_records', 'support_tickets', 'procurements', 'work_orders', 'documents',
            'receiving_reports', 'supplier_evaluations', 'asset_disposals', 'purchase_requests',
            'purchase_orders', 'quotations', 'purchase_request_items', 'receiving_report_items',
            'stock_counts', 'stock_count_items', 'maintenance_schedules', 'inventory_audit_items',
            'license_renewals', 'license_assignments', 'software_license_assignments', 'ticket_comments',
        ];
        foreach ($ownedTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'office_id')) {
                abort_if(DB::table($table)->where('office_id', $office->id)->exists(), 422, 'This office still owns records. Reassign those records before archiving the office.');
            }
        }
        $office->delete();
        Audit::record('office_archived', 'offices', Office::class, $office->id);
        return back()->with('success', 'Office archived.');
    }
}
