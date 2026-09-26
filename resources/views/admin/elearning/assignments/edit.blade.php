@extends('layouts.admin')
@section('title','Edit Course Assignment | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Course Assignment</span><h1>{{ $course->title }}</h1><p>Choose instructors, lead instructor and cohorts for this course.</p></div><div class="admin-page-actions"><a href="{{ route('admin.elearning.assignments.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Assignments</a></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<form method="POST" action="{{ route('admin.elearning.assignments.update',$course) }}" class="admin-panel">@csrf @method('PUT')
<div class="assignment-section"><h2>Instructors</h2><div class="eh-choice-grid">@foreach($instructors as $instructor)<label class="eh-choice-card"><input type="checkbox" name="instructors[]" value="{{ $instructor->id }}" @checked($course->instructors->contains('id',$instructor->id))><span class="eh-choice-card__text">{{ $instructor->name }}</span></label>@endforeach</div></div>
<div class="assignment-section"><label>Lead Instructor</label><select name="lead_instructor_id"><option value="">No lead instructor</option>@foreach($instructors as $instructor)<option value="{{ $instructor->id }}" @selected(optional($course->instructors->firstWhere('pivot.is_lead',true))->id===$instructor->id)>{{ $instructor->name }}</option>@endforeach</select><small class="form-hint">The selected lead instructor should also be ticked above.</small></div>
<div class="assignment-section"><h2>Cohorts</h2><div class="eh-choice-grid">@foreach($cohorts as $cohort)<label class="eh-choice-card"><input type="checkbox" name="cohorts[]" value="{{ $cohort->id }}" @checked($course->cohorts->contains('id',$cohort->id))><span class="eh-choice-card__text">{{ $cohort->name }}</span></label>@endforeach</div></div>
<div class="eh-form-actions"><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Assignments</button></div>
</form>
@endsection
