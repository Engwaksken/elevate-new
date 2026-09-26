@extends('layouts.admin')
@section('title','Attendance | ElevateHer360')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Instructor</span>
    <h1>Attendance — {{ $course->title }}</h1>
    <p>Create a session and record attendance for enrolled participants.</p>
</div>
@if(Route::has('instructor.dashboard'))
<div class="admin-page-actions">
<a href="{{ route('instructor.dashboard') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Instructor Dashboard</a>
</div>
@endif
</div>

<div class="admin-stats-grid compact">
@foreach([
['learners','Enrolled Learners','fa-users'],
['sessions','Sessions','fa-calendar-check'],
['present','Present Records','fa-user-check'],
['absent','Absent Records','fa-user-xmark']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<div class="admin-dashboard-grid">

<section class="admin-panel">
<div class="admin-panel-head">
<div><h2>Record Attendance</h2><p>Every learner listed below is enrolled in this course.</p></div>
</div>

<form method="POST" action="{{ route('instructor.attendance.store',$course) }}">
@csrf

<div class="eh-form-grid">
<div class="full"><label>Session Title *</label><input name="title" value="{{ old('title') }}" required placeholder="e.g. Week 2 practical session"></div>
<div><label>Date *</label><input type="date" name="session_date" value="{{ old('session_date',now()->format('Y-m-d')) }}" required></div>
<div><label>Cohort</label><select name="cohort_id"><option value="">All enrolled learners</option>@foreach($course->cohorts as $cohort)<option value="{{ $cohort->id }}" @selected((string)old('cohort_id')===(string)$cohort->id)>{{ $cohort->name }}</option>@endforeach</select></div>
<div><label>Starts At</label><input type="time" name="starts_at" value="{{ old('starts_at') }}"></div>
<div><label>Ends At</label><input type="time" name="ends_at" value="{{ old('ends_at') }}"></div>
<div class="full"><label>Venue</label><input name="venue" value="{{ old('venue') }}" placeholder="Physical venue or online session"></div>
</div>

<div class="attendance-toolbar">
<div>
<button type="button" class="btn btn-outline btn-sm" data-attendance-all="present">Mark all Present</button>
<button type="button" class="btn btn-outline btn-sm" data-attendance-all="absent">Mark all Absent</button>
</div>
<span>{{ $learners->count() }} learner(s)</span>
</div>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Learner</th><th>Email</th><th>Status</th></tr></thead>
<tbody>
@forelse($learners as $enrolment)
<tr>
<td><strong>{{ $enrolment->user->name }}</strong></td>
<td>{{ $enrolment->user->email }}</td>
<td>
<select name="attendance[{{ $enrolment->user_id }}]" class="attendance-status" required>
@foreach(['present'=>'Present','absent'=>'Absent','late'=>'Late','excused'=>'Excused'] as $value=>$label)
<option value="{{ $value }}" @selected(old("attendance.{$enrolment->user_id}",'present')===$value)>{{ $label }}</option>
@endforeach
</select>
</td>
</tr>
@empty
<tr><td colspan="3"><div class="admin-empty">No participants are enrolled in this course.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

@if($learners->count())
<div class="eh-form-actions">
<button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Attendance</button>
</div>
@endif
</form>
</section>

<section class="admin-panel">
<div class="admin-panel-head">
<div><h2>Recent Sessions</h2><p>Latest attendance sessions for this course.</p></div>
</div>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Session</th><th>Date</th><th>Time</th><th>Venue</th><th>Records</th></tr></thead>
<tbody>
@forelse($recentSessions as $session)
<tr>
<td><strong>{{ $session->title }}</strong></td>
<td>{{ optional($session->session_date)->format('d M Y') }}</td>
<td>{{ $session->starts_at ?: '—' }}{{ $session->ends_at ? ' – '.$session->ends_at : '' }}</td>
<td>{{ $session->venue ?: '—' }}</td>
<td>{{ $session->records_count }}</td>
</tr>
@empty
<tr><td colspan="5"><div class="admin-empty">No attendance sessions recorded yet.</div></td></tr>
@endforelse
</tbody>
</table>
</div>
</section>

</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('[data-attendance-all]').forEach(function(button){
        button.addEventListener('click',function(){
            document.querySelectorAll('.attendance-status').forEach(function(select){
                select.value=button.dataset.attendanceAll;
            });
        });
    });
});
</script>
@endsection
