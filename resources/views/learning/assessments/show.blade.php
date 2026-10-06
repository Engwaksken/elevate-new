@extends('layouts.app')
@section('content')
<div class="card">
<h1>{{ $assessment->title }}</h1>
<p>{{ $assessment->instructions }}</p>

@if($briefFiles->isNotEmpty())
<section aria-labelledby="brief-files-title">
    <h2 id="brief-files-title" style="font-size:1.05rem;margin:12px 0 4px"><i class="fas fa-paperclip"></i> {{ ucfirst($assessment->type) }} files</h2>
    <p class="text-muted" style="margin:0 0 6px;font-size:.85rem">Excel/CSV and zip files can be downloaded. Other files open in the viewer and are view-only.</p>
    <x-learning.file-list :files="$briefFiles" />
</section>
@endif

@if($previousAttempts->isNotEmpty())
<section aria-labelledby="previous-attempts-title" style="margin-top:12px">
    <h2 id="previous-attempts-title" style="font-size:1.05rem;margin:12px 0 4px">Your previous submissions</h2>
    @foreach($previousAttempts as $previous)
        <div class="card" style="margin:8px 0">
            <strong>Attempt {{ $previous->attempt_number }}</strong>
            <small> · {{ ucfirst($previous->status) }} · {{ $previous->submitted_at?->format('d M Y H:i') }}</small>
            @if($previous->submission_text)<p style="white-space:pre-wrap">{{ $previous->submission_text }}</p>@endif
            <x-learning.file-list compact :files="$previous->files" />
        </div>
    @endforeach
</section>
@endif

@if($errors->any())
<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ route('learning.assessment.submit',$assessment) }}">
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

@if($assessment->type === 'assignment')
<div class="card">
    <label for="submission_text"><strong>Your response</strong></label>
    <textarea id="submission_text" name="submission_text" rows="6">{{ old('submission_text') }}</textarea>
    <x-learning.multi-file-input name="submission_files" label="Upload your files" mimes="submission_mimes" style="margin-top:12px" />
</div>
@endif

<button>Submit {{ $assessment->type === 'assignment' ? 'Assignment' : 'Assessment' }}</button>
</form>
</div>
@endsection
