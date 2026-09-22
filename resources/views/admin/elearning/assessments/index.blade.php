@extends('layouts.app')
@section('content')
<div class="card"><h1>Assessments — {{ $course->title }}</h1>
<form method="POST" action="{{ route('admin.elearning.assessments.store',$course) }}">@csrf
<label>Title</label><input name="title" required>
<label>Type</label><select name="type"><option value="quiz">Quiz</option><option value="assignment">Assignment</option><option value="exam">Exam</option></select>
<label>Instructions</label><textarea name="instructions"></textarea>
<div class="grid"><div><label>Pass mark</label><input type="number" step="0.01" name="pass_mark" value="50"></div><div><label>Max attempts</label><input type="number" name="max_attempts" value="1"></div></div>
<label><input style="width:auto" type="checkbox" name="is_published" value="1"> Published</label><br><br>
<button>Create Assessment</button>
</form></div>
@foreach($assessments as $assessment)
<div class="card"><strong>{{ $assessment->title }}</strong> · {{ $assessment->questions_count }} questions
<br><a href="{{ route('admin.elearning.assessments.edit',[$course,$assessment]) }}">Build Questions</a></div>
@endforeach
@endsection
