@extends('layouts.app')
@section('title','Approval center')
@section('breadcrumb','Approval center')
@section('content')
<div class="page-heading"><div><div class="eyebrow">GOVERNANCE</div><h1>Approval center</h1><p>Review requests assigned to your account or authorized role.</p></div></div>
<div class="panel"><div class="panel-header"><div><h2>Pending workflow actions</h2><p>{{ $approvals->total() }} pending or queued approval tasks</p></div><span class="status-pill pending">Action required</span></div>
<div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Request</th><th>Record</th><th>Approver</th><th>Round / stage</th><th>Status</th><th class="text-end">Action</th></tr></thead><tbody>
@forelse($approvals as $approval)
<tr>
 <td><strong>{{ class_basename($approval->approvable_type) }}</strong><div class="table-sub">Submitted {{ $approval->created_at?->format(config('app.date_format', 'd M Y')) }}</div></td>
 <td><a href="{{ $approval->approvable_type === \App\Models\Procurement::class ? route('inventory.show',['procurements',$approval->approvable_id]) : route('inventory.show',['disposals',$approval->approvable_id]) }}">#{{ $approval->approvable_id }}</a></td>
 <td>{{ $approval->approver?->name ?? 'Not assigned' }}</td><td>{{ $approval->round ?? 1 }} / {{ $approval->sequence }}</td>
 <td><span class="status-pill {{ $approval->status }}">{{ str_replace('_',' ',$approval->status) }}</span></td>
 <td class="text-end">
 @if($approval->status==='pending' && ($approval->approver_id===auth()->id() || auth()->user()->hasPermission('approvals.override')))
  <div class="d-flex justify-content-end gap-1 flex-wrap">
   <form method="POST" action="{{ route('approvals.decide',$approval) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="btn btn-sm btn-primary">Approve</button></form>
   <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#revision-{{ $approval->id }}">Return for revision</button>
   <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-{{ $approval->id }}">Reject</button>
  </div>
  <div class="modal fade" id="revision-{{ $approval->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('approvals.decide',$approval) }}" class="modal-content">@csrf<input type="hidden" name="decision" value="revision_requested"><div class="modal-header"><h5 class="modal-title">Return for revision</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Required revision notes</label><textarea class="form-control" name="remarks" rows="3" required></textarea></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-warning">Return request</button></div></form></div></div>
  <div class="modal fade" id="reject-{{ $approval->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('approvals.decide',$approval) }}" class="modal-content">@csrf<input type="hidden" name="decision" value="rejected"><div class="modal-header"><h5 class="modal-title">Reject approval</h5><button class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Reason / remarks</label><textarea class="form-control" name="remarks" rows="3" required></textarea></div><div class="modal-footer"><button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button><button class="btn btn-danger">Confirm rejection</button></div></form></div></div>
 @else<span class="small-muted">{{ $approval->status==='waiting'?'Awaiting prior stage':'No action available' }}</span>@endif
 </td>
</tr>
@empty<tr><td colspan="6" class="empty-state">There are no pending approval steps.</td></tr>@endforelse
</tbody></table></div><div class="panel-footer">{{ $approvals->links() }}</div></div>
@endsection
