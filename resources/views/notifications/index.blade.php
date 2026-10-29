@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Notifications | ElevateHer360')
@section('content')

<style>
.notification-row{cursor:pointer}
.notification-row:hover td{background:#fbf7f4}
.notification-row:focus-visible{outline:2px solid #800000;outline-offset:-2px}
.notification-select{cursor:default}
.notification-row.unread td:nth-child(2) strong::before{content:'';display:inline-block;width:8px;height:8px;margin-right:8px;border-radius:50%;background:#800000;vertical-align:middle}
.notification-row.read td{color:#667085}
</style>

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Account</span>
    <h1>Notifications</h1>
    <p>Review updates and actions that need your attention.</p>
</div>

<div class="admin-page-actions">
    <x-export-buttons />
    @if($unreadCount>0)
    <form method="POST" action="{{ route('notifications.read-all') }}">
        @csrf @method('PATCH')
        <button class="btn btn-outline"><i class="fas fa-check-double"></i> Mark All Read</button>
    </form>
    @endif
</div>
</div>

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-calendar-week"></i></span><div><small>This Week</small><strong>{{ number_format($stats['week']) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-calendar-days"></i></span><div><small>This Month</small><strong>{{ number_format($stats['month']) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-layer-group"></i></span><div><small>Total</small><strong>{{ number_format($stats['total']) }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-bell"></i></span><div><small>Unread</small><strong>{{ number_format($unreadCount) }}</strong></div></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" data-notification-filters>
<input type="search" name="search" value="{{ request('search') }}" placeholder="Search title or message..." aria-label="Search notifications">
<select name="period" aria-label="Period" data-notification-period>
    <option value="">All time</option>
    <option value="today" @selected(request('period')==='today')>Today</option>
    <option value="week" @selected(request('period')==='week')>This week</option>
    <option value="month" @selected(request('period')==='month')>This month</option>
    <option value="year" @selected(request('period')==='year')>This year</option>
    <option value="custom" @selected(request('period')==='custom')>Custom range</option>
</select>
<input type="date" name="from" value="{{ request('from') }}" title="From date" aria-label="From date" data-notification-custom @unless(request('period')==='custom') hidden @endunless>
<input type="date" name="to" value="{{ request('to') }}" title="To date" aria-label="To date" data-notification-custom @unless(request('period')==='custom') hidden @endunless>
<select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <option value="unread" @selected(request('status')==='unread')>Unread</option>
    <option value="read" @selected(request('status')==='read')>Read</option>
</select>
<select name="per_page" aria-label="Rows per page">
    @foreach([10,15,25,50] as $n)
        <option value="{{ $n }}" @selected($perPage===$n)>{{ $n }}/page</option>
    @endforeach
</select>
<button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
<a href="{{ route('notifications.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>
<script>
document.querySelector('[data-notification-period]')?.addEventListener('change', function () {
    document.querySelectorAll('[data-notification-custom]').forEach(el => { el.hidden = this.value !== 'custom'; });
});
</script>

@if($notifications->total() > 0)
<p class="admin-cell-hint" style="margin:0 0 10px">Showing {{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ number_format($notifications->total()) }} notifications</p>
@endif

@include('partials.admin-bulk-bar', ['bulkRoute' => route('notifications.bulk-destroy'), 'bulkTableId' => 'notificationsTable'])
<div class="admin-table-wrap">
<table class="admin-table" id="notificationsTable">
<thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#notificationsTable-bar" aria-label="Select all notifications on this page"></th><th>Notification</th><th>Type</th><th>Received</th><th>Status</th><th class="table-actions">Details</th></tr></thead>
<tbody>
@forelse($notifications as $notification)
<tr class="notification-row {{ $notification->read_at ? 'read' : 'unread' }}" id="notification-item-{{ $notification->id }}" data-modal-open="notification-{{ $notification->id }}" tabindex="0" aria-haspopup="dialog" aria-label="View notification: {{ $notification->title }}">
    <td class="notification-select"><input type="checkbox" data-row-select value="{{ $notification->id }}" aria-label="Select {{ $notification->title }}"></td>
    <td><strong>{{ $notification->title }}</strong>@if($notification->message)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($notification->message, 120) }}</small>@endif</td>
    <td>{{ $notification->type ? ucfirst(str_replace('_',' ',$notification->type)) : 'Notification' }}</td>
    <td title="{{ optional($notification->created_at)->format('d M Y H:i') }}">{{ optional($notification->created_at)->diffForHumans() }}</td>
    <td><span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}" data-notification-status>{{ $notification->read_at ? 'Read' : 'Unread' }}</span></td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="notification-{{ $notification->id }}"><i class="fas fa-eye"></i> View</button>
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty"><i class="fas fa-bell-slash"></i><strong>No notifications</strong><span>You are all caught up.</span></div></td></tr>
@endforelse
</tbody>
</table>
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
