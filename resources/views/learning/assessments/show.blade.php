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
            @foreach((array) $previous->submission_links as $link)<p><i class="fas fa-link"></i> <a href="{{ $link }}" target="_blank" rel="noopener noreferrer">{{ $link }}</a></p>@endforeach
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
    @php($maxFiles = $assessment->maxSubmissionFiles())
    <x-learning.multi-file-input name="submission_files" :label="$assessment->allow_link_submissions ? 'Upload files (optional)' : ($maxFiles === 1 ? 'Upload your file *' : 'Upload your files *')"
        :extensions="$assessment->submissionExtensions()" :max="$maxFiles" :required="!$assessment->allow_link_submissions"
        :hint="($assessment->allow_link_submissions ? 'Optional if you add a link below. ' : 'Required. ').'Accepted formats: '.$assessment->submissionFormatLabels().' ('.implode(', ', $assessment->submissionExtensions()).'). '.($maxFiles === 1 ? 'One file' : 'Up to '.$maxFiles.' files').', '.(int) round(((int) config('elearning.max_file_kb', 51200)) / 1024).' MB each.'" />
    @if($assessment->allow_link_submissions)
    <label for="submission_links" style="display:block;margin-top:12px"><strong>Submission links</strong> <small>(optional, one per line; add a file or link)</small></label>
    <textarea id="submission_links" name="submission_links" rows="3" placeholder="https://example.com/your-work">{{ old('submission_links') }}</textarea>
    @endif
    <label for="submission_text" style="display:block;margin-top:12px"><strong>Comments for your instructor</strong> <small>(optional)</small></label>
    <textarea id="submission_text" name="submission_text" rows="4">{{ old('submission_text') }}</textarea>
</div>
@endif

<button>Submit {{ $assessment->type === 'assignment' ? 'Assignment' : 'Assessment' }}</button>
</form>
</div>
@endsection
