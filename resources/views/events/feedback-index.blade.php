@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<div class="container py-4">
    <h1>Course &amp; event feedback</h1>
    <p>Share your experience of courses you are enrolled in and events you have registered for. Your feedback helps us improve future activities.</p>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <h2 class="mt-4">Courses</h2>
    <div class="list-group">
        @forelse($courses as $course)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $course->title }}</strong><div>Course feedback</div></div>
                @if(in_array($course->id, $courseFeedbackIds, true))
                    <span class="badge bg-success">Feedback submitted</span>
                @else
                    <button class="btn btn-primary" type="button" data-modal-open="course-feedback-{{ $course->id }}">Add feedback</button>
                @endif
            </div>
        @empty
            <div class="list-group-item">No enrolled courses are available for feedback.</div>
        @endforelse
    </div>

    <h2 class="mt-4">Events</h2>
    <div class="list-group">
        @forelse($events as $event)
            <div class="list-group-item d-flex justify-content-between align-items-center gap-3">
                <div><strong>{{ $event->title }}</strong><div>{{ optional($event->starts_at)->format('d M Y') }}</div></div>
                <button class="btn btn-primary" type="button" data-modal-open="event-feedback-{{ $event->id }}">Add feedback</button>
            </div>
        @empty
            <div class="list-group-item">There are no registered, feedback-enabled events available.</div>
        @endforelse
    </div>
</div>

@foreach($courses as $course)
    @if(!in_array($course->id, $courseFeedbackIds, true))
        <div class="eh-modal" id="course-feedback-{{ $course->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="course-feedback-{{ $course->id }}-title">
            <div class="eh-modal-dialog eh-modal-lg">
                <div class="eh-modal-header"><div><h2 id="course-feedback-{{ $course->id }}-title">Course feedback</h2><p>{{ $course->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
                <form method="POST" action="{{ route('courses.feedback.store', $course) }}">@csrf
                    <div class="eh-modal-body"><div class="modal-grid">
                        @foreach(['overall_rating'=>'Overall experience','content_rating'=>'Course content','instructor_rating'=>'Instructor / facilitation','recommend_rating'=>'Recommend this course'] as $field=>$label)
                            <div class="form-group"><label>{{ $label }} (1 = poor, 5 = excellent) *</label><select name="{{ $field }}" required><option value="">Choose a rating</option>@for($rating=1;$rating<=5;$rating++)<option value="{{ $rating }}">{{ $rating }} / 5</option>@endfor</select></div>
                        @endforeach
                        <div class="form-group full"><label>Most important learning</label><textarea name="key_learning" maxlength="5000"></textarea></div>
                        <div class="form-group full"><label>What could be improved?</label><textarea name="what_to_improve" maxlength="5000"></textarea></div>
                        <div class="form-group full"><label>Additional comments</label><textarea name="additional_comments" maxlength="5000"></textarea></div>
                    </div></div>
                    <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Submit course feedback</button></div>
                </form>
            </div>
        </div>
    @endif
@endforeach

@foreach($events as $event)
    <div class="eh-modal" id="event-feedback-{{ $event->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="event-feedback-{{ $event->id }}-title">
        <div class="eh-modal-dialog eh-modal-lg">
            <div class="eh-modal-header"><div><h2 id="event-feedback-{{ $event->id }}-title">Event feedback</h2><p>{{ $event->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
            <form method="POST" action="{{ route('events.feedback.store', $event) }}">@csrf
                <div class="eh-modal-body"><div class="modal-grid">
                    @foreach(['overall_rating'=>'Overall experience','relevance_rating'=>'Relevance to your needs','facilitation_rating'=>'Facilitation quality','organisation_rating'=>'Organisation and logistics','recommend_rating'=>'Likelihood to recommend'] as $field=>$label)
                        <div class="form-group"><label>{{ $label }} *</label><select name="{{ $field }}" required><option value="">Select 1–5</option>@for($rating=1;$rating<=5;$rating++)<option value="{{ $rating }}">{{ $rating }} / 5</option>@endfor</select></div>
                    @endforeach
                    <div class="form-group full"><label>Key learning</label><textarea name="key_learning" maxlength="5000"></textarea></div>
                    <div class="form-group full"><label>What worked well?</label><textarea name="what_worked" maxlength="5000"></textarea></div>
                    <div class="form-group full"><label>What should improve?</label><textarea name="what_to_improve" maxlength="5000"></textarea></div>
                    <div class="form-group full"><label>Additional comments</label><textarea name="additional_comments" maxlength="5000"></textarea></div>
                </div></div>
                <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Submit event feedback</button></div>
            </form>
        </div>
    </div>
@endforeach
@endsection
