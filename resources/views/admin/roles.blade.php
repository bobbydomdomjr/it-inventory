@extends('layouts.app')
@section('title','Roles & permissions')
@section('breadcrumb','Roles & permissions')
@section('content')
<div class="page-heading"><div><div class="eyebrow">ACCESS CONTROL</div><h1>Roles & permissions</h1><p>Configure module-level access using least-privilege assignments.</p></div></div>
<div class="alert alert-info border-0"><i class="bi bi-info-circle me-2"></i>The Super Administrator role always retains unrestricted access. Assign only the permissions needed for each operational role.</div>
<div class="row g-3">@foreach($roles as $role)<div class="col-12"><div class="panel"><form method="POST" action="{{ route('admin.roles.update',$role) }}">@csrf @method('PUT')<div class="panel-header role-panel-head"><div><h2>{{ $role->name }}</h2><p>{{ $role->description ?: 'No role description provided.' }} <span class="role-slug">{{ $role->slug }}</span></p></div><div class="d-flex align-items-center gap-2"><span class="small-muted">{{ $role->permissions->count() }} permissions</span>@if($role->slug==='super-admin')<span class="status-pill active">Full access</span>@else<button class="btn btn-sm btn-primary">Save permissions</button>@endif</div></div>
@if($role->slug!=='super-admin')<div class="permission-grid">@foreach($permissions as $module=>$items)<div class="permission-group"><div class="permission-group-title">{{ ucfirst($module) }}</div>@foreach($items as $permission)<label class="permission-item"><input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains('id',$permission->id))><span>{{ $permission->label }}</span></label>@endforeach</div>@endforeach</div>@else<div class="role-superadmin-note">This role bypasses permission checks by design. Keep its membership strictly limited.</div>@endif
</form></div></div>@endforeach</div>
@endsection
