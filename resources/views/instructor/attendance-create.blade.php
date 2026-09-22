@extends('layouts.app')
@section('content')
<div class="card"><h1>Attendance — {{ $course->title }}</h1>
<form method="POST" action="{{ route('instructor.attendance.store',$course) }}">@csrf
<label>Session title</label><input name="title" required>
<label>Date</label><input type="date" name="session_date" required>
<label>Venue</label><input name="venue">
<table width="100%" cellpadding="8"><tr><th align="left">Learner</th><th>Status</th></tr>
@foreach($learners as $enrolment)
<tr><td>{{ $enrolment->user->name }}</td><td><select name="attendance[{{ $enrolment->user_id }}]"><option value="present">Present</option><option value="absent">Absent</option><option value="late">Late</option><option value="excused">Excused</option></select></td></tr>
@endforeach
</table>
<button>Save Attendance</button>
</form></div>
@endsection
