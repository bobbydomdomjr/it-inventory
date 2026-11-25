@extends('layouts.app')
@section('title',$d['singular'].' details') @section('breadcrumb',$d['title'])
@section('content')
<div class="page-heading"><div><div class="eyebrow"><a href="{{ route('inventory.index',$resource) }}" class="back-link">{{ $d['title'] }}</a> / RECORD DETAILS</div><h1>{{ $record->name ?? $record->asset_tag ?? $record->title ?? $record->software_name ?? $record->subject ?? $record->reference_no ?? $record->company_name ?? $record->employee_no ?? $d['singular'] }}</h1><p>Record information and current status.</p></div><div class="heading-actions"><a class="btn btn-light border" href="{{ route('inventory.index',$resource) }}"><i class="bi bi-arrow-left me-2"></i>Back</a>@if(auth()->user()->hasPermission($d['permission'].'.manage') && empty($d['workflow_only']))<a class="btn btn-primary" href="{{ route('inventory.edit',[$resource,$record->id]) }}"><i class="bi bi-pencil me-2"></i>Edit record</a>@endif</div></div>
<div class="row g-3"><div class="col-xl-8"><div class="panel"><div class="panel-header"><div><h2>Record information</h2><p>Core details captured in the system</p></div>@if(isset($record->status))<span class="status-pill {{ strtolower($record->status) }}">{{ $resource === 'assets' ? ($assetDisplayLabels['status'][$record->status] ?? str_replace('_',' ',$record->status)) : str_replace('_',' ',$record->status) }}</span>@endif</div><div class="details-grid">
@foreach($d['fields'] as $field)@php($value=$record->{$field['name']})@php($displayValue=match($field['name']){'condition'=>$resource === 'assets' ? ($assetDisplayLabels['condition'][$record->condition] ?? $record->condition) : $record->condition,'category_id'=>$record->category?->name,'parent_id'=>$record->parent?->name,'department_id'=>$record->department?->name,'location_id'=>$record->location?->name,'supplier_id'=>$record->supplier?->company_name,'asset_id'=>$record->asset?->asset_tag,'employee_id'=>$record->employee?->full_name,'from_employee_id'=>$record->fromEmployee?->full_name,'to_employee_id'=>$record->toEmployee?->full_name,default=>$value})<div class="detail-item"><span>{{ $field['label'] }}</span><strong>@if(($field['type'] ?? '') === 'password')••••••••@elseif($displayValue instanceof \Carbon\CarbonInterface){{ $displayValue->format(config('app.date_format', 'd M Y').(in_array($field['name'],['first_response_at','sla_due_at','resolved_at','created_at','updated_at'],true)?' '.config('app.time_format', 'H:i'):'')) }}@elseif(is_bool($displayValue)){{ $displayValue?'Yes':'No' }}@elseif($displayValue===null || $displayValue==='')—@else{{ $displayValue }}@endif</strong></div>@endforeach
</div>
@if($resource === 'assets' && !empty(data_get($record->specs, 'custom_fields')))
<div class="panel-subsection"><div class="panel-header"><div><h2>Category-specific information</h2><p>Additional fields configured for {{ $record->category?->name ?? 'this asset category' }}.</p></div></div><div class="details-grid">
@foreach($record->category?->custom_fields ?? [] as $customField)
 @php($customValue = data_get($record->specs, 'custom_fields.'.$customField['key']))
 <div class="detail-item"><span>{{ $customField['label'] }}</span><strong>{{ $customValue !== null && $customValue !== '' ? $customValue : '—' }}</strong></div>
@endforeach
</div></div>
@endif
@if($resource === 'categories' && !empty($record->custom_fields))
<div class="panel-subsection"><div class="panel-header"><div><h2>Configured asset-specific fields</h2><p>Fields presented when registering assets under this category.</p></div></div><div class="table-responsive"><table class="table table-sm data-table mb-0"><thead><tr><th>Label</th><th>Field key</th><th>Type</th><th>Required</th><th>Options</th></tr></thead><tbody>
@foreach($record->custom_fields as $customField)<tr><td>{{ $customField['label'] ?? '—' }}</td><td><code>{{ $customField['key'] ?? '—' }}</code></td><td>{{ ucfirst($customField['type'] ?? 'text') }}</td><td>{{ !empty($customField['required']) ? 'Yes' : 'No' }}</td><td>{{ implode(', ', $customField['options'] ?? []) ?: '—' }}</td></tr>@endforeach
</tbody></table></div></div>
@endif
@if($resource==='audits' && ($auditItems->isNotEmpty() || !empty($record->findings)))
<div class="panel-subsection"><div class="panel-header"><div><h2>Verification checklist</h2><p>{{ $auditItems->where('result','!=','Pending')->count() }} completed · {{ $auditItems->where('result','Pending')->count() }} remaining</p></div></div>
@if($auditItems->isNotEmpty())
<div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Asset</th><th>Result</th><th>Observed location</th><th>Observed custodian</th><th>Condition</th><th>Auditor / Date</th></tr></thead><tbody>
@foreach($auditItems as $auditItem)<tr><td>@if($auditItem->asset)<span class="mono">{{ $auditItem->asset->asset_tag }}</span><div class="table-sub">{{ $auditItem->asset->name }}</div>@else<span class="mono">{{ $auditItem->unregistered_tag ?? '—' }}</span><div class="table-sub">Unregistered item</div>@endif</td><td><span class="status-pill {{ strtolower(str_replace(' ','_',$auditItem->result)) }}">{{ $auditItem->result }}</span></td><td>{{ $auditItem->seenLocation?->name ?? '—' }}</td><td>{{ $auditItem->seenEmployee?->full_name ?? '—' }}</td><td>{{ $auditItem->condition ?? '—' }}</td><td>{{ $auditItem->auditor?->name ?? '—' }}<div class="table-sub">{{ $auditItem->scanned_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) ?? 'Not checked' }}</div></td></tr>@endforeach
</tbody></table></div>
@else
<div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Asset ID</th><th>Result</th><th>Notes</th><th>Verified at</th></tr></thead><tbody>@foreach($record->findings as $finding)<tr><td class="mono">#{{ $finding['asset_id'] ?? '—' }}</td><td>{{ str_replace('_',' ',$finding['result'] ?? '—') }}</td><td>{{ $finding['notes'] ?? '—' }}</td><td>{{ $finding['verified_at'] ?? '—' }}</td></tr>@endforeach</tbody></table></div>
@endif
</div>
@endif
<div class="record-meta">Created {{ $record->created_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }} · Last updated {{ $record->updated_at?->diffForHumans() }}</div></div>
@if($resource==='consumables')
<div class="panel mt-3"><div class="panel-header"><div><h2>Stock position</h2><p>Current quantity, reservations, and inventory valuation.</p></div><span class="status-pill {{ $stockAvailableQty<=0?'danger':($stockAvailableQty<=max((int)$record->reorder_level,(int)$record->min_level)?'warning':'success') }}">{{ $stockAvailableQty<=0?'Out of stock':($stockAvailableQty<=max((int)$record->reorder_level,(int)$record->min_level)?'Low stock':'In stock') }}</span></div><div class="p-3"><div class="row g-2"><div class="col-6 col-md-3"><div class="small text-secondary">On hand</div><div class="fs-5 fw-semibold">{{ number_format($record->quantity_on_hand) }} {{ $record->unit }}</div></div><div class="col-6 col-md-3"><div class="small text-secondary">Reserved</div><div class="fs-5 fw-semibold">{{ number_format($stockReservedQty) }} {{ $record->unit }}</div></div><div class="col-6 col-md-3"><div class="small text-secondary">Available</div><div class="fs-5 fw-semibold">{{ number_format($stockAvailableQty) }} {{ $record->unit }}</div></div><div class="col-6 col-md-3"><div class="small text-secondary">Inventory value</div><div class="fs-5 fw-semibold">{{ number_format($stockValue,2) }}</div></div></div><hr class="my-3"><div class="d-flex align-items-center justify-content-between mb-2"><strong>Location balances</strong><span class="small text-secondary">{{ $stockLocationBalances->count() }} location record(s)</span></div><div class="table-responsive"><table class="table table-sm data-table mb-0"><thead><tr><th>Location</th><th>On hand</th><th>Reserved</th><th>Available</th></tr></thead><tbody>@forelse($stockLocationBalances as $balance)@php($reservedAtLocation=$record->reservations->where('status','active')->filter(fn($reservation)=>$reservation->location_id===$balance->location_id && (!$reservation->expires_at || $reservation->expires_at->isFuture()))->sum(fn($reservation)=>max(0,(int)$reservation->quantity-(int)$reservation->fulfilled_qty)))<tr><td>{{ $balance->location?->name ?? 'Unassigned location' }}</td><td>{{ number_format($balance->quantity_on_hand) }}</td><td>{{ number_format($reservedAtLocation) }}</td><td>{{ number_format(max(0,(int)$balance->quantity_on_hand-$reservedAtLocation)) }}</td></tr>@empty<tr><td colspan="4" class="empty-state">No location balances recorded.</td></tr>@endforelse</tbody></table></div></div></div>
<div class="panel mt-3"><div class="panel-header"><div><h2>Batch and expiration tracking</h2><p>Tracked lots, remaining quantities, suppliers, and expiry dates.</p></div><span class="small text-secondary">{{ $stockBatches->count() }} lot(s) with stock</span></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Batch / Lot</th><th>Location</th><th>Expiry</th><th>Quantity</th><th>Unit cost</th><th>Supplier</th><th>Status</th></tr></thead><tbody>@forelse($stockBatches as $batch)@php($expired=$batch->expiry_date && $batch->expiry_date->isPast() && !$batch->expiry_date->isToday())@php($expiring=$batch->expiry_date && !$expired && $batch->expiry_date->lte(today()->addDays(30)))<tr><td class="mono">{{ $batch->batch_no }}</td><td>{{ $batch->location?->name ?? 'Unassigned' }}</td><td>{{ $batch->expiry_date?->format(config('app.date_format','d M Y')) ?? '—' }}</td><td>{{ number_format($batch->quantity_on_hand) }} {{ $record->unit }}</td><td>{{ number_format((float)$batch->unit_cost,2) }}</td><td>{{ $batch->supplier?->company_name ?? $record->supplier?->company_name ?? '—' }}</td><td>@if($expired)<span class="status-pill danger">Expired</span>@elseif($expiring)<span class="status-pill warning">Expiring</span>@else<span class="status-pill success">Current</span>@endif</td></tr>@empty<tr><td colspan="7" class="empty-state">No batch or lot details have been recorded.</td></tr>@endforelse</tbody></table></div></div>
<div class="panel mt-3"><div class="panel-header"><div><h2>Stock reservations</h2><p>Quantities held for a person, team, or planned activity.</p></div><span class="small text-secondary">{{ $stockReservations->count() }} active reservation(s)</span></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Reserved for</th><th>Location</th><th>Reserved</th><th>Remaining</th><th>Expires</th><th>Reserved by</th><th>Actions</th></tr></thead><tbody>@forelse($stockReservations as $reservation)<tr><td>{{ $reservation->reserved_for ?? '—' }}<div class="table-sub">{{ $reservation->purpose }}</div></td><td>{{ $reservation->location?->name ?? 'Default location' }}</td><td>{{ number_format($reservation->quantity) }}</td><td>{{ number_format($reservation->remainingQuantity()) }}</td><td>{{ $reservation->expires_at?->format(config('app.date_format','d M Y').' '.config('app.time_format','H:i')) ?? 'No expiry' }}</td><td>{{ $reservation->reserver?->name ?? '—' }}</td><td class="text-nowrap">@if(auth()->user()->hasPermission('consumables.manage'))<form class="d-inline-flex gap-1 align-items-center" method="POST" action="{{ route('stock-reservations.issue',$reservation) }}">@csrf<input class="form-control form-control-sm" type="number" name="quantity" min="1" max="{{ $reservation->remainingQuantity() }}" value="{{ $reservation->remainingQuantity() }}" style="width:76px" aria-label="Quantity to issue"><button class="btn btn-sm btn-outline-primary">Issue</button></form><form class="d-inline" method="POST" action="{{ route('stock-reservations.release',$reservation) }}" onsubmit="return confirm('Release this reservation?')">@csrf<button class="btn btn-sm btn-outline-secondary">Release</button></form>@else—@endif</td></tr>@empty<tr><td colspan="7" class="empty-state">No active reservations.</td></tr>@endforelse</tbody></table></div></div>
<div class="panel mt-3"><div class="panel-header"><div><h2>Inventory ledger and movement history</h2><p>Append-only record of stock receipts, issues, returns, adjustments, transfers, damages, and losses.</p></div><span class="small text-secondary">Latest {{ $stockMovements->count() }} movements</span></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Date</th><th>Movement</th><th>Quantity</th><th>Batch</th><th>Location</th><th>Reference</th><th>Performed by</th><th>Remarks</th></tr></thead><tbody>@forelse($stockMovements as $movement)<tr><td>{{ $movement->created_at?->format(config('app.date_format','d M Y').' '.config('app.time_format','H:i')) }}</td><td><span class="status-pill {{ str_contains($movement->type,'out')||in_array($movement->type,['issue','damaged','lost'],true)?'warning':'success' }}">{{ str_replace('_',' ',$movement->type) }}</span></td><td class="mono {{ $movement->qty<0?'text-danger':'text-success' }}">{{ $movement->qty>0?'+':'' }}{{ number_format($movement->qty) }} {{ $record->unit }}</td><td>{{ $movement->batch_no ?? '—' }}</td><td>{{ $movement->location?->name ?? 'Unassigned' }}@if($movement->toLocation)<div class="table-sub">To: {{ $movement->toLocation->name }}</div>@endif</td><td>{{ $movement->reference_text ?? ($movement->reference_id ? class_basename($movement->reference_type).' #'.$movement->reference_id : '—') }}</td><td>{{ $movement->performer?->name ?? 'System' }}</td><td>{{ $movement->remarks ?? '—' }}</td></tr>@empty<tr><td colspan="8" class="empty-state">No stock movements recorded.</td></tr>@endforelse</tbody></table></div></div>
@if($stockTransfers->isNotEmpty())<div class="panel mt-3"><div class="panel-header"><div><h2>Stock transfer history</h2><p>Location-to-location transfer records.</p></div></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Reference</th><th>From</th><th>To</th><th>Quantity</th><th>Transferred</th><th>By</th></tr></thead><tbody>@foreach($stockTransfers as $transfer)<tr><td class="mono">{{ $transfer->reference_no }}</td><td>{{ $transfer->fromLocation?->name }}</td><td>{{ $transfer->toLocation?->name }}</td><td>{{ number_format($transfer->quantity) }} {{ $record->unit }}</td><td>{{ $transfer->transferred_at?->format(config('app.date_format','d M Y').' '.config('app.time_format','H:i')) }}</td><td>{{ $transfer->requester?->name ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>@endif
@endif
@if($resource==='procurements' && $receivedAssets->isNotEmpty())
<div class="panel mt-3"><div class="panel-header"><div><h2>Assets registered from this purchase</h2><p>{{ $receivedAssets->count() }} of {{ $record->quantity_received }} accepted units registered.</p></div></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Asset tag</th><th>Asset</th><th>Serial number</th><th>Status</th><th></th></tr></thead><tbody>@foreach($receivedAssets as $procuredAsset)<tr><td class="mono">{{ $procuredAsset->asset_tag }}</td><td>{{ $procuredAsset->name }}</td><td>{{ $procuredAsset->serial_number ?? '—' }}</td><td><span class="status-pill {{ $procuredAsset->status }}">{{ $procuredAsset->status }}</span></td><td class="text-end"><a class="btn btn-sm btn-outline-secondary" href="{{ route('inventory.show',['assets',$procuredAsset->id]) }}">Open</a></td></tr>@endforeach</tbody></table></div></div>
@endif
</div>
<div class="col-xl-4"><div class="panel"><div class="panel-header"><div><h2>Record actions</h2><p>Available administrative actions</p></div></div><div class="action-stack">
@if($resource==='procurements' && auth()->user()->hasPermission('assets.manage') && auth()->user()->hasPermission('procurements.manage') && in_array($record->status,['received','partially_received'],true) && $receivedAssets->count() < $record->quantity_received)
<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#registerProcurementAssetModal"><i class="bi bi-pc-display me-2"></i>Register received asset</button>
<div class="modal fade" id="registerProcurementAssetModal" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content" method="POST" action="{{ route('procurements.assets.store',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Register asset from purchase</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Asset tag</label><input class="form-control" name="asset_tag" maxlength="80" @unless($automaticAssetNumbering) required @endunless><div class="form-text">@if($automaticAssetNumbering)Leave blank to generate the asset ID automatically using the configured prefix.@elseEnter the asset ID manually; automatic numbering is disabled.@endif</div></div><div class="col-md-6"><label class="form-label">Asset name</label><input class="form-control" name="name" value="{{ $record->item_description }}" required maxlength="255"></div><div class="col-md-6"><label class="form-label">Serial number</label><input class="form-control" name="serial_number" maxlength="255"></div><div class="col-md-6"><label class="form-label">Category</label><select class="form-select" name="category_id" required><option value="">Select category</option>@foreach(\App\Models\AssetCategory::where('type','asset')->where('is_active',true)->orderBy('name')->get() as $category)<option value="{{ $category->id }}">{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Brand</label><input class="form-control" name="brand"></div><div class="col-md-6"><label class="form-label">Model</label><input class="form-control" name="model"></div><div class="col-12"><label class="form-label">Specifications</label><textarea class="form-control" name="specifications" rows="2"></textarea></div><div class="col-md-6"><label class="form-label">Purchase date</label><input type="date" class="form-control" name="purchase_date" value="{{ $record->received_at?->format('Y-m-d') ?? today()->toDateString() }}" required></div><div class="col-md-6"><label class="form-label">Unit purchase cost</label><input type="number" min="0" step="0.01" class="form-control" name="purchase_cost" value="{{ number_format((float)$record->total_cost / max(1,(int)$record->quantity),2,'.','') }}" required></div><div class="col-md-6"><label class="form-label">Warranty expiration</label><input type="date" class="form-control" name="warranty_expiration"></div><div class="col-md-6"><label class="form-label">Condition</label><select class="form-select" name="condition" required><option>New</option><option selected>Good</option><option>Fair</option><option>Defective</option></select></div><div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department_id"><option value="">Unassigned</option>@foreach(\App\Models\Department::orderBy('name')->get() as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Location</label><select class="form-select" name="location_id"><option value="">Unassigned</option>@foreach(\App\Models\Location::orderBy('name')->get() as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Register asset</button></div></form></div></div>
@endif
@if($resource==='procurements' && auth()->user()->hasPermission('procurements.manage') && $record->status==='approved')
<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#placeOrderModal"><i class="bi bi-bag-check me-2"></i>Record purchase order</button>
<div class="modal fade" id="placeOrderModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('procurements.order',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Place purchase order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Purchase order number</label><input class="form-control mb-3" name="purchase_order_no" required maxlength="120"><label class="form-label">Expected delivery date</label><input type="date" class="form-control mb-3" name="expected_delivery_at" min="{{ today()->toDateString() }}" required><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2"></textarea></div><div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save purchase order</button></div></form></div></div>
@endif
@if($resource==='procurements' && auth()->user()->hasPermission('procurements.manage') && in_array($record->status,['ordered','partially_received'],true))
<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#receiveProcurementModal"><i class="bi bi-box-seam me-2"></i>Record delivery / inspection</button>
<div class="modal fade" id="receiveProcurementModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('procurements.receive',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Receive and inspect delivery</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><p class="small text-secondary">Outstanding quantity: <strong>{{ max(0,(int)$record->quantity-(int)$record->quantity_received) }}</strong></p><label class="form-label">Quantity received</label><input type="number" class="form-control mb-3" name="received_quantity" min="1" max="{{ max(1,(int)$record->quantity-(int)$record->quantity_received) }}" required><label class="form-label">Receipt date</label><input type="date" class="form-control mb-3" name="received_at" value="{{ today()->toDateString() }}" required><label class="form-label">Inspection result</label><select class="form-select mb-3" name="inspection_result" required><option value="accepted">Accepted</option><option value="rejected">Rejected</option></select><label class="form-label">Inspection remarks</label><textarea class="form-control" name="inspection_remarks" rows="3"></textarea><div class="form-text">Rejected deliveries close the procurement as rejected. Attach receiving and inspection documents in the Documents section.</div></div><div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save receiving result</button></div></form></div></div>
@endif
@if($resource==='assets')<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#qrModal"><i class="bi bi-qr-code me-2"></i>View QR label</button>@if($record->status==='available' && auth()->user()->hasPermission('assignments.manage'))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="bi bi-person-plus me-2"></i>Assign asset</button>@endif @if($record->status==='assigned' && auth()->user()->hasPermission('transfers.manage'))<button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#transferModal"><i class="bi bi-arrow-left-right me-2"></i>Request transfer</button>@endif @if(auth()->user()->hasPermission('disposals.manage') && !in_array($record->status,['assigned','retired','lost'],true))<button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disposalModal"><i class="bi bi-recycle me-2"></i>Request disposal</button>@endif
@elseif($resource==='consumables' && auth()->user()->hasPermission('consumables.manage'))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockModal"><i class="bi bi-arrow-down-up me-2"></i>Record movement</button><button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockTransferModal"><i class="bi bi-arrow-left-right me-2"></i>Transfer stock</button><button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#stockReservationModal"><i class="bi bi-bookmark-plus me-2"></i>Reserve stock</button>
@elseif($resource==='transfers' && $record->status==='pending' && auth()->user()->hasPermission('transfers.approve'))<form method="POST" action="{{ route('transfers.approve',$record) }}">@csrf<button class="btn btn-primary w-100">Approve transfer</button></form><form method="POST" action="{{ route('transfers.reject',$record) }}">@csrf<button class="btn btn-light border w-100">Reject transfer</button></form>
@elseif($resource==='assignments' && $record->status==='pending_approval' && auth()->user()->hasPermission('approvals.approve'))<form method="POST" action="{{ route('assignments.approve',$record) }}" class="d-inline">@csrf<button class="btn btn-primary" onclick="return confirm('Approve this assignment?')">Approve assignment</button></form><form method="POST" action="{{ route('assignments.reject',$record) }}" class="d-inline">@csrf<input type="hidden" name="remarks" value="Rejected by approver"><button class="btn btn-outline-danger" onclick="return confirm('Reject this assignment?')">Reject</button></form>
@elseif($resource==='assignments' && $record->status==='issued' && auth()->user()->hasPermission('assignments.manage'))<button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#returnModal">Record asset return</button>@elseif($resource==='audits' && auth()->user()->hasPermission('audits.manage'))@if(in_array($record->status,['planned','in_progress'],true))<a class="btn btn-outline-primary" href="{{ route('audits.scan',$record) }}"><i class="bi bi-upc-scan me-2"></i>Scan audit checklist</a>@if(!empty($record->findings) && $auditItems->where('result','Pending')->isEmpty())<form method="POST" action="{{ route('audits.complete',$record) }}" onsubmit="return confirm('Complete and close this physical inventory audit?')">@csrf<button class="btn btn-primary w-100"><i class="bi bi-check2-circle me-2"></i>Complete audit</button></form>@endif @else<span class="small text-secondary">This audit is closed.</span>@endif @endif
@if($resource==='assignments')<a class="btn btn-outline-secondary" target="_blank" href="{{ route('assignments.receipt',$record) }}"><i class="bi bi-printer me-2"></i>Property acknowledgment</a><a class="btn btn-outline-secondary" target="_blank" href="{{ route('assignments.issuance',$record) }}?document_type=issuance"><i class="bi bi-file-earmark-text me-2"></i>Equipment issuance form</a>@endif
@if($resource==='transfers')<a class="btn btn-outline-secondary" target="_blank" href="{{ route('transfers.turnover',$record) }}"><i class="bi bi-arrow-left-right me-2"></i>Asset turnover form</a>@endif
@if(auth()->user()->hasPermission($d['permission'].'.manage') && empty($d['workflow_only']))<form method="POST" action="{{ route('inventory.destroy',[$resource,$record->id]) }}" onsubmit="return confirm('Archive this record? It will no longer appear in the active register.')">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100"><i class="bi bi-archive me-2"></i>Archive record</button></form>@endif
</div></div></div></div>
@if($resource==='assets')
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="qrModalLabel">Asset identification label</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="assetLabelPrint" class="asset-label-print">
                    <div id="assetQr" class="qr-holder">
                        <span class="text-secondary small">Preparing QR code...</span>
                    </div>
                    <div class="asset-label-info">
                        <div class="asset-label-heading">IT ASSET</div>
                        <strong class="asset-label-tag">{{ $record->asset_tag }}</strong>
                        <div class="asset-label-name">{{ $record->name }}</div>
                        @if(!empty($record->serial_number))
                            <div class="asset-label-detail">S/N: {{ $record->serial_number }}</div>
                        @endif
                        <div class="asset-label-detail">{{ $record->category?->name ?? 'IT Asset' }} · {{ $record->location?->name ?? 'Unassigned' }}</div>
                        <div class="asset-label-detail">Status: {{ str_replace('_',' ',$record->status) }}</div>
                        <div class="asset-label-barcode">
                            <svg id="assetBarcode" aria-label="Asset barcode"></svg>
                        </div>
                    </div>
                </div>
                <button type="button" id="printAssetLabelBtn" class="btn btn-primary btn-sm mt-3" disabled>
                    <i class="bi bi-printer me-1"></i>Print QR label
                </button>
            </div>
        </div>
    </div>
</div>
@if(auth()->user()->hasPermission('assignments.manage'))<div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('assets.assign',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Assign asset</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Employee</label><select name="employee_id" class="form-select mb-3" required><option value="">Select employee</option>@foreach(\App\Models\Employee::where('status','active')->orderBy('last_name')->get() as $emp)<option value="{{ $emp->id }}">{{ $emp->employee_no }} — {{ $emp->full_name }}</option>@endforeach</select><div class="row g-2"><div class="col-md-6"><label class="form-label">Assignment type</label><select name="assignment_type" class="form-select mb-3"><option value="permanent">Permanent</option><option value="temporary">Temporary</option></select></div><div class="col-md-6"><label class="form-label">Department</label><select name="department_id" class="form-select mb-3"><option value="">Use employee department</option>@foreach(\App\Models\Department::orderBy('name')->get() as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Office / location</label><select name="location_id" class="form-select mb-3"><option value="">Keep current location</option>@foreach(\App\Models\Location::orderBy('name')->get() as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Approval</label><select name="requires_approval" class="form-select mb-3"><option value="0">Issue immediately</option><option value="1">Submit for approval</option></select></div></div><label class="form-label">Issue date</label><input type="date" name="issued_at" class="form-control mb-3" value="{{ today()->toDateString() }}" required><label class="form-label">Due date (optional)</label><input type="date" name="due_date" class="form-control mb-3"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control mb-3" rows="2"></textarea><label class="form-label">Recipient acknowledgment signature <span class="text-secondary small">(optional)</span></label><div class="signature-pad-wrap"><canvas id="assignmentSignature" class="signature-pad" width="900" height="240" aria-label="Draw acknowledgment signature"></canvas></div><input type="hidden" name="signature_data" id="assignmentSignatureData"><div class="d-flex justify-content-between align-items-center mt-2"><span class="form-text">Sign using a mouse, touch screen, or stylus.</span><button type="button" class="btn btn-sm btn-light border" id="clearAssignmentSignature">Clear signature</button></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Confirm assignment</button></div></form></div></div>@endif
@if(auth()->user()->hasPermission('transfers.manage'))<div class="modal fade" id="transferModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('assets.transfer',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Request asset transfer</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">New custodian</label><select name="to_employee_id" class="form-select mb-3" required><option value="">Select employee</option>@foreach(\App\Models\Employee::where('status','active')->orderBy('last_name')->get() as $emp)<option value="{{ $emp->id }}">{{ $emp->employee_no }} — {{ $emp->full_name }}</option>@endforeach</select><div class="row g-2"><div class="col-md-6"><label class="form-label">Transfer type</label><select name="transfer_type" class="form-select mb-3"><option value="permanent">Permanent</option><option value="temporary">Temporary</option></select></div><div class="col-md-6"><label class="form-label">Destination department</label><select name="to_department_id" class="form-select mb-3"><option value="">Use new custodian department</option>@foreach(\App\Models\Department::orderBy('name')->get() as $dept)<option value="{{ $dept->id }}">{{ $dept->name }}</option>@endforeach</select></div><div class="col-12"><label class="form-label">Destination location</label><select name="to_location_id" class="form-select mb-3"><option value="">Keep current location</option>@foreach(\App\Models\Location::orderBy('name')->get() as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div></div><label class="form-label">Reason</label><textarea name="reason" class="form-control" rows="3" required></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Submit request</button></div></form></div></div>@endif
@elseif($resource==='consumables' && auth()->user()->hasPermission('consumables.manage'))
<div class="modal fade" id="stockModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('consumables.movement',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Record stock movement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="row g-3"><div class="col-md-6"><label class="form-label">Movement type</label><select name="type" class="form-select" required><option value="receive">Receive stock</option><option value="issue">Issue stock</option><option value="return">Return stock</option><option value="adjust">Stock adjustment (set balance)</option><option value="damaged">Record damaged stock</option><option value="lost">Record lost stock</option></select></div><div class="col-md-6"><label class="form-label">Location</label><select name="location_id" class="form-select"><option value="">Default item location ({{ $record->location?->name ?? 'Unassigned' }})</option>@foreach($stockLocations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div><div class="col-md-6"><label class="form-label">Quantity / new balance</label><input type="number" min="0" max="1000000" name="quantity" class="form-control" required></div><div class="col-md-6"><label class="form-label">Batch / lot (for receipts)</label><input name="batch_no" maxlength="100" class="form-control" placeholder="Optional batch no."></div><div class="col-md-6"><label class="form-label">Expiration date</label><input type="date" name="expiry_date" class="form-control"></div><div class="col-md-6"><label class="form-label">Select batch / lot</label><select name="batch_id" class="form-select"><option value="">Automatic FEFO allocation</option>@foreach($stockBatches as $batch)<option value="{{ $batch->id }}">{{ $batch->batch_no }} · {{ $batch->quantity_on_hand }} {{ $record->unit }} · {{ $batch->expiry_date?->format('d M Y') ?? 'No expiry' }}</option>@endforeach</select><div class="form-text">Issues use earliest-expiring stock first by default. Select a lot to issue from it or add a returned quantity back to that lot.</div></div><div class="col-md-6"><label class="form-label">Reference</label><input name="reference" class="form-control" maxlength="100"></div><div class="col-12"><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Save movement</button></div></form></div></div>
<div class="modal fade" id="stockTransferModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('consumables.transfer',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Transfer stock between locations</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">From location</label><select name="from_location_id" class="form-select mb-3" required><option value="">Select source location</option>@foreach($stockLocations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select><label class="form-label">To location</label><select name="to_location_id" class="form-select mb-3" required><option value="">Select destination location</option>@foreach($stockLocations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select><label class="form-label">Quantity</label><input type="number" name="quantity" min="1" max="1000000" class="form-control mb-3" required><label class="form-label">Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea><div class="form-text">A transfer changes the location balances but does not change the overall on-hand quantity.</div></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Complete transfer</button></div></form></div></div>
<div class="modal fade" id="stockReservationModal" tabindex="-1"><div class="modal-dialog"><form class="modal-content" method="POST" action="{{ route('consumables.reserve',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Reserve stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Location</label><select name="location_id" class="form-select mb-3"><option value="">Default item location ({{ $record->location?->name ?? 'Unassigned' }})</option>@foreach($stockLocations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select><label class="form-label">Quantity to reserve</label><input type="number" name="quantity" min="1" max="1000000" class="form-control mb-3" required><label class="form-label">Reserved for</label><input name="reserved_for" class="form-control mb-3" maxlength="180" placeholder="Person, team, or office"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control mb-3" rows="2"></textarea><label class="form-label">Reservation expiry (optional)</label><input type="datetime-local" name="expires_at" class="form-control"></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Reserve quantity</button></div></form></div></div>
@elseif($resource==='assignments' && $record->status==='issued' && auth()->user()->hasPermission('assignments.manage'))
<div class="modal fade" id="returnModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" action="{{ route('assignments.return',$record) }}">@csrf<div class="modal-header"><h5 class="modal-title">Record asset return</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Returned condition</label><select name="condition" class="form-select mb-3"><option>Good</option><option>New</option><option>Fair</option><option>Defective</option></select><label class="form-label">Remarks</label><textarea name="remarks" class="form-control"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Confirm return</button></div></form></div></div>
@endif


@if($resource==='assets' && auth()->user()->hasPermission('disposals.manage') && !in_array($record->status,['assigned','retired','lost'],true))
<div class="modal fade" id="disposalModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('assets.disposal',$record) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Request asset disposal</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Proposed method</label><select name="proposed_method" class="form-select mb-3" required><option value="sale">Sale</option><option value="donation">Donation</option><option value="recycling">Recycling</option><option value="destruction">Destruction</option><option value="return_to_supplier">Return to supplier</option></select><label class="form-label">Justification</label><textarea name="justification" class="form-control mb-3" rows="3" required></textarea><label class="form-label">Technical condition</label><input name="technical_condition" class="form-control mb-3"><label class="form-label">Estimated salvage value</label><input type="number" min="0" step="0.01" name="estimated_salvage_value" class="form-control mb-3" value="0"><label class="form-label">Request date</label><input type="date" name="requested_at" class="form-control" value="{{ today()->toDateString() }}" required></div><div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Create disposal request</button></div></form></div></div>
@endif
@if(in_array($resource,['procurements','disposals']) && auth()->user()->hasPermission($d['permission'].'.manage'))
@if($approvals->whereIn('status',['pending','waiting','approved'])->isEmpty() && in_array($record->status,['draft','submitted','pending_approval','rejected','revision_requested'],true))
<div class="panel mt-3"><div class="panel-header"><div><h2>Approval workflow</h2><p>Submit this request for authorized review.</p></div></div><div class="p-3"><form method="POST" action="{{ route('approvals.submit',[$resource,$record->id]) }}">@csrf<div class="row g-3">
@if($approvalFlow)
 <div class="col-12"><div class="alert alert-info mb-0"><strong>Configured flow: {{ $approvalFlow->name }}</strong><div class="small">{{ $approvalFlow->steps->count() }} approval step(s) · Routing is managed by the configured user/role assignments.</div></div></div>
@else
 <div class="col-12"><div class="alert alert-warning mb-0">No active flow is configured for this request type. Select the approvers in their exact sequential review order below.</div></div>
 @for($stage=1;$stage<=5;$stage++)<div class="col-md-6 col-xl-4"><label class="form-label">Approval stage {{ $stage }} @if($stage===1)<span class="required-mark">*</span>@else <span class="text-secondary small">(optional)</span>@endif</label><select name="approver_ids[]" class="form-select" @required($stage===1)><option value="">{{ $stage===1?'Select first approver':'No additional approver' }}</option>@foreach($approverOptions as $u)<option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>@endforeach</select></div>@endfor
@endif
 <div class="col-md-8"><label class="form-label">Workflow remarks</label><input name="remarks" class="form-control" placeholder="Optional routing note"></div><div class="col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100">Submit for approval</button></div></div></form></div></div>
@endif
@if($approvals->isNotEmpty())
<div class="panel mt-3"><div class="panel-header"><div><h2>Approval history</h2><p>Sequential review and decision record.</p></div></div><div class="table-responsive"><table class="table table-sm data-table mb-0"><thead><tr><th>Round / stage</th><th>Approver</th><th>Status</th><th>Acted at</th><th>Remarks</th></tr></thead><tbody>@foreach($approvals as $approval)<tr><td>{{ $approval->round ?? 1 }} / {{ $approval->sequence }}</td><td>{{ $approval->approver?->name ?? '—' }}</td><td><span class="status-pill {{ $approval->status }}">{{ str_replace('_',' ',$approval->status) }}</span></td><td>{{ $approval->acted_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) ?? '—' }}</td><td>{{ $approval->remarks ?? '—' }}</td></tr>@endforeach</tbody></table></div></div>
@endif
@endif
@if($resource==='disposals' && $record->status==='approved' && auth()->user()->hasPermission('disposals.manage'))
<div class="panel mt-3"><div class="panel-header"><div><h2>Complete asset disposal</h2><p>Record the actual date after the approved disposal method has been carried out.</p></div></div><form class="p-3" method="POST" action="{{ route('disposals.complete',$record) }}">@csrf<div class="row g-2"><div class="col-md-5"><label class="form-label">Disposal date</label><input type="date" name="disposed_at" class="form-control" value="{{ today()->toDateString() }}" required></div><div class="col-md-5"><label class="form-label">Completion remarks</label><input name="remarks" class="form-control"></div><div class="col-md-2 d-flex align-items-end"><button class="btn btn-danger w-100">Mark disposed</button></div></div></form></div>
@endif
@if($resource==='software' && auth()->user()->hasPermission('software.manage'))
<div class="panel mt-3"><div class="panel-header"><div><h2>License allocations</h2><p>{{ $licenseAssignments->where('status','active')->count() }} active allocation(s)</p></div><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#licenseAssignModal">Assign license</button></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Assigned to</th><th>Asset</th><th>Assigned date</th><th>Status</th><th></th></tr></thead><tbody>@forelse($licenseAssignments as $allocation)<tr><td>{{ $allocation->employee?->full_name ?? $allocation->assigned_to ?? '—' }}</td><td>{{ $allocation->asset?->asset_tag ?? '—' }}</td><td>{{ $allocation->assigned_at?->format(config('app.date_format', 'd M Y')) }}</td><td><span class="status-pill {{ $allocation->status }}">{{ $allocation->status }}</span></td><td class="text-end">@if($allocation->status==='active')<form method="POST" action="{{ route('licenses.return',$allocation) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Return</button></form>@endif</td></tr>@empty<tr><td colspan="5" class="empty-state">No license allocations recorded.</td></tr>@endforelse</tbody></table></div></div>
<div class="modal fade" id="licenseAssignModal" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('licenses.assign',$record) }}" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">Assign software license</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Employee</label><select name="employee_id" class="form-select mb-3"><option value="">Select employee</option>@foreach(\App\Models\Employee::where('status','active')->orderBy('last_name')->get() as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>@endforeach</select><label class="form-label">Computer / asset (optional)</label><select name="asset_id" class="form-select mb-3"><option value="">Select asset</option>@foreach(\App\Models\Asset::orderBy('asset_tag')->get() as $asset)<option value="{{ $asset->id }}">{{ $asset->asset_tag }} — {{ $asset->name }}</option>@endforeach</select><label class="form-label">Other assignee (optional)</label><input name="assigned_to" class="form-control mb-3"><label class="form-label">Assigned date</label><input type="date" name="assigned_at" value="{{ today()->toDateString() }}" class="form-control" required></div><div class="modal-footer"><button class="btn btn-light border" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary">Assign</button></div></form></div></div>
@endif
@if($resource==='maintenance' && auth()->user()->hasPermission('maintenance.manage') && !in_array($record->status,['completed','cancelled'],true))
<div class="panel mt-3"><div class="panel-header"><div><h2>Close work order</h2><p>Record the resolution, cost, parts used, and next preventive maintenance date.</p></div></div><div class="p-3"><form method="POST" action="{{ route('maintenance.complete',$record) }}">@csrf<div class="row g-2"><div class="col-md-6"><label class="form-label">Completed date</label><input type="date" class="form-control" name="completed_at" value="{{ today()->toDateString() }}" required></div><div class="col-md-6"><label class="form-label">Final cost</label><input type="number" step="0.01" min="0" class="form-control" name="cost" value="{{ $record->cost }}"></div><div class="col-12"><label class="form-label">Resolution</label><textarea class="form-control" name="resolution" rows="3" required></textarea></div><div class="col-md-6"><label class="form-label">Parts used</label><input class="form-control" name="parts_used"></div><div class="col-md-6"><label class="form-label">Next maintenance date</label><input type="date" class="form-control" name="next_maintenance_at"></div><div class="col-12 text-end"><button class="btn btn-primary">Complete work order</button></div></div></form><hr><form method="POST" action="{{ route('maintenance.cancel',$record) }}" class="d-flex gap-2 align-items-end" onsubmit="return confirm('Cancel this work order?')">@csrf<div class="flex-grow-1"><label class="form-label">Cancellation reason</label><input name="remarks" class="form-control" required maxlength="2000"></div><button class="btn btn-outline-danger">Cancel work order</button></form></div></div>
@endif
@if($resource==='tickets' && auth()->user()->hasPermission('tickets.manage'))
<div class="panel mt-3"><div class="panel-header"><div><h2>Ticket conversation</h2><p>Technician updates and internal notes.</p></div><span class="small-muted">{{ $comments->count() }} entries</span></div><div class="p-3"><form method="POST" action="{{ route('tickets.comments.store',$record) }}">@csrf<textarea name="comment" class="form-control mb-2" rows="3" placeholder="Add a technical update or resolution note..." required></textarea><label class="form-check small mb-3"><input class="form-check-input" type="checkbox" name="is_internal" value="1"><span class="form-check-label">Internal note (not visible to requester)</span></label><button class="btn btn-primary btn-sm">Add update</button></form><div class="ticket-thread mt-3">@forelse($comments as $comment)<div class="ticket-comment"><div><strong>{{ $comment->user?->name ?? 'System' }}</strong><span>{{ $comment->created_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }}</span>@if($comment->is_internal)<em>Internal</em>@endif</div><p>{{ $comment->comment }}</p></div>@empty<div class="text-secondary small py-2">No comments or technician updates yet.</div>@endforelse</div></div></div>
@endif
@if($resource==='employees')
<div class="panel mt-3"><div class="panel-header"><div><h2>Employee equipment accountability</h2><p>All equipment must be returned or transferred before employee offboarding.</p></div><span class="small-muted">{{ $employeeAssets->count() }} currently assigned</span></div>
<div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Asset tag</th><th>Equipment</th><th>Category</th><th>Location</th><th>Status</th></tr></thead><tbody>@forelse($employeeAssets as $asset)<tr><td class="mono">{{ $asset->asset_tag }}</td><td>{{ $asset->name }}</td><td>{{ $asset->category?->name ?? '—' }}</td><td>{{ $asset->location?->name ?? '—' }}</td><td><span class="status-pill {{ $asset->status }}">{{ $asset->status }}</span></td></tr>@empty<tr><td colspan="5" class="empty-state">No equipment is currently assigned to this employee.</td></tr>@endforelse</tbody></table></div>
<div class="p-3 border-top"><div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><strong>Assignment history</strong><span class="small-muted">{{ $employeeAssignments->count() }} records</span></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>Reference</th><th>Asset</th><th>Issued</th><th>Returned</th><th>Status</th></tr></thead><tbody>@forelse($employeeAssignments as $assignment)<tr><td class="mono">{{ $assignment->reference_no }}</td><td>{{ $assignment->asset?->asset_tag }} · {{ $assignment->asset?->name }}</td><td>{{ $assignment->issued_at?->format(config('app.date_format', 'd M Y')) ?? '—' }}</td><td>{{ $assignment->returned_at?->format(config('app.date_format', 'd M Y')) ?? '—' }}</td><td>{{ ucfirst($assignment->status) }}</td></tr>@empty<tr><td colspan="5" class="text-secondary small">No assignment records.</td></tr>@endforelse</tbody></table></div></div>
@if(auth()->user()->hasPermission('employees.manage') && $record->status==='active')<form method="POST" action="{{ route('employees.offboard',$record) }}" class="p-3 border-top" onsubmit="return confirm('Confirm employee offboarding? The linked account will be disabled when status is Separated.')">@csrf<h3 class="section-subtitle">Employee transfer / separation</h3><div class="row g-2 align-items-end"><div class="col-md-4"><label class="form-label">Final status</label><select class="form-select" name="final_status" required><option value="transferred">Transferred</option><option value="separated">Separated</option></select></div><div class="col-md-4"><label class="form-label">Effective date</label><input class="form-control" type="date" name="effective_date" value="{{ today()->toDateString() }}" required></div><div class="col-md-4"><label class="form-label">Remarks</label><input class="form-control" name="remarks" maxlength="3000"></div><div class="col-12 text-end"><button class="btn btn-outline-danger">Complete offboarding</button></div></div></form>@endif
</div>
@endif
@if($resource==='suppliers')
<div class="panel mt-3"><div class="panel-header"><div><h2>Supplier performance</h2><p>Record quality, delivery reliability and service performance after procurement engagements.</p></div><span class="small-muted">{{ $supplierEvaluations->count() }} evaluation(s)</span></div>
@if(auth()->user()->hasPermission('suppliers.manage'))<form method="POST" action="{{ route('suppliers.evaluations.store',$record) }}" class="p-3 border-bottom">@csrf<div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Evaluation date</label><input class="form-control" type="date" name="evaluated_on" value="{{ today()->toDateString() }}" required></div>@foreach(['quality_score'=>'Quality','delivery_score'=>'Delivery','service_score'=>'Service'] as $field=>$label)<div class="col-md-2"><label class="form-label">{{ $label }} (1–5)</label><select class="form-select" name="{{ $field }}" required>@for($score=5;$score>=1;$score--)<option value="{{ $score }}">{{ $score }} — {{ [1=>'Poor',2=>'Fair',3=>'Satisfactory',4=>'Good',5=>'Excellent'][$score] }}</option>@endfor</select></div>@endforeach<div class="col-md-3"><label class="form-label">Remarks</label><input class="form-control" name="remarks" maxlength="3000" placeholder="Optional comments"></div><div class="col-12 text-end"><button class="btn btn-primary btn-sm">Save evaluation</button></div></div></form>@endif
<div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Date</th><th>Quality</th><th>Delivery</th><th>Service</th><th>Overall</th><th>Evaluated by</th><th>Remarks</th></tr></thead><tbody>@forelse($supplierEvaluations as $evaluation)<tr><td>{{ $evaluation->evaluated_on?->format(config('app.date_format', 'd M Y')) }}</td><td>{{ $evaluation->quality_score }}/5</td><td>{{ $evaluation->delivery_score }}/5</td><td>{{ $evaluation->service_score }}/5</td><td><strong>{{ number_format((float)$evaluation->overall_score,2) }}/5</strong></td><td>{{ $evaluation->evaluator?->name ?? '—' }}</td><td>{{ $evaluation->remarks ?? '—' }}</td></tr>@empty<tr><td colspan="7" class="empty-state">No supplier performance evaluations recorded.</td></tr>@endforelse</tbody></table></div></div>
@endif
@if($resource==='assets')
<div class="panel mt-3">
 <div class="panel-header"><div><h2>Asset image gallery</h2><p>Primary image and supporting asset photographs.</p></div></div>
 <div class="p-3">
  @if($record->image_path)
   <div class="mb-3"><img src="{{ route('assets.image',$record->id) }}" alt="Primary image of {{ $record->name }}" class="img-fluid rounded border" style="max-height:260px;object-fit:contain">
    @if(auth()->user()->hasPermission('assets.manage'))<form class="mt-2" method="POST" action="{{ route('assets.image.destroy',$record->id) }}" onsubmit="return confirm('Remove the primary asset image?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove primary image</button></form>@endif
   </div>
  @else
   <p class="text-secondary small">No primary asset image has been uploaded.</p>
  @endif
  @if($assetGallery->isNotEmpty())
   <div class="row g-2 mb-3">
    @foreach($assetGallery as $photo)
     @if(in_array(strtolower($photo->mime_type ?? ''),['image/jpeg','image/png','image/webp'],true))
      <div class="col-6 col-md-3"><a href="{{ route('documents.preview',$photo) }}" target="_blank" rel="noopener"><img src="{{ route('documents.preview',$photo) }}" alt="{{ $photo->original_name }}" class="img-fluid rounded border" style="width:100%;height:130px;object-fit:cover"></a><div class="small text-secondary text-truncate mt-1">{{ $photo->original_name }}</div></div>
     @endif
    @endforeach
   </div>
  @endif
  @if(auth()->user()->hasPermission('assets.manage'))
   <form method="POST" action="{{ route('assets.image.update',$record->id) }}" enctype="multipart/form-data" class="border-top pt-3">
    @csrf
    <div class="row g-2 align-items-end">
     <div class="col-md-5"><label class="form-label">Primary image (JPG, PNG, WEBP; max 5 MB)</label><input class="form-control" type="file" name="image" accept="image/jpeg,image/png,image/webp" required></div>
     <div class="col-md-5"><label class="form-label">Additional gallery images (up to 8; max 5 MB each)</label><input class="form-control" type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple></div>
     <div class="col-md-2"><button class="btn btn-outline-primary w-100">Upload images</button></div>
    </div>
   </form>
  @endif
 </div>
</div>
<div class="panel mt-3">
 <div class="panel-header"><div><h2>Asset depreciation and valuation</h2><p>Estimated straight-line values are informational and do not create ledger postings.</p></div></div>
 <div class="details-grid p-3">
  <div class="detail-item"><span>Depreciation method</span><strong>{{ str_replace('_',' ',$assetValuation['method'] ?? 'not configured') }}</strong></div>
  <div class="detail-item"><span>Useful life</span><strong>{{ ($assetValuation['useful_life_months'] ?? 0) ? $assetValuation['useful_life_months'].' months' : 'Not configured' }}</strong></div>
  <div class="detail-item"><span>Monthly depreciation (estimate)</span><strong>₱{{ number_format($assetValuation['monthly_depreciation'] ?? 0,2) }}</strong></div>
  <div class="detail-item"><span>Estimated accumulated depreciation</span><strong>₱{{ number_format($assetValuation['estimated_accumulated'] ?? 0,2) }}</strong></div>
  <div class="detail-item"><span>Estimated net book value</span><strong>₱{{ number_format($assetValuation['estimated_book_value'] ?? (float)$record->purchase_cost,2) }}</strong></div>
  <div class="detail-item"><span>Posted accumulated depreciation</span><strong>₱{{ number_format($assetValuation['posted_accumulated'] ?? 0,2) }}</strong></div>
  <div class="detail-item"><span>Latest posted book value</span><strong>{{ ($assetValuation['has_posted_entries'] ?? false) ? '₱'.number_format($assetValuation['posted_book_value'],2) : 'No posted entries' }}</strong></div>
  <div class="detail-item"><span>Posted through</span><strong>{{ isset($assetValuation['posted_through']) && $assetValuation['posted_through'] ? \Illuminate\Support\Carbon::parse($assetValuation['posted_through'])->format(config('app.date_format','d M Y')) : '—' }}</strong></div>
 </div>
</div>
@endif
@if($documents->isNotEmpty() || in_array($resource,['assets','maintenance','tickets','procurements','audits','disposals']))
<div class="panel mt-3"><div class="panel-header"><div><h2>Documents & attachments</h2><p>Private files associated with this record.</p></div><span class="small-muted">{{ $documents->count() }} files</span></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Document</th><th>Uploaded by</th><th>Size</th><th>Date</th><th></th></tr></thead><tbody>@forelse($documents as $document)<tr><td>{{ $document->title }}</td><td>{{ $document->uploader?->name ?? '—' }}</td><td>{{ number_format(($document->size??0)/1024,1) }} KB</td><td>{{ $document->created_at?->format(config('app.date_format', 'd M Y')) }}</td><td class="text-end">@if(in_array(strtolower($document->mime_type ?? ''),['application/pdf','image/jpeg','image/png','image/webp'],true))<a class="btn btn-sm btn-outline-secondary" href="{{ route('documents.preview',$document) }}" target="_blank" rel="noopener">Preview</a>@endif <a class="btn btn-sm btn-outline-primary" href="{{ route('documents.download',$document) }}">Download <span class="text-muted">v{{ $document->version ?? 1 }}</span></a>@if(auth()->user()->hasPermission($d['permission'].'.manage'))<form class="d-inline" method="POST" action="{{ route('documents.destroy',$document) }}" onsubmit="return confirm('Remove this document?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>@endif</td></tr>@empty<tr><td colspan="5" class="empty-state">No documents have been uploaded.</td></tr>@endforelse</tbody></table></div>@if(auth()->user()->hasPermission($d['permission'].'.manage'))<form class="p-3 border-top" method="POST" enctype="multipart/form-data" action="{{ route('documents.store',[$resource,$record->id]) }}">@csrf<div class="row g-2 align-items-end"><div class="col-md-3"><label class="form-label">Document title</label><input class="form-control" name="title" maxlength="150" required></div><div class="col-md-3"><label class="form-label">Document type</label><select class="form-select" name="type"><option value="supporting">Supporting document</option><option value="invoice">Invoice / Receipt</option><option value="warranty">Warranty</option><option value="inspection">Inspection report</option><option value="technical">Technical report</option><option value="turnover">Turnover / Acknowledgment</option><option value="other">Other</option></select></div><div class="col-md-4"><label class="form-label">File (max 10 MB)</label><input type="file" class="form-control" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.csv,.txt" required></div><div class="col-md-3"><button class="btn btn-outline-primary w-100">Upload document</button></div></div></form>@endif</div>
@endif
@if($resource==='assets' && $assetHistories->isNotEmpty())
<div class="panel mt-3"><div class="panel-header"><div><h2>Asset lifecycle history</h2><p>Append-only record of registration, custody, movement, maintenance, and changes.</p></div></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Event</th><th>Performed by</th><th>Details</th><th>Date and time</th></tr></thead><tbody>@foreach($assetHistories as $history)<tr><td><span class="status-pill">{{ str_replace('_',' ',$history->event) }}</span></td><td>{{ $history->actor?->name ?? 'System' }}</td><td class="small text-secondary">@if(!empty($history->new_values['current_employee_id']))Custodian ID: {{ $history->new_values['current_employee_id'] }}@elseif(!empty($history->new_values['transfer_id']))Transfer #{{ $history->new_values['transfer_id'] }}@else{{ $history->new_values['file_name'] ?? $history->new_values['disposal_no'] ?? $history->new_values['disposal_status'] ?? $history->new_values['status'] ?? $history->new_values['condition'] ?? $history->new_values['department_id'] ?? $history->new_values['location_id'] ?? $history->new_values['supplier_id'] ?? $history->new_values['asset_tag'] ?? 'Record updated' }}@endif</td><td>{{ $history->created_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }}</td></tr>@endforeach</tbody></table></div></div>
@endif
@if($resource==='assets' && $assignmentHistory->isNotEmpty())
<div class="panel mt-3"><div class="panel-header"><div><h2>Custody and assignment history</h2><p>Historical issuance and return records.</p></div></div><div class="table-responsive"><table class="table data-table mb-0"><thead><tr><th>Reference</th><th>Custodian</th><th>Issued</th><th>Returned</th><th>Status</th><th></th></tr></thead><tbody>@foreach($assignmentHistory as $assignment)<tr><td class="mono">{{ $assignment->reference_no }}</td><td>{{ $assignment->employee?->full_name }}</td><td>{{ $assignment->issued_at?->format(config('app.date_format', 'd M Y')) }}</td><td>{{ $assignment->returned_at?->format(config('app.date_format', 'd M Y'))??'—' }}</td><td><span class="status-pill {{ $assignment->status }}">{{ $assignment->status }}</span></td><td><a class="btn btn-sm btn-outline-secondary" href="{{ route('assignments.receipt',$assignment) }}" target="_blank">Receipt</a></td></tr>@endforeach</tbody></table></div></div>
@endif
@if($resource==='assets')
<style>
    .asset-label-print {
        box-sizing:border-box; display:flex; align-items:center; gap:8px;
        width:320px; height:192px; max-width:100%; margin:0 auto; padding:10px;
        background:#fff; color:#17243a; border:1px solid #d9e0ea;
        border-radius:6px; text-align:left; overflow:hidden;
    }
    .asset-label-print .qr-holder {
        box-sizing:border-box; flex:0 0 112px; width:112px; height:112px;
        display:flex; align-items:center; justify-content:center; overflow:hidden;
    }
    .asset-label-print .qr-holder img,
    .asset-label-print .qr-holder canvas {
        display:block; width:108px !important; height:108px !important;
        max-width:none; margin:0 auto;
    }
    .asset-label-info { flex:1 1 auto; min-width:0; overflow:hidden; }
    .asset-label-heading { font-size:9px; line-height:1.1; font-weight:800; letter-spacing:.6px; margin-bottom:4px; }
    .asset-label-tag { display:block; font-size:13px; line-height:1.15; overflow-wrap:anywhere; }
    .asset-label-name { font-size:9px; line-height:1.2; font-weight:600; margin-top:3px; overflow-wrap:anywhere; }
    .asset-label-detail { font-size:7px; line-height:1.15; color:#475569; margin-top:2px; overflow-wrap:anywhere; }
    .asset-label-barcode { margin-top:4px; max-width:100%; overflow:hidden; }
    .asset-label-barcode svg { display:block; width:100%; max-width:150px; height:28px; }
    @media print {
        @page { size:50mm 30mm; margin:0; }
        html, body { width:50mm !important; height:30mm !important; min-width:0 !important; margin:0 !important; padding:0 !important; background:#fff !important; }
        body * { visibility:hidden !important; }
        #qrModal, #qrModal * { visibility:visible !important; }
        #qrModal { position:absolute !important; left:0 !important; top:0 !important; display:block !important; width:50mm !important; height:30mm !important; margin:0 !important; padding:0 !important; overflow:hidden !important; background:#fff !important; }
        #qrModal .modal-dialog { width:50mm !important; max-width:50mm !important; height:30mm !important; margin:0 !important; padding:0 !important; transform:none !important; }
        #qrModal .modal-content { width:50mm !important; height:30mm !important; border:0 !important; border-radius:0 !important; box-shadow:none !important; }
        #qrModal .modal-header, #qrModal .modal-footer, #printAssetLabelBtn { display:none !important; }
        #qrModal .modal-body { width:50mm !important; height:30mm !important; margin:0 !important; padding:0 !important; }
        #assetLabelPrint {
            width:50mm !important; height:30mm !important; max-width:none !important;
            margin:0 !important; padding:1.5mm !important; gap:1.5mm !important;
            border:0 !important; border-radius:0 !important; overflow:hidden !important;
            break-inside:avoid; page-break-inside:avoid;
        }
        #assetLabelPrint .qr-holder { flex:0 0 18mm !important; width:18mm !important; height:18mm !important; }
        #assetLabelPrint .qr-holder img,
        #assetLabelPrint .qr-holder canvas { width:18mm !important; height:18mm !important; }
        #assetLabelPrint .asset-label-heading { font-size:5.5pt !important; margin-bottom:1mm !important; }
        #assetLabelPrint .asset-label-tag { font-size:8pt !important; line-height:1.05 !important; }
        #assetLabelPrint .asset-label-name { font-size:6pt !important; line-height:1.05 !important; margin-top:.5mm !important; }
        #assetLabelPrint .asset-label-detail { font-size:5pt !important; margin-top:.4mm !important; }
        #assetLabelPrint .asset-label-barcode { margin-top:.7mm !important; }
        #assetLabelPrint .asset-label-barcode svg { width:100% !important; max-width:27mm !important; height:5mm !important; }
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<script>
(function () {
    'use strict';

    // Assignment signature pad
    const canvas = document.getElementById('assignmentSignature');

    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.strokeStyle = '#17243a';
        ctx.lineWidth = 4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        let drawing = false;
        let ink = false;

        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: (event.clientX - rect.left) * canvas.width / rect.width,
                y: (event.clientY - rect.top) * canvas.height / rect.height
            };
        };

        canvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            ink = true;
            canvas.setPointerCapture(event.pointerId);
            const position = point(event);
            ctx.beginPath();
            ctx.moveTo(position.x, position.y);
        });

        canvas.addEventListener('pointermove', (event) => {
            if (!drawing) return;
            const position = point(event);
            ctx.lineTo(position.x, position.y);
            ctx.stroke();
        });

        const stopDrawing = () => { drawing = false; };
        canvas.addEventListener('pointerup', stopDrawing);
        canvas.addEventListener('pointercancel', stopDrawing);

        document.getElementById('clearAssignmentSignature')?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ink = false;
        });

        canvas.closest('form')?.addEventListener('submit', () => {
            const signatureInput = document.getElementById('assignmentSignatureData');
            if (signatureInput) {
                signatureInput.value = ink ? canvas.toDataURL('image/png') : '';
            }
        });
    }

    // Generate the QR code and barcode when the asset label modal opens.
    const qrModal = document.getElementById('qrModal');

    if (qrModal) {
        qrModal.addEventListener('shown.bs.modal', function () {
            const qrElement = document.getElementById('assetQr');
            const barcodeElement = document.getElementById('assetBarcode');

            if (qrElement && !qrElement.dataset.ready) {
                qrElement.innerHTML = '';

                if (typeof QRCode === 'undefined') {
                    qrElement.innerHTML = '<span class="text-danger small">QR code library could not be loaded. Check your internet connection or CDN access.</span>';
                } else {
                    try {
                        new QRCode(qrElement, {
                            text: @json(route('inventory.show', ['resource' => 'assets', 'id' => $record->id])),
                            width: 180,
                            height: 180,
                            colorDark: '#17243a',
                            colorLight: '#ffffff',
                            correctLevel: QRCode.CorrectLevel.H
                        });
                        qrElement.dataset.ready = '1';
                    } catch (error) {
                        console.error('QR code generation failed:', error);
                        qrElement.innerHTML = '<span class="text-danger small">Unable to generate QR code.</span>';
                    }
                }
            }

            if (barcodeElement && !barcodeElement.dataset.ready) {
                if (typeof JsBarcode === 'undefined') {
                    console.error('JsBarcode library could not be loaded.');
                } else {
                    try {
                        JsBarcode(barcodeElement, @json($record->asset_tag), {
                            format: 'CODE128',
                            height: 48,
                            width: 1.6,
                            displayValue: true,
                            font: 'DM Mono',
                            margin: 4
                        });
                        barcodeElement.dataset.ready = '1';
                    } catch (error) {
                        console.error('Barcode generation failed:', error);
                    }
                }
            }

            const printButton = document.getElementById('printAssetLabelBtn');
            if (printButton) {
                printButton.disabled = !(qrElement && qrElement.querySelector('img, canvas'));
            }
        });
    }

    document.getElementById('printAssetLabelBtn')?.addEventListener('click', function () {
        const qrElement = document.getElementById('assetQr');
        if (!qrElement || !qrElement.querySelector('img, canvas')) {
            alert('The QR code is not ready yet. Please wait for it to finish generating.');
            return;
        }
        window.print();
    });
})();
</script>
@endif
@endsection
