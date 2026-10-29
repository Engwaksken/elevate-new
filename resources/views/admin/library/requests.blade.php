@extends('layouts.admin')
@section('title','Library download requests | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Digital Library</span>
    <h1>Download requests</h1>
    <p>Readers ask to download books marked "Download on request". Approved downloads stay available for {{ \App\Models\LibraryDownloadRequest::APPROVAL_DAYS }} days.</p>
</div>
<div class="admin-page-actions">
    <a class="btn btn-outline" href="{{ route('admin.library.index') }}"><i class="fas fa-arrow-left"></i> Library</a>
</div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="admin-stats-grid compact">
@foreach([['pending','Waiting for a reply','fa-hourglass-half'],['approved','Approved','fa-circle-check'],['declined','Declined','fa-circle-xmark']] as [$key,$label,$icon])
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($counts[$key]) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search reader or book..." aria-label="Search requests"></div>
    <select name="status" aria-label="Status">
        <option value="" @selected($status === null)>All requests</option>
        @foreach(['pending'=>'Waiting for a reply','approved'=>'Approved','declined'=>'Declined'] as $value=>$label)
            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.library.requests.index') }}">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Reader</th><th>Book</th><th>Reason</th><th>Requested</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($requests as $item)
<tr>
    <td><strong>{{ $item->user?->name ?? 'Deleted user' }}</strong><small class="admin-cell-hint">{{ $item->user?->email }}</small></td>
    <td><strong>{{ $item->resource?->title ?? 'Removed book' }}</strong><small class="admin-cell-hint">{{ $item->resource?->author }}</small></td>
    <td>{{ $item->reason ?: '-' }}</td>
    <td>{{ $item->created_at->format('d M Y H:i') }}</td>
    <td>
        <span class="status-chip {{ $item->status }}">{{ ['pending'=>'Waiting','approved'=>'Approved','declined'=>'Declined'][$item->status] ?? ucfirst($item->status) }}</span>
        @if($item->decided_at)<small class="admin-cell-hint">{{ $item->decider?->name }} · {{ $item->decided_at->format('d M Y') }}@if($item->status === 'approved' && $item->expires_at) · until {{ $item->expires_at->format('d M Y') }}@endif</small>@endif
        @if($item->decision_note)<small class="admin-cell-hint">{{ $item->decision_note }}</small>@endif
    </td>
    <td class="table-actions">
        @if($item->status === 'pending')
            <div class="action-group">
                <button type="button" class="btn btn-primary btn-sm" data-modal-open="approve-request-{{ $item->id }}"><i class="fas fa-check"></i> Approve</button>
                <button type="button" class="btn btn-outline btn-sm" data-modal-open="decline-request-{{ $item->id }}"><i class="fas fa-xmark"></i> Decline</button>
            </div>
        @else
            <span class="admin-cell-hint">-</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">{{ $status === 'pending' ? 'No requests are waiting for a reply.' : 'No requests found.' }}</div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $requests->links() }}</div>
</div>

@foreach($requests->where('status', 'pending') as $item)
<div class="eh-modal" id="approve-request-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="approve-request-{{ $item->id }}-title">
    <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('admin.library.requests.approve', $item) }}">@csrf
        <div class="eh-modal-header"><div><h2 id="approve-request-{{ $item->id }}-title">Approve download</h2><p>{{ $item->user?->name }} · {{ $item->resource?->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full"><label for="approve-note-{{ $item->id }}">Note to reader (optional)</label><textarea id="approve-note-{{ $item->id }}" name="note" maxlength="1000" rows="3"></textarea></div></div>
            <small class="form-hint">The reader is notified and can download for {{ \App\Models\LibraryDownloadRequest::APPROVAL_DAYS }} days.</small></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-check"></i> Approve</button></div>
    </form></div>
</div>
<div class="eh-modal" id="decline-request-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="decline-request-{{ $item->id }}-title">
    <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('admin.library.requests.decline', $item) }}">@csrf
        <div class="eh-modal-header"><div><h2 id="decline-request-{{ $item->id }}-title">Decline request</h2><p>{{ $item->user?->name }} · {{ $item->resource?->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full"><label for="decline-note-{{ $item->id }}">Reason *</label><textarea id="decline-note-{{ $item->id }}" name="note" required maxlength="1000" rows="3" placeholder="For example: this book is for online reading only because of copyright."></textarea></div></div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Back</button><button class="btn btn-danger"><i class="fas fa-xmark"></i> Decline</button></div>
    </form></div>
</div>
@endforeach
@endsection
