@extends('layouts.app')
@section('content')
<div class="card"><h1>Course Assignment: {{ $course->title }}</h1>
<form method="POST" action="{{ route('admin.elearning.assignments.update',$course) }}">@csrf @method('PUT')
<div class="card"><h3>Instructors</h3>
@foreach($instructors as $instructor)
<label style="display:block"><input style="width:auto" type="checkbox" name="instructors[]" value="{{ $instructor->id }}" @checked($course->instructors->contains($instructor->id))> {{ $instructor->name }}</label>
@endforeach
<label>Lead Instructor</label><select name="lead_instructor_id"><option value="">None</option>@foreach($instructors as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select>
</div>
<div class="card"><h3>Cohorts</h3>
@foreach($cohorts as $cohort)
<label style="display:block"><input style="width:auto" type="checkbox" name="cohorts[]" value="{{ $cohort->id }}" @checked($course->cohorts->contains($cohort->id))> {{ $cohort->name }}</label>
@endforeach
</div>
<button>Save Assignments</button>
</form></div>
@endsection
