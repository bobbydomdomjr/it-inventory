<?php
namespace App\Http\Controllers;

use App\Models\{Asset,AssetCategory,InventoryItem,SoftwareLicense,SupportTicket,MaintenanceRecord,AuditLog,Assignment,AssetTransfer,Procurement,PhysicalAudit,AssetDisposal,Employee,Department,Location,Supplier,SystemSetting,License,PurchaseRequest};
use App\Exports\ArrayReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Neutralize spreadsheet formula prefixes in user-controlled report values.
     * Numeric values remain numeric; only text cells are prefixed.
     */
    private static function safeSpreadsheetValue(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        return preg_match('/^[\x00-\x20]*[=+\-@]/', $value) === 1
            ? "'" . $value
            : $value;
    }

    /** @param resource $stream */
    private static function writeCsvRow($stream, array $values): void
    {
        fputcsv($stream, array_map([self::class, 'safeSpreadsheetValue'], $values));
    }

    private function configuredExportLimit(): int
    {
        $configured = (int) data_get(SystemSetting::where('key', 'export_row_limit')->first()?->value, 'value', 50000);

        return max(100, min(50000, $configured));
    }

    public function index()
    {
        abort_unless(request()->user()->hasPermission('reports.view'),403);
        $summary = [
            'asset_count' => Asset::count(),
            'asset_value' => (float) Asset::sum('purchase_cost'),
            'assigned' => Asset::where('status', 'assigned')->count(),
            'available' => Asset::where('status', 'available')->count(),
            'maintenance' => Asset::where('status', 'maintenance')->count(),
            'defective' => Asset::where('status', 'defective')->count(),
            'retired' => Asset::where('status', 'retired')->count(),
            'low_stock' => InventoryItem::whereColumn('quantity_on_hand', '<=', 'reorder_level')->count(),
            'open_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress', 'waiting'])->count(),
            'overdue_tickets' => SupportTicket::whereIn('status', ['open', 'in_progress', 'waiting'])
                ->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->count(),
            // Count both generations of the license register while legacy records are still supported.
            'software_licenses' => SoftwareLicense::count() + License::count(),
            'expiring_licenses' => SoftwareLicense::whereNotNull('expires_at')
                ->whereBetween('expires_at', [today(), today()->addDays(60)])->count()
                + License::whereNotNull('expiry_date')
                    ->whereBetween('expiry_date', [today(), today()->addDays(60)])->count(),
        ];
        $defaultExportFormat = SystemSetting::where('key', 'default_export_format')->first();
        $defaultExportFormat = data_get($defaultExportFormat?->value, 'value', 'xlsx');
        return view('reports.index', compact('summary', 'defaultExportFormat'));
    }

    public function assetsCsv(): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'),403);
        $rows=Asset::with(['category','department','location','currentEmployee','supplier'])->orderBy('asset_tag')->limit($this->configuredExportLimit())->cursor();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Asset Tag','Asset Name','Serial Number','Category','Brand','Model','Purchase Date','Purchase Cost','Salvage Value','Acquisition Method','Useful Life Months','Depreciation Method','Supplier','Department','Location','Custodian','Condition','Status','Warranty Expiration']);foreach($rows as $a)self::writeCsvRow($out,[$a->asset_tag,$a->name,$a->serial_number,$a->category?->name,$a->brand,$a->model,$a->purchase_date?->format('Y-m-d'),$a->purchase_cost,$a->salvage_value,$a->acquisition_method,$a->useful_life_months,$a->depreciation_method,$a->supplier?->company_name,$a->department?->name,$a->location?->name,$a->currentEmployee?->full_name,$a->condition,$a->status,$a->warranty_expiration?->format('Y-m-d')]);fclose($out);},'it-assets-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }

    public function stockCsv(): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'),403);
        $rows=InventoryItem::with('supplier')->orderBy('name')->limit($this->configuredExportLimit())->cursor();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['SKU','Item','Unit','Quantity On Hand','Reorder Level','Unit Cost','Stock Value','Supplier','Stock Status']);foreach($rows as $r)self::writeCsvRow($out,[$r->sku,$r->name,$r->unit,$r->quantity_on_hand,$r->reorder_level,$r->unit_cost,$r->quantity_on_hand*$r->unit_cost,$r->supplier?->company_name,$r->quantity_on_hand<=$r->reorder_level?'Low stock':'In stock']);fclose($out);},'inventory-stock-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }

    public function depreciationCsv(): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'), 403);

        return response()->streamDownload(function (): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Asset Tag', 'Asset', 'Purchase Date', 'Original Cost', 'Salvage Value',
                'Method', 'Useful Life Months', 'Posted Accumulated Depreciation',
                'Latest Book Value', 'Posted Through', 'Posting Status',
            ]);

            $limit = $this->configuredExportLimit();
            $processed = 0;
            Asset::query()
                ->with(['category', 'depreciationEntries'])
                ->whereNotNull('purchase_date')
                ->orderBy('id')
                ->chunkById(min(500, $limit), function ($assets) use ($out, $limit, &$processed): bool {
                    foreach ($assets as $asset) {
                        if ($processed >= $limit) {
                            return false;
                        }
                        $entries = $asset->depreciationEntries->sortBy('period');
                        $latest = $entries->last();
                        $life = $asset->useful_life_months
                            ?: $asset->category?->useful_life_months
                            ?: $asset->category?->default_useful_life_months;

                        self::writeCsvRow($out, [
                            $asset->asset_tag,
                            $asset->name,
                            $asset->purchase_date?->format('Y-m-d'),
                            $asset->purchase_cost,
                            $asset->salvage_value,
                            $asset->depreciation_method ?: $asset->category?->depreciation_method,
                            $life,
                            number_format((float) $entries->sum('amount'), 2, '.', ''),
                            $latest?->book_value,
                            $latest?->period?->format('Y-m-d'),
                            $latest ? 'Posted' : 'Not posted',
                        ]);
                        $processed++;
                    }

                    return $processed < $limit;
                });

            fclose($out);
        }, 'asset-depreciation-ledger-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function maintenanceCsv(): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'),403);
        $rows=MaintenanceRecord::with('asset')->orderByDesc('created_at')->limit($this->configuredExportLimit())->cursor();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Work Order','Asset Tag','Type','Problem','Priority','Technician','Service Provider','Scheduled','Completed','Next Maintenance','Cost','Status','Resolution']);foreach($rows as $r)self::writeCsvRow($out,[$r->work_order_no,$r->asset?->asset_tag,$r->maintenance_type,$r->problem,$r->priority,$r->assigned_technician,$r->service_provider,$r->scheduled_at?->format('Y-m-d'),$r->completed_at?->format('Y-m-d'),$r->next_maintenance_at?->format('Y-m-d'),$r->cost,$r->status,$r->resolution]);fclose($out);},'maintenance-report-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }

    public function ticketsCsv(): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'),403);
        $rows=SupportTicket::with('asset')->orderByDesc('created_at')->limit($this->configuredExportLimit())->cursor();
        return response()->streamDownload(function()use($rows){$out=fopen('php://output','w');fputcsv($out,['Ticket No','Subject','Requester','Requester Email','Asset Tag','Category','Priority','Assigned To','Created','SLA Due','First Response','Resolved','Status','Resolution']);foreach($rows as $r)self::writeCsvRow($out,[$r->ticket_no,$r->subject,$r->requester_name,$r->requester_email,$r->asset?->asset_tag,$r->category,$r->priority,$r->assigned_to,$r->created_at?->format('Y-m-d H:i:s'),$r->sla_due_at?->format('Y-m-d H:i:s'),$r->first_response_at?->format('Y-m-d H:i:s'),$r->resolved_at?->format('Y-m-d H:i:s'),$r->status,$r->resolution]);fclose($out);},'helpdesk-report-'.now()->format('Ymd-His').'.csv',['Content-Type'=>'text/csv']);
    }

    public function registerCsv(string $resource): StreamedResponse
    {
        abort_unless(request()->user()->hasPermission('reports.export'),403);
        $definitions = [
            'assignments' => ['assignments.csv', ['Reference','Asset Tag','Asset','Employee No','Employee','Department','Location','Assignment Type','Issued','Due','Returned','Approved By','Approved At','Status','Overdue'], Assignment::class],
            'transfers' => ['asset-transfers.csv', ['Reference','Asset Tag','From Employee','To Employee','Reason','Requested','Approved','Status'], AssetTransfer::class],
            'procurements' => ['procurement-register.csv', ['Request No','Item / Purpose','Requested By','Supplier','Purchase Order','Quantity','Accepted Quantity','Rejected Quantity','Estimated Cost','Requested','Expected Delivery','Received','Inspection','Status'], Procurement::class],
            'audits' => ['physical-inventory-audits.csv', ['Audit No','Title','Department','Location','Scheduled','Status','Verified Count','Findings Count'], PhysicalAudit::class],
            'disposals' => ['asset-disposals.csv', ['Disposal No','Asset Tag','Method','Justification','Technical Condition','Salvage Value','Requested','Disposed','Status'], AssetDisposal::class],
            'software' => ['software-licenses.csv', ['Software','Publisher','Version','License Type','Purchased','Assigned','Available','Purchase Cost','Expires'], SoftwareLicense::class],
            'licenses' => ['normalized-license-entitlements.csv', ['Software','Publisher','Version','License Type','Total Seats','Assigned Seats','Available Seats','Supplier','Purchase Date','Expiry Date','Cost','Renewal Count','Latest Renewal'], License::class],
            'purchase_requests' => ['purchase-requests.csv', ['PR No','Purpose','Requester','Department','Estimated Total','Status','Item Count','Selected Supplier','PO No','Ordered At'], PurchaseRequest::class],
            'employees' => ['employees.csv', ['Employee No','First Name','Last Name','Email','Phone','Position','Department','Status'], Employee::class],
            'categories' => ['asset-categories.csv', ['Code','Category','Type','Parent Category','Depreciation Method','Useful Life Months','Salvage Percent','Active'], AssetCategory::class],
            'departments' => ['departments.csv', ['Code','Department','Parent Department','Department Head','Description'], Department::class],
            'locations' => ['locations.csv', ['Code','Location','Type','Parent Location','Building','Floor','Room','Description'], Location::class],
            'suppliers' => ['suppliers.csv', ['Supplier','Contact Person','Email','Phone','Address','TIN','Accreditation Status'], Supplier::class],
        ];
        abort_unless(isset($definitions[$resource]),404);
        [$filename,$headers,$class] = $definitions[$resource];
        $filename = pathinfo($filename, PATHINFO_FILENAME).'-'.now()->format('Ymd-His').'.csv';
        $limit = $this->configuredExportLimit();

        return response()->streamDownload(function () use ($resource,$headers,$class,$limit) {
            $out = fopen('php://output','w');
            fputcsv($out,$headers);
            $query = $class::query();
            $relations = match ($resource) {
                'assignments' => ['asset','employee.department','department','location','approver'],
                'transfers' => ['asset','fromEmployee','toEmployee'],
                'audits' => ['department','location'],
                'disposals' => ['asset'],
                'employees' => ['department'],
                'departments' => ['parent','headEmployee'],
                'locations' => ['parent'],
                'categories' => ['parent'],
                'licenses' => ['software','supplier','renewals'],
                'purchase_requests' => ['requester','department','items','quotations.supplier','purchaseOrder'],
                default => [],
            };
            if ($relations) $query->with($relations);
            $processed = 0;
            $query->orderBy('id')->chunkById(min(500, $limit),function ($records) use ($out,$resource,$limit,&$processed): bool {
                foreach ($records as $r) {
                    if ($processed >= $limit) {
                        return false;
                    }
                    $row = match ($resource) {
                        'assignments' => [$r->reference_no,$r->asset?->asset_tag,$r->asset?->name,$r->employee?->employee_no,$r->employee?->full_name,$r->department?->name ?? $r->employee?->department?->name,$r->location?->name ?? $r->asset?->location?->name,$r->assignment_type,$r->issued_at?->format('Y-m-d'),$r->due_date?->format('Y-m-d'),$r->returned_at?->format('Y-m-d'),$r->approver?->name,$r->approved_at?->format('Y-m-d H:i:s'),$r->status,$r->status==='issued' && $r->due_date && $r->due_date->lt(today()) ? 'Yes' : 'No'],
                        'transfers' => [$r->reference_no,$r->asset?->asset_tag,$r->fromEmployee?->full_name,$r->toEmployee?->full_name,$r->reason,$r->created_at?->format('Y-m-d H:i:s'),$r->approved_at?->format('Y-m-d H:i:s'),$r->status],
                        'procurements' => [$r->request_no,$r->item_description,$r->requested_by,$r->supplier_name,$r->purchase_order_no,$r->quantity,$r->quantity_received,$r->quantity_rejected,$r->total_cost,$r->requested_at?->format('Y-m-d'),$r->expected_delivery_at?->format('Y-m-d'),$r->received_at?->format('Y-m-d'),$r->inspection_result,$r->status],
                        'audits' => [$r->audit_no,$r->title,$r->department?->name,$r->location?->name,$r->scheduled_at?->format('Y-m-d'),$r->status,$r->verified_count,count($r->findings ?? [])],
                        'disposals' => [$r->disposal_no,$r->asset?->asset_tag,$r->proposed_method,$r->justification,$r->technical_condition,$r->estimated_salvage_value,$r->requested_at?->format('Y-m-d'),$r->disposed_at?->format('Y-m-d'),$r->status],
                        'software' => [$r->software_name,$r->publisher,$r->version,$r->license_type,$r->quantity_purchased,$r->quantity_assigned,max(0,$r->quantity_purchased-$r->quantity_assigned),$r->purchase_cost,$r->expires_at?->format('Y-m-d')],
                        'licenses' => [$r->software?->name,$r->software?->publisher,$r->software?->version,$r->type,$r->seats_total,$r->seats_assigned,$r->seats_available,$r->supplier?->company_name,$r->purchase_date?->format('Y-m-d'),$r->expiry_date?->format('Y-m-d'),$r->cost,$r->renewals->count(),$r->renewals->sortByDesc('renewed_on')->first()?->new_expiry?->format('Y-m-d')],
                        'purchase_requests' => [$r->pr_no,$r->purpose,$r->requester?->name,$r->department?->name,$r->total_estimated,$r->status,$r->items->count(),$r->quotations->firstWhere('is_selected',true)?->supplier?->company_name,$r->purchaseOrder?->po_no,$r->purchaseOrder?->ordered_at?->format('Y-m-d')],
                        'employees' => [$r->employee_no,$r->first_name,$r->last_name,$r->email,$r->phone,$r->position,$r->department?->name,$r->status],
                        'categories' => [$r->code,$r->name,$r->type,$r->parent?->name,$r->depreciation_method,$r->useful_life_months ?: $r->default_useful_life_months,$r->salvage_percent,$r->is_active ? 'Yes' : 'No'],
                        'departments' => [$r->code,$r->name,$r->parent?->name,$r->headEmployee?->full_name ?: $r->head_name,$r->description],
                        'locations' => [$r->code,$r->name,$r->type,$r->parent?->name,$r->building,$r->floor,$r->room,$r->description],
                        'suppliers' => [$r->company_name,$r->contact_person,$r->email,$r->phone,$r->address,$r->tin,$r->accreditation_status],
                    };
                    self::writeCsvRow($out, $row);
                    $processed++;
                }

                return $processed < $limit;
            });
            fclose($out);
        },$filename,['Content-Type'=>'text/csv; charset=UTF-8']);
    }

    /**
     * Download a bounded PDF or XLSX report using the same authoritative
     * model records used by the operational modules. PDF output is capped
     * lower because Dompdf renders the document in memory.
     */
    public function export(Request $request, string $resource, string $format)
    {
        abort_unless($request->user()->hasPermission('reports.export'), 403);
        abort_unless(in_array($format, ['pdf', 'xlsx'], true), 404);

        $configuredLimit = (int) data_get(SystemSetting::where('key', 'export_row_limit')->first()?->value, 'value', 50000);
        $configuredLimit = max(100, min(50000, $configuredLimit));
        $limit = $format === 'pdf' ? min(1500, $configuredLimit) : $configuredLimit;
        [$title, $headings, $rows] = $this->reportData($resource, $limit);
        $timestamp = now()->format('Ymd-His');
        $baseName = Str::slug($title).'-'.$timestamp;
        $settings = SystemSetting::whereIn('key', ['organization_name', 'organization_address'])->get()->keyBy('key');
        $organizationName = data_get($settings->get('organization_name')?->value, 'value', config('app.name'));
        $organizationAddress = data_get($settings->get('organization_address')?->value, 'value', '');

        if ($format === 'xlsx') {
            abort_unless(
                class_exists(Excel::class) && class_exists(\XMLWriter::class) && class_exists(\ZipArchive::class),
                503,
                'Excel export requires the locked report dependencies and PHP XMLWriter and ZIP extensions.'
            );
            $rows = array_map(
                static fn (array $row): array => array_map([self::class, 'safeSpreadsheetValue'], $row),
                $rows
            );
            return Excel::download(new ArrayReportExport($headings, $rows), $baseName.'.xlsx');
        }

        abort_unless(class_exists(Pdf::class), 503, 'PDF export is unavailable. Run composer install to install the locked report dependencies.');
        return Pdf::loadView('reports.pdf', [
            'title' => $title,
            'headings' => $headings,
            'rows' => $rows,
            'generatedAt' => now(),
            'organizationName' => $organizationName,
            'organizationAddress' => $organizationAddress,
            'truncated' => count($rows) >= $limit,
            'limit' => $limit,
        ])->setPaper('a4', 'landscape')->download($baseName.'.pdf');
    }

    /** @return array{0:string,1:array<int,string>,2:array<int,array<int,mixed>>} */
    private function reportData(string $resource, int $limit): array
    {
        $date = static fn ($value) => $value?->format('Y-m-d') ?? '';
        $money = static fn ($value) => number_format((float) $value, 2, '.', '');

        return match ($resource) {
            'assets' => (function () use ($limit, $date, $money) {
                $items = Asset::with(['category','department','location','currentEmployee','supplier'])->orderBy('asset_tag')->limit($limit)->get();
                return ['IT Asset Register', ['Asset Tag','Asset Name','Serial Number','Category','Brand','Model','Purchase Date','Purchase Cost','Salvage Value','Acquisition Method','Useful Life Months','Depreciation Method','Supplier','Department','Location','Custodian','Condition','Status','Warranty Expiration'], $items->map(fn ($a) => [$a->asset_tag,$a->name,$a->serial_number,$a->category?->name,$a->brand,$a->model,$date($a->purchase_date),$money($a->purchase_cost),$money($a->salvage_value),$a->acquisition_method,$a->useful_life_months,$a->depreciation_method ?: $a->category?->depreciation_method,$a->supplier?->company_name,$a->department?->name,$a->location?->name,$a->currentEmployee?->full_name,$a->condition,$a->status,$date($a->warranty_expiration)])->all()];
            })(),
            'stock' => (function () use ($limit, $money) {
                $items = InventoryItem::with(['supplier','location'])->orderBy('name')->limit($limit)->get();
                return ['Stock and Consumables Register', ['SKU','Item','Unit','On Hand','Reorder Level','Unit Cost','Stock Value','Supplier','Location','Status'], $items->map(fn ($r) => [$r->sku,$r->name,$r->unit,$r->quantity_on_hand,$r->reorder_level,$money($r->unit_cost),$money($r->quantity_on_hand * $r->unit_cost),$r->supplier?->company_name,$r->location?->name,((int) $r->quantity_on_hand <= (int) $r->reorder_level ? 'Low stock' : 'In stock')])->all()];
            })(),
            'maintenance' => (function () use ($limit, $date, $money) {
                $items = MaintenanceRecord::with('asset')->latest()->limit($limit)->get();
                return ['Maintenance and Repair Register', ['Work Order','Asset Tag','Type','Problem','Priority','Technician','Service Provider','Scheduled','Completed','Next Maintenance','Cost','Status','Resolution'], $items->map(fn ($r) => [$r->work_order_no,$r->asset?->asset_tag,$r->maintenance_type,$r->problem,$r->priority,$r->assigned_technician,$r->service_provider,$date($r->scheduled_at),$date($r->completed_at),$date($r->next_maintenance_at),$money($r->cost),$r->status,$r->resolution])->all()];
            })(),
            'tickets' => (function () use ($limit, $date) {
                $items = SupportTicket::with('asset')->latest()->limit($limit)->get();
                return ['IT Helpdesk Ticket Register', ['Ticket No','Subject','Requester','Requester Email','Asset Tag','Category','Priority','Assigned To','Created','SLA Due','First Response','Resolved','Status','Resolution'], $items->map(fn ($r) => [$r->ticket_no,$r->subject,$r->requester_name,$r->requester_email,$r->asset?->asset_tag,$r->category,$r->priority,$r->assigned_to,$r->created_at?->format('Y-m-d H:i:s'),$r->sla_due_at?->format('Y-m-d H:i:s'),$r->first_response_at?->format('Y-m-d H:i:s'),$r->resolved_at?->format('Y-m-d H:i:s'),$r->status,$r->resolution])->all()];
            })(),
            'employees' => (function () use ($limit) {
                $items = Employee::with('department')->orderBy('employee_no')->limit($limit)->get();
                return ['Employee Register', ['Employee No','Full Name','Position / Rank','Department','Email','Phone','Status'], $items->map(fn ($r) => [$r->employee_no,$r->full_name,$r->position,$r->department?->name,$r->email,$r->phone,$r->status])->all()];
            })(),
            'departments' => (function () use ($limit) {
                $items = Department::with(['parent','headEmployee'])->orderBy('code')->limit($limit)->get();
                return ['Department Register', ['Code','Department','Parent Department','Department Head','Description'], $items->map(fn ($r) => [$r->code,$r->name,$r->parent?->name,$r->headEmployee?->full_name ?: $r->head_name,$r->description])->all()];
            })(),
            'locations' => (function () use ($limit) {
                $items = Location::with('parent')->orderBy('code')->limit($limit)->get();
                return ['Location Register', ['Code','Location','Type','Parent Location','Building','Floor','Room','Description'], $items->map(fn ($r) => [$r->code,$r->name,$r->type,$r->parent?->name,$r->building,$r->floor,$r->room,$r->description])->all()];
            })(),
            'categories' => (function () use ($limit, $money) {
                $items = AssetCategory::with('parent')->orderBy('code')->limit($limit)->get();
                return ['Asset Category Register', ['Code','Category','Type','Parent Category','Depreciation Method','Useful Life (Months)','Salvage %','Active'], $items->map(fn ($r) => [$r->code,$r->name,$r->type,$r->parent?->name,$r->depreciation_method,$r->useful_life_months ?: $r->default_useful_life_months,$r->salvage_percent,$r->is_active ? 'Yes' : 'No'])->all()];
            })(),
            'suppliers' => (function () use ($limit) {
                $items = Supplier::orderBy('company_name')->limit($limit)->get();
                return ['Supplier Register', ['Supplier','Contact Person','Email','Phone','Address','TIN','Accreditation'], $items->map(fn ($r) => [$r->company_name,$r->contact_person,$r->email,$r->phone,$r->address,$r->tin,$r->accreditation_status])->all()];
            })(),
            'assignments' => (function () use ($limit, $date) {
                $items = Assignment::with(['asset','employee.department','department','location','approver'])->latest()->limit($limit)->get();
                return ['Asset Assignment Register', ['Reference','Asset Tag','Asset','Employee No','Employee','Department','Location','Assignment Type','Issued','Due','Returned','Approved By','Approved At','Status','Overdue'], $items->map(fn ($r) => [$r->reference_no,$r->asset?->asset_tag,$r->asset?->name,$r->employee?->employee_no,$r->employee?->full_name,$r->department?->name ?? $r->employee?->department?->name,$r->location?->name ?? $r->asset?->location?->name,$r->assignment_type,$date($r->issued_at),$date($r->due_date),$date($r->returned_at),$r->approver?->name,$r->approved_at?->format('Y-m-d H:i:s'),$r->status,$r->status==='issued' && $r->due_date && $r->due_date->lt(today()) ? 'Yes' : 'No'])->all()];
            })(),
            'transfers' => (function () use ($limit) {
                $items = AssetTransfer::with(['asset','fromEmployee','toEmployee'])->latest()->limit($limit)->get();
                return ['Asset Transfer and Turnover Register', ['Reference','Asset Tag','From Employee','To Employee','Reason','Requested','Approved','Status'], $items->map(fn ($r) => [$r->reference_no,$r->asset?->asset_tag,$r->fromEmployee?->full_name,$r->toEmployee?->full_name,$r->reason,$r->created_at?->format('Y-m-d H:i:s'),$r->approved_at?->format('Y-m-d H:i:s'),$r->status])->all()];
            })(),
            'procurements' => (function () use ($limit, $date, $money) {
                $items = Procurement::latest()->limit($limit)->get();
                return ['Procurement Register', ['Request No','Item / Purpose','Requested By','Supplier','PO No','Quantity','Received','Rejected','Estimated Cost','Requested','Expected Delivery','Received Date','Status'], $items->map(fn ($r) => [$r->request_no,$r->item_description,$r->requested_by,$r->supplier_name,$r->purchase_order_no,$r->quantity,$r->quantity_received,$r->quantity_rejected,$money($r->total_cost),$date($r->requested_at),$date($r->expected_delivery_at),$date($r->received_at),$r->status])->all()];
            })(),
            'audits' => (function () use ($limit, $date) {
                $items = PhysicalAudit::with(['department','location'])->withCount('items')->latest()->limit($limit)->get();
                return ['Physical Inventory Audit Register', ['Audit No','Title','Scope','Department','Location','Scheduled','Status','Verified','Checklist Items'], $items->map(fn ($r) => [$r->audit_no,$r->title,$r->scope,$r->department?->name,$r->location?->name,$date($r->scheduled_at),$r->status,$r->verified_count,$r->items_count])->all()];
            })(),
            'disposals' => (function () use ($limit, $date, $money) {
                $items = AssetDisposal::with('asset')->latest()->limit($limit)->get();
                return ['Asset Disposal Register', ['Disposal No','Asset Tag','Method','Justification','Technical Condition','Salvage Value','Requested','Disposed','Status'], $items->map(fn ($r) => [$r->disposal_no,$r->asset?->asset_tag,$r->proposed_method,$r->justification,$r->technical_condition,$money($r->estimated_salvage_value),$date($r->requested_at),$date($r->disposed_at),$r->status])->all()];
            })(),
            'software' => (function () use ($limit, $date, $money) {
                $items = SoftwareLicense::latest()->limit($limit)->get();
                return ['Software License Register', ['Software','Publisher','Version','License Type','Purchased Seats','Assigned Seats','Available Seats','Purchase Cost','Expires'], $items->map(fn ($r) => [$r->software_name,$r->publisher,$r->version,$r->license_type,$r->quantity_purchased,$r->quantity_assigned,max(0,$r->quantity_purchased-$r->quantity_assigned),$money($r->purchase_cost),$date($r->expires_at)])->all()];
            })(),
            'licenses' => (function () use ($limit, $date, $money) {
                $items = License::with(['software','supplier','renewals'])->latest()->limit($limit)->get();
                return ['Normalized Software License Entitlements', ['Software','Publisher','Version','License Type','Total Seats','Assigned Seats','Available Seats','Supplier','Purchase Date','Expiry Date','Cost','Renewal Count','Latest Renewal'], $items->map(fn ($r) => [$r->software?->name,$r->software?->publisher,$r->software?->version,$r->type,$r->seats_total,$r->seats_assigned,$r->seats_available,$r->supplier?->company_name,$date($r->purchase_date),$date($r->expiry_date),$money($r->cost),$r->renewals->count(),$date($r->renewals->sortByDesc('renewed_on')->first()?->new_expiry)])->all()];
            })(),
            'purchase_requests' => (function () use ($limit, $money) {
                $items = PurchaseRequest::with(['requester','department','items','quotations.supplier','purchaseOrder'])->latest()->limit($limit)->get();
                return ['Purchase Request Register', ['PR No','Purpose','Requester','Department','Estimated Total','Status','Item Count','Selected Supplier','PO No','Ordered At'], $items->map(fn ($r) => [$r->pr_no,$r->purpose,$r->requester?->name,$r->department?->name,$money($r->total_estimated),$r->status,$r->items->count(),$r->quotations->firstWhere('is_selected',true)?->supplier?->company_name,$r->purchaseOrder?->po_no,$r->purchaseOrder?->ordered_at?->format('Y-m-d')])->all()];
            })(),
            'depreciation' => (function () use ($limit, $date, $money) {
                $items = Asset::with(['category', 'depreciationEntries'])->whereNotNull('purchase_date')->orderBy('asset_tag')->limit($limit)->get();
                $rows = $items->map(function ($asset) use ($date, $money) {
                    $entries = $asset->depreciationEntries->sortBy('period');
                    $latest = $entries->last();
                    $life = $asset->useful_life_months ?: $asset->category?->useful_life_months ?: $asset->category?->default_useful_life_months;
                    return [$asset->asset_tag, $asset->name, $date($asset->purchase_date), $money($asset->purchase_cost), $money($asset->salvage_value), $asset->depreciation_method ?: $asset->category?->depreciation_method, $life, $money($entries->sum('amount')), $latest?->book_value ?? $money($asset->purchase_cost), $date($latest?->period), $latest ? 'Posted' : 'Not posted'];
                })->all();
                return ['Asset Valuation and Depreciation Register', ['Asset Tag','Asset','Purchase Date','Original Cost','Salvage Value','Method','Useful Life Months','Accumulated Depreciation','Latest Book Value','Posted Through','Posting Status'], $rows];
            })(),
            default => abort(404, 'The requested report is not available.'),
        };
    }

    public function activity()
    {
        abort_unless(request()->user()->hasPermission('audit.view'),403);
        $logs=AuditLog::with('user')->latest('created_at')->paginate(30);
        return view('admin.activity',compact('logs'));
    }
}
