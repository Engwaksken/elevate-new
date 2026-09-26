@extends('layouts.admin')
@section('title','Notifications | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">System Administration</span>
    <h1>Notification History</h1>
    <p>Review notifications generated for participants and staff.</p>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Notifications','fa-bell'],
['unread','Unread','fa-envelope'],
['read','Read','fa-envelope-open'],
['today','Created Today','fa-calendar-day']
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search title, message, type or recipient...">
    </div>

    <select name="type">
        <option value="">All types</option>
        @foreach($types as $type)
        <option value="{{ $type }}" @selected(request('type')===$type)>{{ ucfirst(str_replace('_',' ',$type)) }}</option>
        @endforeach
    </select>

    <select name="read_status">
        <option value="">All read statuses</option>
        <option value="unread" @selected(request('read_status')==='unread')>Unread</option>
        <option value="read" @selected(request('read_status')==='read')>Read</option>
    </select>

    <select name="per_page">
        @foreach([10,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Recipient</th><th>Type</th><th>Notification</th><th>Created</th><th>Read</th><th>Action URL</th></tr></thead>
<tbody>
@forelse($notifications as $notification)
<tr>
    <td><strong>{{ data_get($notification,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($notification,'user.email','') }}</small></td>
    <td>{{ ucfirst(str_replace('_',' ',$notification->type)) }}</td>
    <td><strong>{{ $notification->title }}</strong><small class="admin-cell-hint">{{ Str::limit($notification->message,100) }}</small></td>
    <td>{{ optional($notification->created_at)->format('d M Y H:i') }}</td>
    <td><span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span></td>
    <td>{{ $notification->action_url ?: '—' }}</td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No notifications found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $notifications->links() }}</div>
</div>
@endsection
