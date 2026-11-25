@extends('layouts.app')
@section('title','Physical inventory scan')
@section('breadcrumb','Physical Inventory')
@section('content')
<div class="page-heading"><div><div class="eyebrow">PHYSICAL INVENTORY · {{ $audit->audit_no }}</div><h1>Verify audit checklist</h1><p>{{ $audit->title }} · {{ $pendingItems->count() }} assets remaining to verify.</p></div><div class="heading-actions"><a class="btn btn-light border" href="{{ route('inventory.show',['audits',$audit->id]) }}">Back to audit</a></div></div>
<div class="row g-3"><div class="col-xl-5"><div class="panel p-3"><div class="panel-header"><div><h2>Camera scanner</h2><p>Scan the QR code or barcode printed on the asset label.</p></div></div><div class="scanner-frame"><video id="auditScannerVideo" playsinline></video><div class="scanner-guide"></div></div><div class="d-flex gap-2 mt-3"><button id="auditStartScanner" class="btn btn-primary" type="button">Start camera</button><button id="auditStopScanner" class="btn btn-light border" type="button" disabled>Stop camera</button></div><p id="auditScannerMessage" class="small text-secondary mt-3 mb-0">Camera access requires localhost or HTTPS and browser BarcodeDetector support.</p><hr><h3 class="h6">Remaining checklist</h3><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Asset</th><th>Tag</th></tr></thead><tbody>@forelse($pendingItems as $item)<tr><td>{{ $item->asset?->name ?? 'Asset record unavailable' }}</td><td class="mono">{{ $item->asset?->asset_tag ?? '—' }}</td></tr>@empty<tr><td colspan="2" class="text-secondary small">All registered checklist assets have been verified.</td></tr>@endforelse</tbody></table></div></div></div>
<div class="col-xl-7"><div class="panel p-3"><div class="panel-header"><div><h2>Record verification finding</h2><p>Capture observed location, custodian, condition, and evidence.</p></div></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('audits.verify',$audit) }}" enctype="multipart/form-data">@csrf
<div class="row g-3"><div class="col-md-6"><label class="form-label">Checklist asset</label><select class="form-select" name="asset_id" id="auditAssetSelect"><option value="">Select a registered asset</option>@foreach($pendingItems as $item)@if($item->asset)<option value="{{ $item->asset->id }}" data-tag="{{ $item->asset->asset_tag }}">{{ $item->asset->asset_tag }} — {{ $item->asset->name }}</option>@endif @endforeach</select></div><div class="col-md-6"><label class="form-label">Scanned asset tag / serial</label><input name="asset_tag" id="auditAssetTag" class="form-control" value="{{ old('asset_tag',request('tag')) }}" placeholder="Scan or enter identifier"></div>
<div class="col-md-6"><label class="form-label">Verification result</label><select name="result" class="form-select" required><option value="verified">Verified</option><option value="not_found">Not found</option><option value="transferred">Transferred</option><option value="damaged">Damaged</option><option value="wrong_location">Incorrect location</option><option value="wrong_custodian">Incorrect custodian</option><option value="unregistered">Unregistered</option></select></div>
<div class="col-md-6"><label class="form-label">Observed condition</label><select name="condition" class="form-select"><option value="">Not recorded</option><option>New</option><option>Good</option><option>Fair</option><option>Defective</option></select></div>
<div class="col-md-6"><label class="form-label">Observed location</label><select name="seen_location_id" class="form-select"><option value="">Not recorded</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Observed custodian</label><select name="seen_employee_id" class="form-select"><option value="">Not recorded</option>@foreach($employees as $employee)<option value="{{ $employee->id }}">{{ $employee->employee_no }} — {{ $employee->full_name }}</option>@endforeach</select></div>
<div class="col-12"><label class="form-label">Photo evidence (optional, max 5 MB)</label><input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
<div class="col-12"><label class="form-label">Findings / notes</label><textarea name="notes" class="form-control" rows="3" maxlength="2000">{{ old('notes') }}</textarea></div>
<div class="col-12 d-flex justify-content-end"><button type="submit" class="btn btn-primary">Save audit finding</button></div></div></form></div></div></div>
@endsection
@push('scripts')
<script>
(()=>{
 const video=document.getElementById('auditScannerVideo');
 const start=document.getElementById('auditStartScanner');
 const stop=document.getElementById('auditStopScanner');
 const message=document.getElementById('auditScannerMessage');
 const tagInput=document.getElementById('auditAssetTag');
 const select=document.getElementById('auditAssetSelect');
 let stream=null,active=false,detector=null;
 const shutdown=()=>{active=false;stream?.getTracks().forEach(track=>track.stop());stream=null;video.srcObject=null;start.disabled=false;stop.disabled=true;};
 stop.addEventListener('click',shutdown);
 start.addEventListener('click',async()=>{
   if(!('BarcodeDetector' in window)){message.textContent='This browser does not support native QR/barcode scanning. Enter the asset tag manually.';return;}
   try{
     detector=new BarcodeDetector({formats:['qr_code','code_128','code_39','ean_13']});
     stream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'}},audio:false});
     video.srcObject=stream;await video.play();active=true;start.disabled=true;stop.disabled=false;message.textContent='Camera active. Point it at the asset label.';
     const scan=async()=>{if(!active)return;try{const codes=await detector.detect(video);if(codes.length){const value=codes[0].rawValue;tagInput.value=value;const option=[...select.options].find(o=>o.dataset.tag===value);if(option)select.value=option.value;message.textContent='Identifier captured: '+value;shutdown();return;}}catch(error){}requestAnimationFrame(scan);};scan();
   }catch(error){message.textContent='Camera could not be started: '+error.message;shutdown();}
 });
 select.addEventListener('change',()=>{const option=select.selectedOptions[0];if(option?.dataset.tag)tagInput.value=option.dataset.tag;});
 window.addEventListener('pagehide',shutdown);
})();
</script>
@endpush
