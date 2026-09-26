@extends('layouts.admin')
@section('title',$courseCall->title.' | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Course Opportunity</span>
        <h1>{{ $courseCall->title }}</h1>

        <p>
            @if($courseCall->closes_at)
                Applications close {{ $courseCall->closes_at->format('d M Y H:i') }}
            @else
                Applications are currently open
            @endif
        </p>
    </div>

    <a
        href="{{ route('participant.course-calls.index') }}"
        class="btn btn-outline"
    >
        Back
    </a>
</div>

<div class="admin-panel">
    <h2>About this Course Call</h2>
    <p>{!! nl2br(e($courseCall->description ?: 'Course Call details will appear here.')) !!}</p>

    @if($courseCall->eligibility_criteria)
        <h3>Eligibility Criteria</h3>
        <p>{!! nl2br(e($courseCall->eligibility_criteria)) !!}</p>
    @endif
</div>

@if($courseCall->relationLoaded('courses') && $courseCall->courses->isNotEmpty())
<div class="admin-panel">
    <h2>Courses Available Under This Call</h2>

    <div class="eh-course-call-course-grid">
        @foreach($courseCall->courses as $course)
            <div class="eh-course-call-course-card">
                <i class="fas fa-graduation-cap"></i>
                <div>
                    <strong>{{ $course->title }}</strong>
                    <small>Available course</small>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<form
    method="POST"
    action="{{ route('participant.course-calls.save', $courseCall) }}"
>
    @csrf
    @method('PUT')

    @foreach($courseCall->questions as $question)
        @php
            $saved = $application->answers
                ->firstWhere('course_call_question_id', $question->id);
        @endphp

        <div class="admin-panel">
            <label>
                {{ $question->question_text }}
                {{ $question->is_required ? '*' : '' }}
            </label>

            @if(in_array($question->question_type,['single_choice','yes_no']))
                <select name="question_{{ $question->id }}">
                    <option value="">Select an answer</option>

                    @foreach(
                        $question->question_type === 'yes_no'
                            ? ['Yes','No']
                            : ($question->options ?? [])
                        as $option
                    )
                        <option
                            value="{{ $option }}"
                            @selected($saved?->answer_text === $option)
                        >
                            {{ $option }}
                        </option>
                    @endforeach
                </select>
            @elseif($question->question_type === 'multiple_choice')
                @foreach($question->options ?? [] as $option)
                    <label class="eh-checkbox-row">
                        <input
                            type="checkbox"
                            name="question_{{ $question->id }}[]"
                            value="{{ $option }}"
                            @checked(in_array($option,$saved?->answer_json ?? []))
                        >
                        <span>{{ $option }}</span>
                    </label>
                @endforeach
            @elseif($question->question_type === 'long_text')
                <textarea
                    name="question_{{ $question->id }}"
                    placeholder="Your answer"
                >{{ $saved?->answer_text }}</textarea>
            @else
                <input
                    type="{{ $question->question_type === 'number' ? 'number' : ($question->question_type === 'date' ? 'date' : 'text') }}"
                    name="question_{{ $question->id }}"
                    value="{{ $saved?->answer_text }}"
                    placeholder="Your answer"
                >
            @endif
        </div>
    @endforeach

    <div class="admin-panel eh-form-actions">
        <button
            name="submit"
            value="0"
            class="btn btn-outline"
        >
            Save Draft
        </button>

        <button
            name="submit"
            value="1"
            class="btn btn-primary"
        >
            Submit Application
        </button>
    </div>
</form>

@if($courseCall->entryAssessment)
    <div class="admin-panel">
        <h3>Entry Assessment</h3>
        <p>{{ $courseCall->entryAssessment->title }}</p>
        <p>Complete the entry assessment before final enrolment review.</p>
    </div>
@endif
@endsection
