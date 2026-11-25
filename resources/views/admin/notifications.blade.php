@extends('layouts.app')
@section('title','Notifications') @section('breadcrumb','Notifications')
@section('content')
<div class="page-heading"><div><div class="eyebrow">WORKSPACE</div><h1>Notifications</h1><p>System alerts, approval requests, low-stock notices, and workflow updates.</p></div><form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-light border">Mark all as read</button></form></div>
<div class="panel"><div class="table-responsive"><table class="table data-table align-middle mb-0"><thead><tr><th>Notification</th><th>Type</th><th>Date</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($notifications as $notification)<tr class="{{ $notification->read_at?'':'notification-unread' }}"><td><strong>{{ $notification->data['title'] ?? 'System notification' }}</strong><div class="table-sub">{{ $notification->data['message'] ?? '' }}</div></td><td>{{ ucfirst($notification->data['level'] ?? 'info') }}</td><td>{{ $notification->created_at?->format(config('app.date_format', 'd M Y').' '.config('app.time_format', 'H:i')) }}</td><td><span class="status-pill {{ $notification->read_at?'inactive':'active' }}">{{ $notification->read_at?'Read':'Unread' }}</span></td><td class="text-end"><form method="POST" action="{{ route('notifications.read',$notification->id) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Open</button></form></td></tr>
@empty<tr><td colspan="5" class="empty-state">You have no notifications.</td></tr>@endforelse
</tbody></table></div><div class="panel-footer">{{ $notifications->links() }}</div></div>
@endsection
