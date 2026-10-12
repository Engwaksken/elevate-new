@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<div class="container py-4">
    <h1>Course &amp; event feedback</h1>
    <p>Share your experience of completed courses and events you attended. Your feedback helps us improve future activities.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <h2 class="mt-4">Courses</h2>
    <div class="list-group">
        @forelse($courses as $course)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $course->title }}</strong><div>Course feedback</div></div>
                @if(in_array($course->id, $courseFeedbackIds, true))
                    <span class="badge bg-success">Feedback submitted</span>
                @else
                    <a class="btn btn-primary" href="{{ route('courses.feedback', $course) }}">Give feedback</a>
                @endif
            </div>
        @empty
            <div class="list-group-item">No completed courses are awaiting feedback.</div>
        @endforelse
    </div>

    <h2 class="mt-4">Events</h2>
    <div class="list-group">
        @forelse($events as $event)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $event->title }}</strong><div>{{ optional($event->starts_at)->format('d M Y') }}</div></div>
                <a class="btn btn-primary" href="{{ route('events.feedback', $event) }}">Give feedback</a>
            </div>
        @empty
            <div class="list-group-item">There are no events awaiting your feedback.</div>
        @endforelse
    </div>
</div>
@endsection
