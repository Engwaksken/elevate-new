@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Notifications | ElevateHer360')
@section('content')

<style>
/* Show notifications four per row (two on tablets, one on phones). */
.notification-list{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.notification-item{grid-template-columns:1fr;align-items:stretch;gap:10px}
.notification-item .notification-actions{justify-content:flex-end}
@media(max-width:1100px){.notification-list{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.notification-list{grid-template-columns:1fr}}
</style>

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Account</span>
    <h1>Notifications</h1>
    <p>Review updates and actions that need your attention.</p>
</div>

@if($unreadCount>0)
<div class="admin-page-actions">
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf @method('PATCH')
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
<article class="notification-item is-clickable {{ $notification->read_at ? 'read' : 'unread' }}" id="notification-item-{{ $notification->id }}" data-modal-open="notification-{{ $notification->id }}" tabindex="0" role="button" aria-haspopup="dialog" aria-label="View notification: {{ $notification->title }}">
    <div class="notification-icon"><i class="fas fa-bell"></i></div>
    <div class="notification-content">
        <div class="notification-heading">
            <strong>{{ $notification->title }}</strong>
            <small>{{ optional($notification->created_at)->diffForHumans() }}</small>
        </div>
        @if($notification->message)<p>{{ \Illuminate\Support\Str::limit($notification->message, 160) }}</p>@endif
        <span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}" data-notification-status>{{ $notification->read_at ? 'Read' : 'Unread' }}</span>
    </div>
    <div class="notification-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="notification-{{ $notification->id }}"><i class="fas fa-eye"></i> View</button>
    </div>
</article>
@empty
<div class="admin-empty"><i class="fas fa-bell-slash"></i><strong>No notifications</strong><span>You are all caught up.</span></div>
@endforelse
</div>

@foreach($notifications as $notification)
<div class="eh-modal" id="notification-{{ $notification->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="notification-{{ $notification->id }}-title"
     @unless($notification->read_at) data-notification-read-url="{{ route('notifications.read', $notification) }}" data-notification-item="notification-item-{{ $notification->id }}" data-notification-token="{{ csrf_token() }}" @endunless>
<div class="eh-modal-dialog eh-modal-sm">
    <div class="eh-modal-header">
        <div>
            <h2 id="notification-{{ $notification->id }}-title">{{ $notification->title }}</h2>
            <p>{{ $notification->type ? ucfirst(str_replace('_',' ',$notification->type)) : 'Notification' }}</p>
        </div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <p class="notification-detail-message">{{ $notification->message ?: 'No further details were provided.' }}</p>
        <div class="notification-detail-meta">
            <span><i class="fas fa-clock"></i>{{ optional($notification->created_at)->format('d M Y H:i') }}</span>
            <span><i class="fas fa-envelope-open"></i>{{ $notification->read_at ? 'Read '.$notification->read_at->diffForHumans() : 'Unread' }}</span>
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Close</button>
        @if($notification->action_url)
            <form method="POST" action="{{ route('notifications.read',$notification) }}">
                @csrf @method('PATCH')
                <button class="btn btn-primary"><i class="fas fa-arrow-up-right-from-square"></i> Open</button>
            </form>
        @endif
    </div>
</div>
</div>
@endforeach

<div class="admin-pagination">{{ $notifications->links() }}</div>
</div>
@endsection
