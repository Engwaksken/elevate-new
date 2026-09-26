@extends('layouts.admin')
@section('title','Reminder Delivery Logs | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Event Reminders</span><h1>{{ $event->title }}</h1><p>Delivery history for automatic event reminders.</p></div><div class="admin-page-actions"><a href="{{ route('admin.events.view',$event) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Event View</a></div></div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<select name="channel"><option value="">All channels</option><option value="email" @selected(request('channel')==='email')>Email</option><option value="system" @selected(request('channel')==='system')>System</option></select>
<select name="status"><option value="">All statuses</option><option value="sent" @selected(request('status')==='sent')>Sent</option><option value="failed" @selected(request('status')==='failed')>Failed</option></select>
<button class="btn btn-primary btn-sm">Apply</button>
</form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>User</th><th>Channel</th><th>Reminder</th><th>Sent At</th><th>Status</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td><strong>{{ $log->name ?: 'User' }}</strong><small class="admin-cell-hint">{{ $log->email }}</small></td><td>{{ ucfirst($log->channel) }}</td><td>{{ $log->minutes_before }} minutes before</td><td>{{ $log->sent_at }}</td><td>@if($log->error_message)<span class="status-chip inactive">Failed</span><small class="admin-cell-hint">{{ Str::limit($log->error_message,90) }}</small>@else<span class="status-chip active">Sent</span>@endif</td></tr>
@empty<tr><td colspan="5"><div class="admin-empty">No reminder delivery logs yet.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $logs->links() }}</div>
</div>
@endsection
