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
                @if(isset($courseFeedbackByCourseId[$course->id]))
                    <span class="badge bg-success">Feedback submitted</span><button class="btn btn-outline" type="button" data-modal-open="course-feedback-{{ $course->id }}">Edit feedback</button>
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
                @if(isset($eventFeedbackByEventId[$event->id]))<span class="badge bg-success">Feedback submitted</span><button class="btn btn-outline" type="button" data-modal-open="event-feedback-{{ $event->id }}">Edit feedback</button>@else<button class="btn btn-primary" type="button" data-modal-open="event-feedback-{{ $event->id }}">Add feedback</button>@endif
            </div>
        @empty
            <div class="list-group-item">There are no registered, feedback-enabled events available.</div>
        @endforelse
    </div>
</div>

@foreach($courses as $course)
        <div class="eh-modal" id="course-feedback-{{ $course->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="course-feedback-{{ $course->id }}-title" @if($errors->any() && old('feedback_context') === 'course-'.$course->id) data-modal-autoopen @endif>
            <div class="eh-modal-dialog eh-modal-lg">
                <div class="eh-modal-header"><div><h2 id="course-feedback-{{ $course->id }}-title">Course feedback</h2><p>{{ $course->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
                <form method="POST" action="{{ route('courses.feedback.store', $course) }}">@csrf
                    <input type="hidden" name="feedback_context" value="course-{{ $course->id }}">
                    <div class="eh-modal-body">@if($errors->any() && old('feedback_context') === 'course-'.$course->id)<div class="alert alert-danger" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<div class="modal-grid">
                        @foreach(['overall_rating'=>'Overall experience','content_rating'=>'Course content','instructor_rating'=>'Instructor / facilitation','recommend_rating'=>'Recommend this course'] as $field=>$label)
                            <div class="form-group"><label for="course-{{ $course->id }}-{{ $field }}">{{ $label }} (1 = poor, 5 = excellent) *</label><select id="course-{{ $course->id }}-{{ $field }}" name="{{ $field }}" required><option value="">Choose a rating</option>@for($rating=1;$rating<=5;$rating++)<option value="{{ $rating }}" @selected((old('feedback_context') === 'course-'.$course->id ? old($field, '') : ($courseFeedbackByCourseId[$course->id]->{$field} ?? '')) == $rating)>{{ $rating }} / 5</option>@endfor</select></div>
                        @endforeach
                        <div class="form-group full"><label for="course-{{ $course->id }}-key_learning">Most important learning</label><textarea id="course-{{ $course->id }}-key_learning" name="key_learning" maxlength="5000">{{ old('feedback_context') === 'course-'.$course->id ? old('key_learning', '') : ($courseFeedbackByCourseId[$course->id]->key_learning ?? '') }}</textarea></div>
                        <div class="form-group full"><label for="course-{{ $course->id }}-what_to_improve">What could be improved?</label><textarea id="course-{{ $course->id }}-what_to_improve" name="what_to_improve" maxlength="5000">{{ old('feedback_context') === 'course-'.$course->id ? old('what_to_improve', '') : ($courseFeedbackByCourseId[$course->id]->what_to_improve ?? '') }}</textarea></div>
                        <div class="form-group full"><label for="course-{{ $course->id }}-additional_comments">Additional comments</label><textarea id="course-{{ $course->id }}-additional_comments" name="additional_comments" maxlength="5000">{{ old('feedback_context') === 'course-'.$course->id ? old('additional_comments', '') : ($courseFeedbackByCourseId[$course->id]->additional_comments ?? '') }}</textarea></div>
                    </div></div>
                    <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">{{ isset($courseFeedbackByCourseId[$course->id]) ? 'Save changes' : 'Submit course feedback' }}</button></div>
                </form>
            </div>
        </div>
@endforeach

@foreach($events as $event)
    <div class="eh-modal" id="event-feedback-{{ $event->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="event-feedback-{{ $event->id }}-title" @if($errors->any() && old('feedback_context') === 'event-'.$event->id) data-modal-autoopen @endif>
        <div class="eh-modal-dialog eh-modal-lg">
            <div class="eh-modal-header"><div><h2 id="event-feedback-{{ $event->id }}-title">Event feedback</h2><p>{{ $event->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
            <form method="POST" action="{{ route('events.feedback.store', $event) }}">@csrf
                <input type="hidden" name="feedback_context" value="event-{{ $event->id }}">
                <div class="eh-modal-body">@if($errors->any() && old('feedback_context') === 'event-'.$event->id)<div class="alert alert-danger" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif<div class="modal-grid">
                    @foreach(['overall_rating'=>'Overall experience','relevance_rating'=>'Relevance to your needs','facilitation_rating'=>'Facilitation quality','organisation_rating'=>'Organisation and logistics','recommend_rating'=>'Likelihood to recommend'] as $field=>$label)
                        <div class="form-group"><label for="event-{{ $event->id }}-{{ $field }}">{{ $label }} *</label><select id="event-{{ $event->id }}-{{ $field }}" name="{{ $field }}" required><option value="">Choose a rating (1–5)</option>@for($rating=1;$rating<=5;$rating++)<option value="{{ $rating }}" @selected((old('feedback_context') === 'event-'.$event->id ? old($field, '') : ($eventFeedbackByEventId[$event->id]->{$field} ?? '')) == $rating)>{{ $rating }} / 5</option>@endfor</select></div>
                    @endforeach
                    <div class="form-group full"><label for="event-{{ $event->id }}-key_learning">Key learning</label><textarea id="event-{{ $event->id }}-key_learning" name="key_learning" maxlength="5000">{{ old('feedback_context') === 'event-'.$event->id ? old('key_learning', '') : ($eventFeedbackByEventId[$event->id]->key_learning ?? '') }}</textarea></div>
                    <div class="form-group full"><label for="event-{{ $event->id }}-what_worked">What worked well?</label><textarea id="event-{{ $event->id }}-what_worked" name="what_worked" maxlength="5000">{{ old('feedback_context') === 'event-'.$event->id ? old('what_worked', '') : ($eventFeedbackByEventId[$event->id]->what_worked ?? '') }}</textarea></div>
                    <div class="form-group full"><label for="event-{{ $event->id }}-what_to_improve">What should improve?</label><textarea id="event-{{ $event->id }}-what_to_improve" name="what_to_improve" maxlength="5000">{{ old('feedback_context') === 'event-'.$event->id ? old('what_to_improve', '') : ($eventFeedbackByEventId[$event->id]->what_to_improve ?? '') }}</textarea></div>
                    <div class="form-group full"><label for="event-{{ $event->id }}-additional_comments">Additional comments</label><textarea id="event-{{ $event->id }}-additional_comments" name="additional_comments" maxlength="5000">{{ old('feedback_context') === 'event-'.$event->id ? old('additional_comments', '') : ($eventFeedbackByEventId[$event->id]->additional_comments ?? '') }}</textarea></div>
                </div></div>
                <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">{{ isset($eventFeedbackByEventId[$event->id]) ? 'Save changes' : 'Submit event feedback' }}</button></div>
            </form>
        </div>
    </div>
@endforeach
@endsection
