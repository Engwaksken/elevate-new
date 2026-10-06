@extends('layouts.admin')
@section('title','Job Applications | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Jobs &amp; Opportunities</span>
        <h1>Job Applications</h1>
        <p>Track every application and follow up with employers through the pipeline.</p>
    </div>
    <div class="admin-page-actions"><x-export-buttons /></div>
</div>

<div class="admin-stats-grid compact">
    @foreach(['submitted','under_review','shortlisted','hired'] as $status)
        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas fa-file-signature"></i></span>
            <div><small>{{ ucfirst(str_replace('_',' ',$status)) }}</small><strong>{{ number_format($stats[$status] ?? 0) }}</strong></div>
        </div>
    @endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search applicant or job title...">
    </div>
    <select name="status">
        <option value="">All statuses</option>
        @foreach($statuses as $status)
            <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.jobs.applications.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>Applicant</th>
    <th>Job</th>
    <th>Employer</th>
    <th>Status</th>
    <th>Applied</th>
    <th>Progress</th>
</tr>
</thead>
<tbody>
@forelse($applications as $application)
<tr>
    <td>
        <strong>{{ $application->user?->name ?? '—' }}</strong>
        <small class="admin-cell-hint">{{ $application->user?->email }}</small>
    </td>
    <td>{{ $application->job?->title ?? '—' }}</td>
    <td>{{ $application->job?->employer?->company_name ?? '—' }}</td>
    <td><span class="status-chip {{ $application->status }}">{{ ucfirst(str_replace('_',' ',$application->status)) }}</span></td>
    <td>{{ optional($application->applied_at)->format('d M Y') ?: '—' }}</td>
    <td>
        @if($application->statusHistory->isNotEmpty())
            <span class="admin-cell-hint">{{ $application->statusHistory->pluck('status')->map(fn ($s) => ucfirst(str_replace('_', ' ', $s)))->join(' → ') }}</span>
        @else
            <span class="admin-cell-hint">—</span>
        @endif
    </td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No job applications found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $applications->links() }}</div>
</div>
@endsection
