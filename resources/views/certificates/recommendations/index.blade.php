@extends('layouts.admin')
@section('title','Certificate Recommendations | ElevateHer360')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Certificates</span>
    <h1>Certificate Recommendations</h1>
    <p>{{ $canApprove ? 'Review recommendations from instructors and programme staff, and track issued certificates.' : 'Track the participants you have recommended for certificates.' }}</p>
</div>
<div class="admin-page-actions">
    @if($canApprove && Route::has('admin.elearning.certificates.templates.index'))
        <a href="{{ route('admin.elearning.certificates.templates.index') }}" class="btn btn-outline"><i class="fas fa-image"></i> Templates</a>
    @endif
    <a href="{{ route('certificates.recommendations.create') }}" class="btn btn-primary"><i class="fas fa-award"></i> {{ $canApprove ? 'Recommend / issue' : 'Recommend participants' }}</a>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
    ['pending','Awaiting Review','fa-hourglass-half'],
    ['approved','Approved & Issued','fa-circle-check'],
    ['rejected','Not Approved','fa-circle-xmark'],
    ['total','All Recommendations','fa-award'],
] as [$key,$label,$icon])
<a class="admin-stat" href="{{ route('certificates.recommendations.index', $key === 'total' ? [] : ['status' => $key]) }}">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div>
</a>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search participant, ID, course or event...">
    </div>
    <select name="status">
        <option value="">All statuses</option>
        <option value="pending" @selected(request('status')==='pending')>Awaiting review</option>
        <option value="approved" @selected(request('status')==='approved')>Approved</option>
        <option value="rejected" @selected(request('status')==='rejected')>Not approved</option>
    </select>
    <select name="context">
        <option value="">Courses &amp; events</option>
        <option value="course" @selected(request('context')==='course')>Courses</option>
        <option value="event" @selected(request('context')==='event')>Events</option>
    </select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('certificates.recommendations.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@if($canApprove)
<form method="POST" action="{{ route('certificates.recommendations.review') }}" id="cert-review-bulk">
    @csrf
    <div class="admin-bulk-bar" id="cert-bulk-bar">
        <strong><span data-selected-count>0</span> selected</strong>
        <div style="display:flex;gap:8px">
            <button type="submit" name="decision" value="approve" class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Approve &amp; issue</button>
            <button type="button" class="btn btn-outline btn-sm" data-modal-open="cert-bulk-reject"><i class="fas fa-xmark"></i> Reject</button>
        </div>
    </div>
</form>
@endif

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    @if($canApprove)<th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#cert-bulk-bar" aria-label="Select all pending"></th>@endif
    <th>Participant</th>
    <th>Course / Event</th>
    <th>Recommended by</th>
    <th>Status</th>
    <th class="table-actions">Details</th>
</tr>
</thead>
<tbody>
@forelse($recommendations as $recommendation)
<tr>
    @if($canApprove)
    <td>
        @if($recommendation->isPending())
            <input type="checkbox" value="{{ $recommendation->id }}" data-row-select aria-label="Select {{ $recommendation->user?->name }}">
        @endif
    </td>
    @endif
    <td><strong>{{ $recommendation->user?->name }}</strong><small class="admin-cell-hint">{{ $recommendation->user?->participant_code ?? '—' }} · {{ $recommendation->user?->email }}</small></td>
    <td>
        <span class="cert-context"><i class="fas {{ $recommendation->context_type === 'event' ? 'fa-calendar-days' : 'fa-graduation-cap' }}"></i> {{ $recommendation->context_type === 'event' ? 'Event' : 'Course' }}</span>
        <strong>{{ $recommendation->contextTitle() }}</strong>
    </td>
    <td>{{ $recommendation->recommender?->name ?? '—' }}<small class="admin-cell-hint">{{ $recommendation->created_at?->format('d M Y') }}</small></td>
    <td>
        @include('certificates.recommendations._status', ['status' => $recommendation->status])
        @if($recommendation->reviewed_at)<small class="admin-cell-hint">{{ $recommendation->reviewer?->name }} · {{ $recommendation->reviewed_at->format('d M Y') }}</small>@endif
    </td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="cert-rec-{{ $recommendation->id }}">
            <i class="fas {{ $canApprove && $recommendation->isPending() ? 'fa-gavel' : 'fa-eye' }}"></i> {{ $canApprove && $recommendation->isPending() ? 'Review' : 'View' }}
        </button>
    </td>
</tr>
@empty
<tr><td colspan="{{ $canApprove ? 6 : 5 }}"><div class="admin-empty"><i class="fas fa-award"></i><strong>No recommendations yet</strong><span>Recommend participants who have earned a certificate.</span></div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $recommendations->links() }}</div>
</div>

@if($canApprove)
<div class="eh-modal" id="cert-bulk-reject" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cert-bulk-reject-title">
<div class="eh-modal-dialog eh-modal-sm">
    <div class="eh-modal-header">
        <div><h2 id="cert-bulk-reject-title">Reject selected recommendations</h2><p>The recommender is notified with your reason.</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <div class="modal-grid"><div class="form-group full">
            <label for="cert-bulk-notes">Reason</label>
            <textarea id="cert-bulk-notes" name="review_notes" form="cert-review-bulk" rows="4" maxlength="2000"></textarea>
        </div></div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button type="submit" form="cert-review-bulk" name="decision" value="reject" class="btn btn-primary">Reject selected</button>
    </div>
</div>
</div>
@endif

@foreach($recommendations as $recommendation)
@php $reviewable = $canApprove && $recommendation->isPending(); @endphp<div class="eh-modal" id="cert-rec-{{ $recommendation->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cert-rec-{{ $recommendation->id }}-title">
<div class="eh-modal-dialog eh-modal-sm">
<form method="POST" action="{{ route('certificates.recommendations.review') }}">
    @csrf
    <input type="hidden" name="ids[]" value="{{ $recommendation->id }}">
    <div class="eh-modal-header">
        <div>
            <h2 id="cert-rec-{{ $recommendation->id }}-title">{{ $recommendation->user?->name }}</h2>
            <p>{{ $recommendation->context_type === 'event' ? 'Event' : 'Course' }} · {{ $recommendation->contextTitle() }}</p>
        </div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        <dl class="audit-meta" style="grid-template-columns:repeat(2,minmax(0,1fr))">
            <div><dt>Status</dt><dd>@include('certificates.recommendations._status', ['status' => $recommendation->status])</dd></div>
            <div><dt>Recommended</dt><dd>{{ $recommendation->recommender?->name ?? '—' }}<small>{{ $recommendation->created_at?->format('d M Y H:i') }}</small></dd></div>
            @if($recommendation->reviewed_at)
                <div><dt>Reviewed by</dt><dd>{{ $recommendation->reviewer?->name ?? '—' }}<small>{{ $recommendation->reviewed_at->format('d M Y H:i') }}</small></dd></div>
                <div><dt>Certificate</dt><dd>{{ $recommendation->certificate?->certificate_number ?? ($recommendation->event_certificate_id ? 'Event certificate issued' : '—') }}</dd></div>
            @endif
        </dl>
        <div class="cert-note"><strong>Reason for recommendation</strong><p>{{ $recommendation->reason ?: 'No reason given.' }}</p></div>
        @if($recommendation->review_notes)
            <div class="cert-note"><strong>Reviewer notes</strong><p>{{ $recommendation->review_notes }}</p></div>
        @endif
        @if($reviewable)
            <div class="modal-grid"><div class="form-group full">
                <label for="cert-rec-{{ $recommendation->id }}-notes">Review notes <span class="form-hint" style="display:inline">(required to reject)</span></label>
                <textarea id="cert-rec-{{ $recommendation->id }}-notes" name="review_notes" rows="3" maxlength="2000"></textarea>
            </div></div>
        @endif
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Close</button>
        @if($reviewable)
            <button type="submit" name="decision" value="reject" class="btn btn-outline"><i class="fas fa-xmark"></i> Reject</button>
            <button type="submit" name="decision" value="approve" class="btn btn-primary"><i class="fas fa-check"></i> Approve &amp; issue</button>
        @endif
    </div>
</form>
</div>
</div>
@endforeach

@if($canApprove)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('cert-review-bulk');
    if (!form) return;

    form.addEventListener('submit', function () {
        form.querySelectorAll('input[name="ids[]"]').forEach(function (input) { input.remove(); });
        document.querySelectorAll('[data-row-select]:checked').forEach(function (box) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = box.value;
            form.appendChild(input);
        });
    });

    const master = document.querySelector('[data-select-all]');
    const boxes = [...document.querySelectorAll('[data-row-select]')];
    const sync = function () {
        const selected = boxes.filter(function (b) { return b.checked; });
        if (master) master.checked = boxes.length > 0 && selected.length === boxes.length;
        if (master) master.indeterminate = selected.length > 0 && selected.length < boxes.length;
        const count = document.querySelector('[data-selected-count]');
        if (count) count.textContent = String(selected.length);
        const bar = document.getElementById('cert-bulk-bar');
        if (bar) bar.classList.toggle('is-visible', selected.length > 0);
    };
    if (master) master.addEventListener('change', function () { boxes.forEach(function (b) { b.checked = master.checked; }); sync(); });
    boxes.forEach(function (box) { box.addEventListener('change', sync); });
    sync();
});
</script>
@endif
@endsection
