@extends('layouts.admin')
@section('title','Participant Goals | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">People &amp; Performance</span>
        <h1>Participant Goals</h1>
        <p>Review every participant's personal goals and how far each one has progressed.</p>
    </div>
    <div class="admin-page-actions">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline"><i class="fas fa-users"></i> Users</a>
    </div>
</div>

@include('admin.shared.feedback')

<div class="admin-stats-grid compact">
@foreach([
    ['total','Total Goals','fa-flag'],
    ['in_progress','In Progress','fa-person-running'],
    ['completed','Completed','fa-trophy'],
    ['average','Average Progress','fa-chart-line'],
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ $key === 'average' ? number_format((float)$stats[$key],1).'%' : number_format($stats[$key]) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search participant, email, title or description..."></div>
    <select name="user_id">
        <option value="">All participants</option>
        @foreach($participants as $participant)
            <option value="{{ $participant->id }}" @selected((int)request('user_id')===$participant->id)>{{ $participant->name }}</option>
        @endforeach
    </select>
    <select name="status">
        <option value="">All statuses</option>
        @foreach(['not_started'=>'Not started','in_progress'=>'In progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l)
            <option value="{{ $v }}" @selected(request('status')===$v)>{{ $l }}</option>
        @endforeach
    </select>
    <select name="category">
        <option value="">All categories</option>
        @foreach(['career'=>'Career','learning'=>'Learning','personal'=>'Personal','mentorship'=>'Mentorship','other'=>'Other'] as $v=>$l)
            <option value="{{ $v }}" @selected(request('category')===$v)>{{ $l }}</option>
        @endforeach
    </select>
    <select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
    <a href="{{ route('admin.participant-goals.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@php
$bulkRoute = route('admin.participant-goals.bulk-destroy');
$bulkTableId = 'participantGoalsTable';
@endphp
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap">
<table class="admin-table" id="{{ $bulkTableId }}">
<thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Participant</th><th>Goal</th><th>Category</th><th>Progress</th><th>Status</th><th>Target date</th><th>Mentor feedback</th></tr></thead>
<tbody>
@forelse($goals as $goal)
<tr>
<td><input type="checkbox" data-row-select value="{{ $goal->id }}" aria-label="Select {{ $goal->user?->name ?? 'goal' }}"></td>
<td>
    <strong>{{ $goal->user?->name ?? 'Participant' }}</strong>
    <small class="admin-cell-hint">{{ $goal->user?->participant_code ?: $goal->user?->email }}</small>
</td>
<td>
    <strong>{{ $goal->title }}</strong>
    @if($goal->description)<small class="admin-cell-hint">{{ \Illuminate\Support\Str::limit($goal->description, 90) }}</small>@endif
    @if($goal->target_value !== null)
        <small class="admin-cell-hint">{{ rtrim(rtrim(number_format((float)$goal->current_value,2),'0'),'.') }} / {{ rtrim(rtrim(number_format((float)$goal->target_value,2),'0'),'.') }} {{ $goal->unit }}</small>
    @endif
</td>
<td>{{ ucfirst($goal->category) }}<small class="admin-cell-hint">{{ ucfirst($goal->priority) }} priority</small></td>
<td style="min-width:150px">
    <strong>{{ number_format((float)$goal->progress_percent,0) }}%</strong>
    <div style="height:6px;background:#eee;border-radius:6px;margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ max(0,min(100,(float)$goal->progress_percent)) }}%;background:#800000"></div></div>
</td>
<td><span class="status-chip {{ $goal->status }}">{{ ucfirst(str_replace('_',' ',$goal->status)) }}</span></td>
<td>{{ optional($goal->target_date)->format('d M Y') ?: '—' }}</td>
<td>
    @if($goal->mentor_comment)
        {{ \Illuminate\Support\Str::limit($goal->mentor_comment, 90) }}
        <small class="admin-cell-hint">{{ $goal->mentorReviewer?->name }}{{ $goal->mentor_reviewed_at ? ' · '.$goal->mentor_reviewed_at->format('d M Y') : '' }}</small>
    @else
        <span style="color:#98a2b3">—</span>
    @endif
</td>
</tr>
@empty
<tr><td colspan="8"><div class="admin-empty">No participant goals match these filters.</div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $goals->links() }}</div>
</div>
@endsection
