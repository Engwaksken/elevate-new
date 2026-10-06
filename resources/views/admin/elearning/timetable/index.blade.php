@extends('layouts.admin')
@section('title', 'Course Timetables | Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Learning</span><h1>Course Timetables</h1><p>See course sessions scheduled by instructors and trainers across all branches.</p></div><div class="admin-page-actions"><x-export-buttons /></div></div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<select name="course_id"><option value="">All courses</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->title }}</option>@endforeach</select>
<select name="branch_id"><option value="">All branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(request('branch_id') == $branch->id)>{{ $branch->name }}</option>@endforeach</select>
<label>From (UTC)<input type="date" name="from" value="{{ request('from') }}"></label>
<select name="status"><option value="">All statuses</option><option value="scheduled" @selected(request('status') === 'scheduled')>Scheduled</option><option value="cancelled" @selected(request('status') === 'cancelled')>Cancelled</option></select><button class="btn btn-primary">Filter</button>
</form>
<details><summary>Manage a course timetable</summary><div class="action-group">@foreach($courses as $course)@if(app(\App\Services\TimetableAccessService::class)->canManage(auth()->user(), $course))<a class="btn btn-outline btn-sm" href="{{ route('admin.elearning.timetable.manage', $course) }}">{{ $course->title }}</a>@endif @endforeach</div></details>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Course / branches</th><th>Session</th><th>Date & time</th><th>Added by</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($slots as $slot)
@php($session = app(\App\Services\CourseTimetableService::class)->present($slot))
<tr><td>{{ $slot->course?->title }}<small>{{ $slot->course?->branches->pluck('name')->join(', ') }}</small></td><td><strong>{{ $slot->title }}</strong><small>{{ $slot->venue }}</small>@if($slot->notes)<p>{{ $slot->notes }}</p>@endif</td><td>{{ $session['date_label'] }}<br>{{ $session['time_label'] }}<small>{{ $slot->timezone }}</small></td><td>{{ $slot->creator?->name ?: '—' }}</td><td>{{ ucfirst($slot->status) }}</td><td>@if($slot->course && app(\App\Services\TimetableAccessService::class)->canManage(auth()->user(), $slot->course))<a href="{{ route('admin.elearning.timetable.manage', $slot->course) }}">Manage</a>@endif</td></tr>
@empty<tr><td colspan="6">No sessions found.</td></tr>@endforelse
</tbody></table></div>{{ $slots->links() }}
</div>
@endsection
