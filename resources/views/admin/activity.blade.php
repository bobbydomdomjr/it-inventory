@extends('layouts.app')
@section('title','Audit trail')
@section('breadcrumb','Audit trail')
@section('content')
<div class="page-heading"><div><div class="eyebrow">GOVERNANCE</div><h1>Audit trail</h1><p>Recorded user actions and changes across inventory operations.</p></div></div>
<div class="panel"><div class="panel-header"><div><h2>Activity history</h2><p>{{ $logs->total() }} recorded events</p></div><span class="small-muted">Newest first</span></div><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Event</th><th>Module</th><th>User</th><th>Record</th><th>IP address</th><th>Date & time</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td><span class="event-name">{{ str_replace('_',' ',$log->action) }}</span></td><td>{{ ucfirst($log->module ?? 'system') }}</td><td>{{ $log->user?->name ?? 'System' }}</td><td class="mono small">{{ class_basename($log->record_type ?? '') }}{{ $log->record_id ? ' #'.$log->record_id : '' }}</td><td class="mono small">{{ $log->ip_address ?? '—' }}</td><td>{{ $log->created_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }}</td></tr>
@empty<tr><td colspan="6" class="empty-state">No activity has been recorded.</td></tr>@endforelse
</tbody></table></div><div class="panel-footer">{{ $logs->links() }}</div></div>
@endsection
