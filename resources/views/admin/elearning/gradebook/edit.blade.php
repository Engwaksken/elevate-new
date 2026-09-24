@extends('layouts.admin')
@section('content')
<div class="card"><h1>Grade Attempt #{{ $attempt->id }}</h1>
<form method="POST" action="{{ route('admin.elearning.gradebook.update',$attempt) }}">@csrf @method('PUT')
@foreach($attempt->assessment->questions as $question)
@php($answer=$answers->get($question->id))
<div class="card">
<strong>{{ $question->question_text }}</strong>
<p>Answer: {{ $answer?->answer_text }}</p>
<label>Marks / {{ $question->marks }}</label><input type="number" step="0.1" name="marks[{{ $question->id }}]" value="{{ $answer?->awarded_marks ?? 0 }}">
<label>Feedback</label><textarea name="feedback[{{ $question->id }}]">{{ $answer?->grader_feedback }}</textarea>
</div>
@endforeach
<button>Save Grade</button>
</form></div>
@endsection
