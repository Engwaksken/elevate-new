@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Notifications | ElevateHer360')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Account</span>
    <h1>Notifications</h1>
    <p>Review updates and actions that need your attention.</p>
</div>

@if($unreadCount>0)
<div class="admin-page-actions">
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf
        <button class="btn btn-outline"><i class="fas fa-check-double"></i> Mark All Read</button>
    </form>
</div>
@endif
</div>

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-bell"></i></span><div><small>Unread Notifications</small><strong>{{ number_format($unreadCount) }}</strong></div></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<select name="status">
    <option value="">All notifications</option>
    <option value="unread" @selected(request('status')==='unread')>Unread</option>
    <option value="read" @selected(request('status')==='read')>Read</option>
</select>
<button class="btn btn-primary btn-sm">Apply</button>
<a href="{{ route('notifications.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="notification-list">
@forelse($notifications as $notification)
<article class="notification-item {{ $notification->read_at ? 'read' : 'unread' }}">
    <div class="notification-icon"><i class="fas fa-bell"></i></div>
    <div class="notification-content">
        <div class="notification-heading">
            <strong>{{ $notification->title }}</strong>
            <small>{{ optional($notification->created_at)->diffForHumans() }}</small>
        </div>
        @if($notification->message)<p>{{ $notification->message }}</p>@endif
        <span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span>
    </div>
    <div class="notification-actions">
        @if(!$notification->read_at)
        <form method="POST" action="{{ route('notifications.read',$notification) }}">
            @csrf
            <button class="btn btn-primary btn-sm">{{ $notification->action_url ? 'Open' : 'Mark Read' }}</button>
        </form>
        @elseif($notification->action_url)
        <a href="{{ $notification->action_url }}" class="btn btn-outline btn-sm">Open</a>
        @endif
    </div>
</article>
@empty
<div class="admin-empty"><i class="fas fa-bell-slash"></i><strong>No notifications</strong><span>You are all caught up.</span></div>
@endforelse
</div>

<div class="admin-pagination">{{ $notifications->links() }}</div>
</div>
@endsection
