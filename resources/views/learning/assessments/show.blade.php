@extends('layouts.app')
@section('content')
<div class="card">
<h1>{{ $assessment->title }}</h1>
<p>{{ $assessment->instructions }}</p>
<form method="POST" action="{{ route('learning.assessment.submit',$assessment) }}">
@csrf
@foreach($assessment->questions as $question)
<div class="card">
<strong>{{ $loop->iteration }}. {{ $question->question_text }}</strong>
@if(in_array($question->question_type,['multiple_choice','true_false']))
@foreach($question->options ?? [] as $key => $option)
<label style="display:block;margin:8px 0"><input style="width:auto" type="radio" name="answers[{{ $question->id }}]" value="{{ is_string($key) ? $key : $option }}"> {{ $option }}</label>
@endforeach
@else
<textarea name="answers[{{ $question->id }}]"></textarea>
@endif
</div>
@endforeach
<button>Submit Assessment</button>
</form>
</div>
@endsection
