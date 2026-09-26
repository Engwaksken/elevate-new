@extends('layouts.admin')
@section('title','Grade Attempt | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Grade Attempt</span><h1>{{ data_get($attempt,'assessment.title','Assessment') }}</h1><p>{{ data_get($attempt,'user.name','Learner') }} · Attempt #{{ $attempt->attempt_number }}</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.elearning.gradebook.index',$attempt->assessment->course_id) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Gradebook</a></div>
</div>

<form method="POST" action="{{ route('admin.elearning.gradebook.update',$attempt) }}">@csrf @method('PUT')
<div class="admin-panel">
@foreach($attempt->assessment->questions as $question)
@php($answer=$answers->get($question->id))
<article class="grading-card">
<div class="grading-head"><strong>{{ $loop->iteration }}. {{ $question->question_text }}</strong><span>{{ number_format((float)$question->marks,1) }} mark(s)</span></div>
<div class="grading-answer"><small>Learner answer</small><div>{{ is_array($answer?->answer) ? json_encode($answer?->answer) : ($answer?->answer ?? data_get($answer,'answer_text','—')) }}</div></div>
<div class="modal-grid">
<div class="form-group"><label>Awarded Marks</label><input type="number" step=".1" min="0" max="{{ $question->marks }}" name="marks[{{ $question->id }}]" value="{{ $answer?->awarded_marks ?? 0 }}"></div>
<div class="form-group full"><label>Feedback</label><textarea name="feedback[{{ $question->id }}]" rows="3">{{ $answer?->grader_feedback }}</textarea></div>
</div>
</article>
@endforeach
</div>
<div class="eh-form-actions"><a href="{{ route('admin.elearning.gradebook.index',$attempt->assessment->course_id) }}" class="btn btn-outline">Cancel</a><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Grade</button></div>
</form>
@endsection
