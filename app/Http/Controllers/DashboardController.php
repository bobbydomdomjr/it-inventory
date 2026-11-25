<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\License;
use App\Models\MaintenanceRecord;
use App\Models\SoftwareLicense;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DashboardController extends Controller
{
    private function safelyLoad(string $metric, mixed $fallback, callable $callback, array &$warnings): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            Log::error('Dashboard metric could not be loaded.', [
                'metric' => $metric,
                'exception' => $exception,
            ]);
            $warnings[] = $metric;
            return $fallback;
        }
    }

    public function index()
    {
        $user = auth()->user();

        if (! $user->hasPermission('assets.view') && $user->hasPermission('portal.self')) {
            return redirect()->route('portal.index');
        }

        $warnings = [];
        $empty = collect();
        $assetQuery = fn () => Asset::query();

        $stats = [
            'assets' => $this->safelyLoad('Total IT assets', 0, fn () => Asset::count(), $warnings),
            'active_assets' => $this->safelyLoad('Total active assets', 0, fn () => Asset::whereNotIn('status', ['retired','disposed','lost'])->count(), $warnings),
            'available' => $this->safelyLoad('Available assets', 0, fn () => Asset::where('status', 'available')->count(), $warnings),
            'assigned' => $this->safelyLoad('Assigned assets', 0, fn () => Asset::where('status', 'assigned')->count(), $warnings),
            'maintenance' => $this->safelyLoad('Under maintenance assets', 0, fn () => Asset::where('status', 'maintenance')->count(), $warnings),
            'defective' => $this->safelyLoad('Defective assets', 0, fn () => Asset::where('status', 'defective')->count(), $warnings),
            'retired' => $this->safelyLoad('Retired or disposed assets', 0, fn () => Asset::whereIn('status', ['retired','disposed'])->count(), $warnings),
            'lost' => $this->safelyLoad('Lost or missing assets', 0, fn () => Asset::whereIn('status', ['lost','missing'])->count(), $warnings),
            'inventory_value' => $this->safelyLoad('Total inventory value', 0.0, fn () => (float) Asset::sum('purchase_cost'), $warnings),
            'low_stock' => $this->safelyLoad('Low stock consumables', 0, fn () => InventoryItem::whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(), $warnings),
            'software_licenses' => $this->safelyLoad('Legacy software licenses', 0, fn () => SoftwareLicense::count(), $warnings)
                + $this->safelyLoad('Normalized software licenses', 0, fn () => License::count(), $warnings),
            'expiring_licenses' => $this->safelyLoad('Legacy license expirations', 0, fn () => SoftwareLicense::whereNotNull('expires_at')->whereBetween('expires_at', [today(), today()->addDays(45)])->count(), $warnings)
                + $this->safelyLoad('Normalized license expirations', 0, fn () => License::whereNotNull('expiry_date')->whereBetween('expiry_date', [today(), today()->addDays(45)])->count(), $warnings),
            'pending_asset_requests' => $this->safelyLoad('Pending asset requests', 0, fn () => Assignment::whereIn('status', ['pending','pending_approval','requested'])->count(), $warnings),
            'pending_approvals' => $this->safelyLoad('Pending approvals', 0, fn () => Approval::where('status', 'pending')->where(function ($q) use ($user) {
                $q->where('approver_id', $user->id)->orWhereNull('approver_id');
            })->count(), $warnings),
            'overdue_maintenance' => $this->safelyLoad('Overdue maintenance activities', 0, fn () => MaintenanceRecord::whereNotNull('scheduled_at')->whereDate('scheduled_at', '<', today())->whereNotIn('status', ['completed','cancelled'])->count(), $warnings),
            'open_tickets' => $this->safelyLoad('Open support tickets', 0, fn () => SupportTicket::whereIn('status', ['open','in_progress','waiting'])->count(), $warnings),
        ];

        $statusBreakdown = $this->safelyLoad('Asset condition overview', $empty, fn () =>
            Asset::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'), $warnings);

        $recentAssets = $this->safelyLoad('Recently added assets', $empty, fn () =>
            Asset::with('category')->latest()->limit(6)->get(), $warnings);

        $recentAssignments = $this->safelyLoad('Recently assigned assets', $empty, fn () =>
            Assignment::with(['asset','employee'])->whereNull('returned_at')->latest('issued_at')->limit(6)->get(), $warnings);

        $recentReturns = $this->safelyLoad('Recently returned assets', $empty, fn () =>
            Assignment::with(['asset','employee'])->whereNotNull('returned_at')->latest('returned_at')->limit(6)->get(), $warnings);

        $departmentDistribution = $this->safelyLoad('Asset distribution by department', $empty, fn () =>
            Asset::query()->leftJoin('departments', 'assets.department_id', '=', 'departments.id')
                ->selectRaw("COALESCE(departments.name, 'Unassigned') as label, COUNT(assets.id) as total")
                ->groupBy('departments.name')->orderByDesc('total')->limit(10)->get(), $warnings);

        $categoryDistribution = $this->safelyLoad('Asset distribution by category', $empty, fn () =>
            Asset::query()->leftJoin('asset_categories', 'assets.category_id', '=', 'asset_categories.id')
                ->selectRaw("COALESCE(asset_categories.name, 'Uncategorized') as label, COUNT(assets.id) as total")
                ->groupBy('asset_categories.name')->orderByDesc('total')->limit(10)->get(), $warnings);

        $locationDistribution = $this->safelyLoad('Asset distribution by location', $empty, fn () =>
            Asset::query()->leftJoin('locations', 'assets.location_id', '=', 'locations.id')
                ->selectRaw("COALESCE(locations.name, 'Unassigned') as label, COUNT(assets.id) as total")
                ->groupBy('locations.name')->orderByDesc('total')->limit(10)->get(), $warnings);

        $monthlyAcquisitions = $this->safelyLoad('Monthly asset acquisition chart', collect(), function () {
            $start = today()->startOfMonth()->subMonths(11);
            return Asset::whereNotNull('purchase_date')->whereDate('purchase_date', '>=', $start)
                ->selectRaw("DATE_FORMAT(purchase_date, '%Y-%m') as month, COUNT(*) as total")
                ->groupBy('month')->orderBy('month')->pluck('total', 'month');
        }, $warnings);

        $maintenanceStatistics = $this->safelyLoad('Asset maintenance statistics', $empty, fn () =>
            MaintenanceRecord::select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status'), $warnings);

        $recentActivities = $this->safelyLoad('Recent system activities', $empty, function () {
            if (! Schema::hasTable('audit_logs')) return collect();
            return DB::table('audit_logs')->leftJoin('users', 'audit_logs.user_id', '=', 'users.id')
                ->select('audit_logs.action','audit_logs.module','audit_logs.created_at','users.name as user_name')
                ->orderByDesc('audit_logs.created_at')->limit(10)->get();
        }, $warnings);

        $attention = collect();
        foreach ($this->safelyLoad('Low-stock attention list', $empty, fn () =>
            InventoryItem::whereColumn('quantity_on_hand', '<=', 'reorder_level')->orderBy('quantity_on_hand')->limit(5)->get(), $warnings) as $item) {
            $attention->push(['title'=>$item->name,'meta'=>'Stock: '.$item->quantity_on_hand.' '.$item->unit,'type'=>'Low stock']);
        }

        $databaseWarnings = array_values(array_unique($warnings));

        return view('dashboard.index', compact(
            'stats','statusBreakdown','recentAssets','recentAssignments','recentReturns',
            'departmentDistribution','categoryDistribution','locationDistribution',
            'monthlyAcquisitions','maintenanceStatistics','recentActivities','attention','databaseWarnings'
        ));
    }
}
