@extends('layouts.admin')
@section('title','Notifications | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">System Administration</span>
    <h1>Notification History</h1>
    <p>Review sent notifications or send a message to selected users and roles.</p>
</div>
<div class="admin-page-actions"><x-export-buttons />@if($canSend)<button type="button" class="btn btn-primary" data-modal-open="sendTargetedNotification"><i class="fas fa-paper-plane"></i> Send Notification</button>@endif</div>
</div>
@include('admin.shared.feedback')

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

@if($canSend)
<div class="eh-modal" id="sendTargetedNotification" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Send targeted notification</h2><p>Only the users and role members selected here will receive it.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
    <form method="POST" action="{{ route('admin.notifications.send') }}">@csrf
        <div class="eh-modal-body">
            <div class="modal-grid">
                <div class="form-group full"><label>Title *</label><input name="title" value="{{ old('title') }}" maxlength="190" required></div>
                <div class="form-group full"><label>Message *</label><textarea name="message" rows="4" maxlength="5000" required>{{ old('message') }}</textarea></div>
                <div class="form-group full"><label>Action link (optional)</label><input type="url" name="action_url" value="{{ old('action_url') }}" maxlength="2048" placeholder="https://..."></div>
                <div class="form-group"><label>Selected users</label><select name="user_ids[]" multiple size="8" style="height:auto;min-height:180px">
                    @foreach($recipientUsers as $recipient)
                        <option value="{{ $recipient->id }}" @selected(in_array((string)$recipient->id, array_map('strval', old('user_ids', [])), true))>{{ $recipient->name }} — {{ $recipient->email }} ({{ ucfirst($recipient->user_type) }})</option>
                    @endforeach
                </select><small class="form-hint">Use Ctrl/Command to select multiple individual users.</small></div>
                <div class="form-group"><label>Selected roles</label><div style="display:grid;gap:8px;max-height:180px;overflow:auto;padding:10px;border:1px solid var(--border-color,#d0d5dd);border-radius:8px">
                    @foreach($recipientRoles as $recipientRole)
                        <label style="display:flex;align-items:center;gap:8px;font-weight:400"><input type="checkbox" name="role_ids[]" value="{{ $recipientRole->id }}" @checked(in_array((string)$recipientRole->id, array_map('strval', old('role_ids', [])), true))>{{ $recipientRole->name }}</label>
                    @endforeach
                </div><small class="form-hint">Role members are resolved at send time; overlapping selections are deduplicated.</small></div>
            </div>
            @error('recipients')<p class="form-error">{{ $message }}</p>@enderror
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send to selected recipients</button></div>
    </form>
</div>
</div>
@endif

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Recipient</th><th>Type</th><th>Notification</th><th>Created</th><th>Read</th><th class="table-actions">Details</th></tr></thead>
<tbody>
@forelse($notifications as $notification)
<tr>
    <td><strong>{{ data_get($notification,'user.name','—') }}</strong><small class="admin-cell-hint">{{ data_get($notification,'user.email','') }}</small></td>
    <td>{{ ucfirst(str_replace('_',' ',$notification->type)) }}</td>
    <td><strong>{{ $notification->title }}</strong><small class="admin-cell-hint">{{ Str::limit($notification->message,100) }}</small></td>
    <td>{{ optional($notification->created_at)->format('d M Y H:i') }}</td>
    <td><span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span></td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="notification-{{ $notification->id }}"><i class="fas fa-eye"></i> View</button>
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No notifications found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $notifications->links() }}</div>
</div>

@foreach($notifications as $notification)
<div class="eh-modal" id="notification-{{ $notification->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="notification-{{ $notification->id }}-title">
<div class="eh-modal-dialog eh-modal-sm">
    <div class="eh-modal-header">
        <div>
            <h2 id="notification-{{ $notification->id }}-title">{{ $notification->title }}</h2>
            <p>{{ ucfirst(str_replace('_',' ',$notification->type)) }}</p>
        </div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <dl class="audit-meta" style="grid-template-columns:repeat(2,minmax(0,1fr))">
            <div><dt>Recipient</dt><dd>{{ data_get($notification,'user.name','—') }}@if(data_get($notification,'user.email'))<small>{{ data_get($notification,'user.email') }}</small>@endif</dd></div>
            <div><dt>Status</dt><dd><span class="status-chip {{ $notification->read_at ? 'active' : 'pending' }}">{{ $notification->read_at ? 'Read' : 'Unread' }}</span>@if($notification->read_at)<small>{{ $notification->read_at->format('d M Y H:i') }}</small>@endif</dd></div>
            <div><dt>Created</dt><dd>{{ optional($notification->created_at)->format('d M Y H:i') }}</dd></div>
            <div><dt>Linked page</dt><dd>@if($notification->action_url)<a href="{{ $notification->action_url }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($notification->action_url, 60) }}</a>@else — @endif</dd></div>
        </dl>
        <p class="notification-detail-message">{{ $notification->message ?: 'No message body.' }}</p>
    </div>
    <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Close</button></div>
</div>
</div>
@endforeach
@endsection
