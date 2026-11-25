<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Employee;
use App\Models\License;
use App\Models\LicenseAssignment;
use App\Models\LicenseRenewal;
use App\Models\Software;
use App\Models\Supplier;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LicenseCatalogController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('software.view'), 403);

        $software = Software::query()
            ->with([
                'category',
                'licenses.supplier',
                'licenses.renewals.renewedBy',
                'licenses.assignments.assignable',
            ])
            ->withCount('licenses')
            ->orderBy('name')
            ->get();
        $categories = AssetCategory::where('type', 'software')->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::orderBy('company_name')->get();
        $employees = Employee::where('status', 'active')->orderBy('last_name')->orderBy('first_name')->get();
        $assets = Asset::orderBy('asset_tag')->get();

        return view('software-catalog.index', compact('software', 'categories', 'suppliers', 'employees', 'assets'));
    }

    public function storeSoftware(Request $request)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'version' => 'nullable|string|max:80',
            'publisher' => 'nullable|string|max:150',
            'category_id' => ['nullable', 'integer', Rule::exists('asset_categories', 'id')->where('type', 'software')->where('is_active', true)],
            'description' => 'nullable|string|max:5000',
        ]);
        if (! empty($data['category_id'])) AssetCategory::query()->where('type', 'software')->where('is_active', true)->findOrFail($data['category_id']);
        $software = Software::create($data);
        Audit::record('software_catalog_created', 'software', Software::class, $software->id, [], $data);

        return back()->with('success', 'Software product registered.');
    }

    public function updateSoftware(Request $request, Software $software)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'name' => 'required|string|max:190',
            'version' => 'nullable|string|max:80',
            'publisher' => 'nullable|string|max:150',
            'category_id' => ['nullable', 'integer', Rule::exists('asset_categories', 'id')->where('type', 'software')],
            'description' => 'nullable|string|max:5000',
        ]);
        if (! empty($data['category_id'])) {
            $categoryQuery = AssetCategory::query()->where('type', 'software');
            if ((int) $data['category_id'] !== (int) $software->category_id) $categoryQuery->where('is_active', true);
            $categoryQuery->findOrFail($data['category_id']);
        }
        $before = $software->only(array_keys($data));
        $software->update($data);
        Audit::record('software_catalog_updated', 'software', Software::class, $software->id, $before, $data);

        return back()->with('success', 'Software product updated.');
    }

    public function storeLicense(Request $request, Software $software)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'license_key' => 'nullable|string|max:5000',
            'type' => 'required|in:perpetual,subscription,open_source,trial,volume',
            'seats_total' => 'required|integer|min:1|max:1000000',
            'purchase_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:purchase_date',
            'cost' => 'required|numeric|min:0|max:999999999.99',
            'subscription_cycle' => 'nullable|in:monthly,quarterly,annually,biennial,one_time,other',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);
        $license = $software->licenses()->create($data);
        Audit::record('software_license_created', 'software', License::class, $license->id, [], [
            'software_id' => $software->id,
            'type' => $data['type'],
            'seats_total' => $data['seats_total'],
            'expiry_date' => $data['expiry_date'] ?? null,
            'cost' => $data['cost'],
        ]);

        return back()->with('success', 'Software license entitlement registered.');
    }

    public function renew(Request $request, License $license)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'renewed_on' => 'required|date',
            'new_expiry' => 'required|date|after:renewed_on',
            'cost' => 'required|numeric|min:0|max:999999999.99',
            'remarks' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($license, $data, $request): void {
            $locked = License::whereKey($license->id)->lockForUpdate()->firstOrFail();
            LicenseRenewal::create($data + [
                'license_id' => $locked->id,
                'renewed_by' => $request->user()->id,
            ]);
            $locked->update([
                'expiry_date' => $data['new_expiry'],
                'purchase_date' => $locked->purchase_date ?: $data['renewed_on'],
                'cost' => $data['cost'],
            ]);
        });
        Audit::record('software_license_renewed', 'software', License::class, $license->id, [], [
            'renewed_on' => $data['renewed_on'],
            'new_expiry' => $data['new_expiry'],
            'cost' => $data['cost'],
        ]);

        return back()->with('success', 'License renewal recorded and the entitlement expiry was updated.');
    }

    public function assign(Request $request, License $license)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        $data = $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'asset_id' => 'nullable|exists:assets,id',
            'assigned_at' => 'required|date',
        ]);
        if (empty($data['employee_id']) === empty($data['asset_id'])) {
            throw ValidationException::withMessages(['employee_id' => 'Select exactly one employee or IT asset.']);
        }

        DB::transaction(function () use ($license, $data, $request): void {
            $locked = License::whereKey($license->id)->lockForUpdate()->firstOrFail();
            $assigned = LicenseAssignment::where('license_id', $locked->id)->whereNull('revoked_at')->count();
            abort_if($assigned >= (int) $locked->seats_total, 422, 'All purchased license seats are currently allocated.');

            $type = ! empty($data['employee_id']) ? Employee::class : Asset::class;
            $assignableId = $data['employee_id'] ?? $data['asset_id'];
            if ($type === Employee::class) {
                Employee::whereKey($assignableId)->where('status', 'active')->firstOrFail();
            } else {
                Asset::whereKey($assignableId)->firstOrFail();
            }
            abort_if(
                LicenseAssignment::where('license_id', $locked->id)
                    ->where('assignable_type', $type)
                    ->where('assignable_id', $assignableId)
                    ->whereNull('revoked_at')->exists(),
                422,
                'This software entitlement is already assigned to that employee or asset.'
            );
            LicenseAssignment::create([
                'license_id' => $locked->id,
                'assignable_type' => $type,
                'assignable_id' => $assignableId,
                'assigned_at' => $data['assigned_at'],
                'assigned_by' => $request->user()->id,
            ]);
        });
        Audit::record('normalized_license_assigned', 'software', License::class, $license->id, [], [
            'employee_id' => $data['employee_id'] ?? null,
            'asset_id' => $data['asset_id'] ?? null,
        ]);

        return back()->with('success', 'Software entitlement assigned.');
    }

    public function revoke(Request $request, LicenseAssignment $assignment)
    {
        abort_unless($request->user()->hasPermission('software.manage'), 403);
        DB::transaction(function () use ($assignment): void {
            $locked = LicenseAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->revoked_at !== null, 422, 'This license allocation has already been revoked.');
            $locked->update(['revoked_at' => now()]);
        });
        Audit::record('normalized_license_revoked', 'software', LicenseAssignment::class, $assignment->id);

        return back()->with('success', 'License allocation revoked and the seat returned to the available pool.');
    }
}
