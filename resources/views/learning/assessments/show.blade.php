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

@php($effectiveDue = $dueState['effective_due'] ?? null)
@php($extension = $dueState['extension'] ?? null)
@php($isOverdue = (bool) ($dueState['is_overdue'] ?? false))
@if($effectiveDue)
<section class="card" aria-labelledby="due-date-title" style="margin-top:12px">
    <h2 id="due-date-title" style="font-size:1.05rem;margin:0 0 6px"><i class="fas fa-clock"></i> Due date</h2>
    <p style="margin:0">
        <strong>{{ $effectiveDue->format('d M Y H:i') }}</strong>
        @if($isOverdue)
            <span class="status-chip inactive">Overdue</span>
        @elseif($effectiveDue->lte(now()->addHours(\App\Services\Participant\ParticipantAssignmentService::EXTENSION_WINDOW_HOURS)))
            <span class="status-chip draft">Due {{ $effectiveDue->diffForHumans() }}</span>
        @endif
        @if($extension && $extension->status === 'approved' && $extension->approved_due_at)
            <small class="text-muted">(extended from {{ $assessment->due_at?->format('d M Y H:i') ?? 'no due date' }})</small>
        @endif
    </p>

    @if($extension)
    <div style="margin-top:10px">
        <strong>Your latest extension request:</strong>
        <span class="status-chip {{ ['pending' => 'draft', 'approved' => 'active', 'rejected' => 'inactive'][$extension->status] ?? 'draft' }}">{{ $extension->status === 'rejected' ? 'Not approved' : ucfirst($extension->status) }}</span>
        <small class="text-muted">Sent {{ $extension->created_at?->format('d M Y H:i') }}@if($extension->requested_due_at) · asked for {{ $extension->requested_due_at->format('d M Y H:i') }}@endif</small>
        @if($extension->isPending())
            <p class="text-muted" style="margin:4px 0 0">Your instructor has been notified. You will get a notification when they respond.</p>
        @endif
        @if($extension->approved_due_at)
            <p style="margin:4px 0 0">New due date: <strong>{{ $extension->approved_due_at->format('d M Y H:i') }}</strong></p>
        @endif
        @if($extension->reviewer_note)
            <p style="margin:4px 0 0">Instructor note: {{ $extension->reviewer_note }}</p>
        @endif
    </div>
    @endif

    @if($isOverdue)
        <p style="margin:10px 0 0">This assignment is past its due date, so it can no longer be submitted. @if($dueState['can_request_extension'] ?? false)You can ask your instructor for more time below.@endif</p>
    @endif

    @if($dueState['can_request_extension'] ?? false)
    <details style="margin-top:12px" @if($isOverdue || $errors->has('reason') || $errors->has('requested_due_at') || $errors->has('extension')) open @endif>
        <summary style="cursor:pointer"><strong>Request a deadline extension</strong></summary>
        <form method="POST" action="{{ route('learning.assessment.extension-request', $assessment) }}" style="margin-top:10px">
            @csrf
            <label for="extension_reason" style="display:block"><strong>Why do you need more time? *</strong></label>
            <textarea id="extension_reason" name="reason" rows="4" minlength="10" maxlength="1000" required placeholder="Explain briefly what has stopped you from submitting on time.">{{ old('reason') }}</textarea>
            <label for="extension_requested_due_at" style="display:block;margin-top:10px"><strong>New due date you are asking for</strong> <small>(optional)</small></label>
            <input id="extension_requested_due_at" type="datetime-local" name="requested_due_at" value="{{ old('requested_due_at') }}" min="{{ now()->format('Y-m-d\TH:i') }}">
            <p class="text-muted" style="margin:6px 0 10px;font-size:.85rem">Your instructor decides the final new due date.</p>
            <button type="submit"><i class="fas fa-paper-plane"></i> Send request</button>
        </form>
    </details>
    @endif
</section>
@endif

@unless($isOverdue)
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
@endunless
</div>
@endsection
