@extends('layouts.app')

@section('title', 'Feedback')

@section('content')
<style>
.fb-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 20px}
.fb-stat{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;background:#fff}
.fb-stat i{width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#fff7da;color:#800000}
.fb-stat small{display:block;color:#667085;font-weight:700}.fb-stat strong{font-size:1.3rem;color:#101828}
@media(max-width:900px){.fb-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="page-header">
    <div><span class="eh-kicker">Your voice</span><h1>Course &amp; event feedback</h1><p>Tell us how your courses and events went. Your feedback helps us improve future activities.</p></div>
</div>

<div class="fb-stats">
    <div class="fb-stat"><i class="fas fa-graduation-cap"></i><div><small>Courses waiting for feedback</small><strong>{{ number_format($stats['courses_waiting']) }}</strong></div></div>
    <div class="fb-stat"><i class="fas fa-calendar-check"></i><div><small>Events waiting for feedback</small><strong>{{ number_format($stats['events_waiting']) }}</strong></div></div>
    <div class="fb-stat"><i class="fas fa-circle-check"></i><div><small>Feedback given</small><strong>{{ number_format($stats['submitted']) }}</strong></div></div>
    <div class="fb-stat"><i class="fas fa-star"></i><div><small>Your average rating</small><strong>{{ $stats['average'] !== null ? number_format($stats['average'], 1).' / 5' : '-' }}</strong></div></div>
</div>

<div class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search course or event..." aria-label="Search">
        <select name="type" aria-label="Type">
            <option value="">Courses and events</option>
            <option value="course" @selected(request('type') === 'course')>Courses</option>
            <option value="event" @selected(request('type') === 'event')>Events</option>
        </select>
        <select name="status" aria-label="Status">
            <option value="">Any status</option>
            <option value="waiting" @selected(request('status') === 'waiting')>Waiting for my feedback</option>
            <option value="submitted" @selected(request('status') === 'submitted')>Feedback given</option>
        </select>
        <select name="period" aria-label="Date" data-period-select>
            @foreach(\App\Support\PeriodFilter::OPTIONS as $value => $label)
                <option value="{{ $value }}" @selected(request('period', '') === $value)>{{ $value === '' ? 'Any date' : $label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
        <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
        <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
        <a class="btn btn-outline btn-sm" href="{{ route('participant.feedback.index') }}">Reset</a>
    </form>
    @if($feedbackItems->total() > 0)<p class="admin-cell-hint" style="margin:0 0 10px">Showing {{ $feedbackItems->firstItem() }}–{{ $feedbackItems->lastItem() }} of {{ number_format($feedbackItems->total()) }}. Items waiting for your feedback are listed first.</p>@endif

    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Type</th><th>Course or event</th><th>Date</th><th>Status</th><th>Your rating</th><th class="table-actions">Actions</th></tr></thead>
        <tbody>
        @forelse($feedbackItems as $item)
            <tr>
                <td><span class="status-chip {{ $item['type'] === 'course' ? 'active' : 'pending' }}"><i class="fas {{ $item['type'] === 'course' ? 'fa-graduation-cap' : 'fa-calendar-check' }}"></i> {{ ucfirst($item['type']) }}</span></td>
                <td><strong>{{ $item['title'] }}</strong></td>
                <td>{{ $item['date']?->format('d M Y') ?? '-' }}<small class="admin-cell-hint">{{ $item['type'] === 'course' ? 'Enrolled' : 'Event date' }}</small></td>
                <td>
                    @if($item['response'])
                        <span class="status-chip completed">Feedback submitted</span><small class="admin-cell-hint">{{ optional($item['response']->submitted_at)->format('d M Y') }}</small>
                    @else
                        <span class="status-chip pending">Waiting for your feedback</span>
                    @endif
                </td>
                <td>{{ $item['response'] ? $item['response']->overall_rating.' / 5' : '-' }}</td>
                <td class="table-actions">
                    <button class="btn {{ $item['response'] ? 'btn-outline' : 'btn-primary' }} btn-sm" type="button" data-modal-open="{{ $item['type'] }}-feedback-{{ $item['id'] }}">
                        <i class="fas {{ $item['response'] ? 'fa-pen' : 'fa-star' }}"></i> {{ $item['response'] ? 'Edit feedback' : 'Give feedback' }}
                    </button>
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="admin-empty">{{ request()->hasAny(['search', 'type', 'status', 'period']) ? 'Nothing matches these filters.' : 'There are no courses or events to give feedback on yet. Enrol in a course or register for an event to share your experience.' }}</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="admin-pagination">{{ $feedbackItems->links() }}</div>
</div>

<script>
document.querySelectorAll('[data-period-select]').forEach(function (select) {
    select.addEventListener('change', function () {
        select.form.querySelectorAll('[data-period-custom]').forEach(function (input) { input.hidden = select.value !== 'custom'; });
    });
});
</script>

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
