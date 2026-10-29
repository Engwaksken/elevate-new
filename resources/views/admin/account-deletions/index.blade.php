@extends('layouts.admin')
@section('title','Account deletion requests | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Administration</span>
    <h1>Account deletion requests</h1>
    <p>Participants ask to delete their account from the mobile app or the public account deletion page. Complete each request within {{ \App\Models\AccountDeletionRequest::DAYS_TO_PROCESS }} days.</p>
</div>
<div class="admin-page-actions">
    <a class="btn btn-outline" href="{{ route('account-deletion.show') }}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i> Public page</a>
</div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="admin-stats-grid">
@foreach([['pending','Waiting','fa-hourglass-half'],['overdue','Due within 5 days','fa-triangle-exclamation'],['completed','Completed','fa-user-xmark'],['rejected','Rejected','fa-circle-xmark']] as [$key,$label,$icon])
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($counts[$key]) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search name or email..." aria-label="Search requests"></div>
    <select name="status" aria-label="Status">
        <option value="" @selected($status === null)>All requests</option>
        @foreach(['pending'=>'Waiting','completed'=>'Completed','rejected'=>'Rejected'] as $value=>$label)
            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="period" aria-label="Requested" data-period-select>
        @foreach(\App\Support\PeriodFilter::OPTIONS as $value => $label)
            <option value="{{ $value }}" @selected(request('period', '') === $value)>{{ $value === '' ? 'Any time' : 'Requested: '.$label }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
    <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a class="btn btn-outline btn-sm" href="{{ route('admin.account-deletions.index') }}">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Participant</th><th>Reason</th><th>Source</th><th>Requested</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($requests as $item)
@php($due = $item->created_at->copy()->addDays(\App\Models\AccountDeletionRequest::DAYS_TO_PROCESS))
<tr>
    <td><strong>{{ $item->name }}</strong><small class="admin-cell-hint">{{ $item->email }}</small></td>
    <td>{{ $item->reason ?: '-' }}</td>
    <td>{{ $item->source === 'app' ? 'Mobile app' : 'Website' }}</td>
    <td>{{ $item->created_at->format('d M Y') }}@if($item->status === 'pending')<small class="admin-cell-hint" @if($due->isPast() || $due->diffInDays(now()) <= 5) style="color:#b42318" @endif>Due by {{ $due->format('d M Y') }}</small>@endif</td>
    <td>
        <span class="status-chip {{ $item->status }}">{{ ['pending'=>'Waiting','completed'=>'Completed','rejected'=>'Rejected'][$item->status] ?? ucfirst($item->status) }}</span>
        @if($item->processed_at)<small class="admin-cell-hint">{{ $item->processor?->name }} · {{ $item->processed_at->format('d M Y') }}</small>@endif
        @if($item->admin_note)<small class="admin-cell-hint">{{ $item->admin_note }}</small>@endif
    </td>
    <td class="table-actions">
        @if($item->status === 'pending')
            <div class="action-group">
                <button type="button" class="btn btn-danger btn-sm" data-modal-open="complete-deletion-{{ $item->id }}"><i class="fas fa-user-xmark"></i> Delete account</button>
                <button type="button" class="btn btn-outline btn-sm" data-modal-open="reject-deletion-{{ $item->id }}"><i class="fas fa-xmark"></i> Reject</button>
            </div>
        @else
            <span class="admin-cell-hint">-</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">{{ $status === 'pending' ? 'No requests are waiting.' : 'No requests found.' }}</div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $requests->links() }}</div>
</div>

@foreach($requests->where('status', 'pending') as $item)
<div class="eh-modal" id="complete-deletion-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="complete-deletion-{{ $item->id }}-title">
    <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('admin.account-deletions.complete', $item) }}">@csrf
        <div class="eh-modal-header"><div><h2 id="complete-deletion-{{ $item->id }}-title">Delete this account?</h2><p>{{ $item->name }} · {{ $item->email }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body">
            <p>This permanently removes the participant's name, contact details, profile, documents, messages and reading history, and signs them out everywhere. Course, certificate, attendance and feedback records are kept without their identity. This cannot be undone.</p>
            <div class="modal-grid"><div class="form-group full"><label for="complete-note-{{ $item->id }}">Note (optional)</label><textarea id="complete-note-{{ $item->id }}" name="note" maxlength="1000" rows="2"></textarea></div></div>
            <label class="modal-check"><input type="checkbox" name="confirm" value="1" required> <span>I have checked this is the right account.</span></label>
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-danger"><i class="fas fa-user-xmark"></i> Delete account</button></div>
    </form></div>
</div>
<div class="eh-modal" id="reject-deletion-{{ $item->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="reject-deletion-{{ $item->id }}-title">
    <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('admin.account-deletions.reject', $item) }}">@csrf
        <div class="eh-modal-header"><div><h2 id="reject-deletion-{{ $item->id }}-title">Reject request</h2><p>{{ $item->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full"><label for="reject-note-{{ $item->id }}">Reason shown to the participant *</label><textarea id="reject-note-{{ $item->id }}" name="note" required maxlength="1000" rows="3"></textarea></div></div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Back</button><button class="btn btn-primary">Reject request</button></div>
    </form></div>
</div>
@endforeach

<script>
document.querySelectorAll('[data-period-select]').forEach(function (select) {
    select.addEventListener('change', function () {
        select.form.querySelectorAll('[data-period-custom]').forEach(function (input) { input.hidden = select.value !== 'custom'; });
    });
});
</script>
@endsection
