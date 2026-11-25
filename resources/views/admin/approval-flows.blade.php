@extends('layouts.app')
@section('title','Approval Flows')
@section('breadcrumb','Approval Flows')
@section('content')
<div class="page-heading"><div><div class="eyebrow">ADMINISTRATION</div><h1>Approval flows</h1><p>Configure reusable approval routing for procurement and asset disposal.</p></div></div>
<div class="row g-4">
 <div class="col-xl-4">
  <div class="panel"><div class="panel-header"><div><h2>Create approval flow</h2><p>One active flow per transaction type.</p></div></div>
   <form method="POST" action="{{ route('admin.approval-flows.store') }}" class="p-3">@csrf
    <div class="mb-3"><label class="form-label">Flow name</label><input class="form-control" name="name" maxlength="150" required placeholder="Standard procurement review"></div>
    <div class="mb-3"><label class="form-label">Applies to</label><select class="form-select" name="subject_type" required>@foreach($subjects as $class=>$label)<option value="{{ $class }}">{{ $label }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Default routing mode</label><select class="form-select" name="mode"><option value="sequential">Sequential</option><option value="parallel">Parallel</option></select><div class="form-text">Each step can override the mode.</div></div>
    <button class="btn btn-primary w-100">Create flow</button>
   </form>
  </div>
  <div class="panel mt-3"><div class="panel-header"><div><h2>Routing rules</h2><p>Sequential steps accept one authorized approver per step. Parallel steps require all assigned users in that step.</p></div></div><div class="p-3 small-muted">Escalation reminders are sent to Super Administrators after the configured number of hours. Escalation does not automatically approve a request.</div></div>
 </div>
 <div class="col-xl-8">
  @forelse($flows as $flow)
   <div class="panel mb-3">
    <div class="panel-header"><div><div class="d-flex flex-wrap align-items-center gap-2"><h2 class="mb-0">{{ $flow->name }}</h2><span class="status-pill {{ $flow->is_active?'approved':'inactive' }}">{{ $flow->is_active?'Active':'Inactive' }}</span></div><p>{{ $subjects[$flow->subject_type] ?? class_basename($flow->subject_type) }} · {{ ucfirst($flow->mode) }} routing</p></div>
     <div class="d-flex gap-2"><form method="POST" action="{{ route('admin.approval-flows.toggle',$flow) }}">@csrf @method('PATCH')<button class="btn btn-sm {{ $flow->is_active?'btn-outline-secondary':'btn-primary' }}">{{ $flow->is_active?'Deactivate':'Activate' }}</button></form>@if(!$flow->is_active)<form method="POST" action="{{ route('admin.approval-flows.destroy',$flow) }}" onsubmit="return confirm('Archive this flow?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Archive</button></form>@endif</div>
    </div>
    <div class="table-responsive"><table class="table table-sm data-table mb-0"><thead><tr><th>Step</th><th>Approver</th><th>Mode</th><th>Escalation</th><th>Revision</th><th class="text-end">Manage</th></tr></thead><tbody>
     @forelse($flow->steps as $step)<tr><td>{{ $step->sequence }}</td><td>{{ $step->approverUser?->name ?? ($step->approverRole?->name.' (role)') }}</td><td>{{ ucfirst($step->mode) }}</td><td>{{ $step->escalate_after_hours ? $step->escalate_after_hours.' hours' : '—' }}</td><td>{{ $step->allow_return_for_revision?'Allowed':'Not allowed' }}</td><td class="text-end">@if(!$flow->is_active)<form method="POST" action="{{ route('admin.approval-flows.steps.destroy',[$flow,$step]) }}" onsubmit="return confirm('Remove this step?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Remove</button></form>@else — @endif</td></tr>@empty<tr><td colspan="6" class="text-center text-secondary py-3">No steps configured.</td></tr>@endforelse
    </tbody></table></div>
    @if(!$flow->is_active)
    <form method="POST" action="{{ route('admin.approval-flows.steps.store',$flow) }}" class="border-top p-3">@csrf
     <div class="row g-2 align-items-end"><div class="col-sm-2"><label class="form-label">Step no.</label><input class="form-control" type="number" min="1" max="50" name="sequence" value="{{ ($flow->steps->max('sequence') ?? 0)+1 }}" required></div>
      <div class="col-sm-3"><label class="form-label">Approver type</label><select class="form-select approver-type" name="approver_type"><option value="user">User</option><option value="role">Role</option></select></div>
      <div class="col-sm-3 user-choice"><label class="form-label">User</label><select class="form-select" name="approver_user_id"><option value="">Select user</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select></div>
      <div class="col-sm-3 role-choice d-none"><label class="form-label">Role</label><select class="form-select" name="approver_role_id"><option value="">Select role</option>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
      <div class="col-sm-2"><label class="form-label">Mode</label><select class="form-select" name="mode"><option value="sequential" @selected($flow->mode==='sequential')>Sequential</option><option value="parallel" @selected($flow->mode==='parallel')>Parallel</option></select></div>
      <div class="col-sm-2"><label class="form-label">Escalate (hrs)</label><input class="form-control" type="number" min="1" max="8760" name="escalate_after_hours" placeholder="Optional"></div>
      <div class="col-sm-2"><button class="btn btn-primary w-100">Add step</button></div>
      <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="allow_return_for_revision" value="1" checked id="revision-{{ $flow->id }}"><label class="form-check-label" for="revision-{{ $flow->id }}">Allow return for revision</label></div></div>
     </div>
    </form>
    @endif
   </div>
  @empty<div class="panel p-4 text-center text-secondary">No approval flows have been configured.</div>@endforelse
  <div>{{ $flows->links() }}</div>
 </div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('.approver-type').forEach(function(select){select.addEventListener('change',function(){const form=select.closest('form');form.querySelector('.user-choice').classList.toggle('d-none',select.value!=='user');form.querySelector('.role-choice').classList.toggle('d-none',select.value!=='role');});});
</script>
@endpush
