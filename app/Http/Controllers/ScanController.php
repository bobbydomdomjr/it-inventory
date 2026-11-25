<?php
namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PhysicalAudit;
use App\Models\Location;
use App\Models\Employee;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasPermission('assets.view'),403);
        $asset=null;
        if($tag=trim((string)$request->query('tag'))){$asset=Asset::where('asset_tag',$tag)->orWhere('serial_number',$tag)->first();}
        return view('inventory.scan',compact('asset'));
    }

    public function audit(Request $request, PhysicalAudit $audit)
    {
        abort_unless($request->user()->hasPermission('audits.manage'), 403);
        abort_unless(in_array($audit->status, ['planned', 'in_progress'], true), 422, 'This audit is already closed.');

        $pendingItems = $audit->items()
            ->with('asset')
            ->where('result', 'Pending')
            ->orderBy('id')
            ->get();
        $locations = Location::query()->orderBy('name')->get();
        $employees = Employee::query()->where('status', 'active')->orderBy('last_name')->get();

        return view('inventory.audit-scan', compact('audit', 'pendingItems', 'locations', 'employees'));
    }

}
