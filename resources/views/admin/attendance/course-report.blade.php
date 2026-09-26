@extends('layouts.admin')
@section('title','Course Attendance Report | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Monitoring & Attendance</span><h1>Course Attendance Report</h1><p>Filter and export daily course attendance across courses and cohorts.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.course-attendance-report.csv',request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Export CSV</a></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['sessions','Sessions','fa-calendar-check'],['records','Attendance Records','fa-clipboard-user'],['present','Present / Late','fa-user-check'],['rate','Attendance Rate','fa-percent']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ $k==='rate' ? number_format((float)($stats[$k]??0),1).'%' : number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar eh-filter-row">
<div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search participant name or email..."></div>
<select name="course_id"><option value="">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)request('course_id')===(string)$course->id)>{{ $course->title }}</option>@endforeach</select>
<select name="cohort_id"><option value="">All cohorts</option>@foreach($cohorts as $cohort)<option value="{{ $cohort->id }}" @selected((string)request('cohort_id')===(string)$cohort->id)>{{ $cohort->name }}</option>@endforeach</select>
<select name="status"><option value="">All statuses</option>@foreach(['present','late','absent','excused'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}" title="From date">
<input type="date" name="to" value="{{ request('to') }}" title="To date">
<button class="btn btn-primary btn-sm">Apply</button>
<a href="{{ route('admin.course-attendance-report.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Date</th><th>Session</th><th>Participant</th><th>Status</th><th>Remarks</th></tr></thead>
<tbody>
@forelse($records as $record)
<tr>
<td>{{ optional($record->session?->session_date)->format('d M Y') ?: '—' }}</td>
<td><strong>{{ $record->session?->title ?: 'Session' }}</strong><small class="admin-cell-hint">Course #{{ $record->session?->course_id }} · Cohort #{{ $record->session?->cohort_id ?: '—' }}</small></td>
<td><strong>{{ $record->user?->name ?: '—' }}</strong><small class="admin-cell-hint">{{ $record->user?->email }}</small></td>
<td><span class="status-chip {{ in_array($record->status,['present','late']) ? 'active':'inactive' }}">{{ ucfirst($record->status) }}</span></td>
<td>{{ $record->remarks ?: '—' }}</td>
</tr>
@empty<tr><td colspan="5"><div class="admin-empty">No attendance records found for the selected filters.</div></td></tr>@endforelse
</tbody>
</table></div>

<div class="admin-pagination">{{ $records->links() }}</div>
</div>
@endsection
