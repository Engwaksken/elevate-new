@extends('layouts.app')

@section('title', $lesson->title.' | ElevateHer360')

@section('content')
<div class="card">
    <p>
        <a href="{{ route('learning.course.dashboard', $course) }}">
            <i class="fas fa-arrow-left"></i> Back to course
        </a>
    </p>

    <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap">
        <div>
            <h1>{{ $lesson->title }}</h1>
            <p class="text-muted">{{ $lesson->module?->title }}</p>
        </div>

        @php
            $offlineUrls = [request()->fullUrl()];

            if ($lesson->file_path) {
                $offlineUrls[] = \Illuminate\Support\Facades\Storage::disk('public')->url($lesson->file_path);
            }
        @endphp

        <button
            type="button"
            class="btn btn-outline"
            data-offline-download="{{ implode(',', $offlineUrls) }}"
            data-offline-key="lesson-{{ $lesson->id }}"
        >
            <i class="fas fa-download"></i>
            Download for offline use
        </button>
    </div>

    @if($lesson->video_url)
        <p>
            <a class="btn btn-outline" href="{{ $lesson->video_url }}" target="_blank" rel="noopener">
                <i class="fas fa-video"></i> Open video
            </a>
        </p>
    @endif

    @if($lesson->external_url)
        <p>
            <a class="btn btn-outline" href="{{ $lesson->external_url }}" target="_blank" rel="noopener">
                <i class="fas fa-link"></i> Open resource
            </a>
        </p>
    @endif

    @if($lesson->file_path)
        <p>
            <a
                class="btn btn-outline"
                href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($lesson->file_path) }}"
                target="_blank"
            >
                <i class="fas fa-file-arrow-down"></i> Open lesson file
            </a>
        </p>
    @endif

    <div class="lesson-content">
        {!! nl2br(e($lesson->content)) !!}
    </div>

    <hr>

    <form method="POST" action="{{ route('learning.lesson.complete', $lesson) }}">
        @csrf
        <button class="btn btn-primary">
            <i class="fas fa-circle-check"></i> Mark Complete
        </button>
    </form>
</div>
@endsection
