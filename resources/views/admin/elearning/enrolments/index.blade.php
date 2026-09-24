@extends('layouts.admin')
@section('content')
<div class="card"><h1>Course Enrolments</h1>
<form method="GET"><select name="course_id"><option value="">All Courses</option>@foreach($courses as $course)<option value="{{ $course->id }}">{{ $course->title }}</option>@endforeach</select><button>Filter</button></form>
<table width="100%" cellpadding="8"><tr><th>Learner</th><th>Course</th><th>Status</th><th>Progress</th><th>Score</th></tr>
@foreach($enrolments as $e)<tr><td>{{ $e->user->name }}</td><td>{{ $e->course->title }}</td><td>{{ $e->status }}</td><td>{{ $e->progress_percent }}%</td><td>{{ $e->final_score }}</td></tr>@endforeach
</table>
{{ $enrolments->links() }}
</div>
@endsection
