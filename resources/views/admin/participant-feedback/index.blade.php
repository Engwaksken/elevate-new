@extends('layouts.admin')
@section('title', 'Participant Feedback | ElevateHer360')
@section('content')
<style>
.fb-rating{display:inline-flex;align-items:center;justify-content:center;min-width:38px;padding:2px 8px;border-radius:999px;font-weight:700;font-size:.78rem}
.fb-rating.r-high{background:#ecfdf3;color:#067647}.fb-rating.r-mid{background:#fffaeb;color:#b54708}.fb-rating.r-low{background:#fef3f2;color:#b42318}
.fb-comment{max-width:320px;color:#475467}
</style>
@php($ratingClass = fn ($value) => $value >= 4 ? 'r-high' : ($value >= 3 ? 'r-mid' : 'r-low'))

<div class="admin-page-header">
    <div><span class="admin-eyebrow">Monitoring &amp; Learning</span><h1>Participant Feedback</h1><p>Review how participants rate courses and events, and what they say about them.</p></div>
</div>

<div class="admin-stats-grid">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-comments"></i></span><div><small>Responses</small><strong>{{ number_format($stats['total']) }}</strong><small class="admin-cell-hint">{{ number_format($stats['courses']) }} course · {{ number_format($stats['events']) }} event</small></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-star"></i></span><div><small>Average rating</small><strong>{{ $stats['average'] !== null ? number_format($stats['average'], 1).' / 5' : '-' }}</strong><small class="admin-cell-hint">Overall experience</small></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-face-smile"></i></span><div><small>Satisfied</small><strong>{{ $stats['satisfied'] !== null ? $stats['satisfied'].'%' : '-' }}</strong><small class="admin-cell-hint">Rated 4 or 5</small></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-thumbs-up"></i></span><div><small>Would recommend</small><strong>{{ $stats['recommend'] !== null ? number_format($stats['recommend'], 1).' / 5' : '-' }}</strong><small class="admin-cell-hint">Average recommend score</small></div></div>
</div>

<div class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search participant, course or event..." aria-label="Search feedback"></div>
        <select name="rating" aria-label="Overall rating">
            <option value="">All ratings</option>
            <option value="high" @selected(request('rating') === 'high')>Good (4 to 5)</option>
            <option value="mid" @selected(request('rating') === 'mid')>Average (3)</option>
            <option value="low" @selected(request('rating') === 'low')>Poor (1 to 2)</option>
        </select>
        <select name="period" aria-label="Submitted" data-period-select>
            @foreach(\App\Support\PeriodFilter::OPTIONS as $value => $label)
                <option value="{{ $value }}" @selected(request('period', '') === $value)>{{ $value === '' ? 'Any time' : 'Submitted: '.$label }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
        <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" data-period-custom @unless(request('period') === 'custom') hidden @endunless>
        <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
        <a class="btn btn-outline btn-sm" href="{{ route('admin.participant-feedback.index') }}">Reset</a>
    </form>

    <div class="eh-tabs" data-eh-tabs>
        <div class="eh-tab-nav">
            <button class="eh-tab-button active" data-eh-tab="course-feedback" type="button"><i class="fas fa-graduation-cap"></i> Course feedback ({{ number_format($courseResponses->total()) }})</button>
            <button class="eh-tab-button" data-eh-tab="event-feedback" type="button"><i class="fas fa-calendar-check"></i> Event feedback ({{ number_format($eventResponses->total()) }})</button>
        </div>
        <div class="eh-tab-content">
            <section class="eh-tab-pane active" data-eh-pane="course-feedback">
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Course</th><th>Participant</th><th>Overall</th><th>Content</th><th>Instructor</th><th>Recommend</th><th>Comments</th><th>Submitted</th><th class="table-actions">Details</th></tr></thead>
                    <tbody>
                    @forelse($courseResponses as $response)
                        <tr>
                            <td><strong>{{ $response->course?->title ?? 'Course removed' }}</strong></td>
                            <td>{{ $response->user?->name ?? 'Participant removed' }}</td>
                            @foreach(['overall_rating', 'content_rating', 'instructor_rating', 'recommend_rating'] as $field)
                                <td><span class="fb-rating {{ $ratingClass($response->{$field}) }}">{{ $response->{$field} }}/5</span></td>
                            @endforeach
                            <td class="fb-comment">{{ \Illuminate\Support\Str::limit(collect([$response->key_learning, $response->what_to_improve, $response->additional_comments])->filter()->implode(' · '), 90) ?: '-' }}</td>
                            <td>{{ optional($response->submitted_at)->format('d M Y') ?: '-' }}</td>
                            <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="course-response-{{ $response->id }}"><i class="fas fa-eye"></i> View</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="9"><div class="admin-empty">No course feedback matches these filters.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $courseResponses->links() }}</div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="event-feedback">
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Event</th><th>Participant</th><th>Overall</th><th>Relevance</th><th>Facilitation</th><th>Organisation</th><th>Recommend</th><th>Comments</th><th>Submitted</th><th class="table-actions">Details</th></tr></thead>
                    <tbody>
                    @forelse($eventResponses as $response)
                        <tr>
                            <td><strong>{{ $response->event?->title ?? 'Event removed' }}</strong></td>
                            <td>{{ $response->user?->name ?? 'Participant removed' }}</td>
                            @foreach(['overall_rating', 'relevance_rating', 'facilitation_rating', 'organisation_rating', 'recommend_rating'] as $field)
                                <td><span class="fb-rating {{ $ratingClass($response->{$field}) }}">{{ $response->{$field} }}/5</span></td>
                            @endforeach
                            <td class="fb-comment">{{ \Illuminate\Support\Str::limit(collect([$response->key_learning, $response->what_worked, $response->what_to_improve, $response->additional_comments])->filter()->implode(' · '), 90) ?: '-' }}</td>
                            <td>{{ optional($response->submitted_at)->format('d M Y') ?: '-' }}</td>
                            <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="event-response-{{ $response->id }}"><i class="fas fa-eye"></i> View</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="10"><div class="admin-empty">No event feedback matches these filters.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $eventResponses->links() }}</div>
            </section>
        </div>
    </div>
</div>

@foreach([['course', $courseResponses, ['key_learning' => 'Most important learning', 'what_to_improve' => 'What could be improved', 'additional_comments' => 'Additional comments']], ['event', $eventResponses, ['key_learning' => 'Key learning', 'what_worked' => 'What worked well', 'what_to_improve' => 'What should improve', 'additional_comments' => 'Additional comments']]] as [$type, $responses, $fields])
    @foreach($responses as $response)
        <div class="eh-modal" id="{{ $type }}-response-{{ $response->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $type }}-response-{{ $response->id }}-title">
            <div class="eh-modal-dialog">
                <div class="eh-modal-header">
                    <div><h2 id="{{ $type }}-response-{{ $response->id }}-title">{{ $type === 'course' ? ($response->course?->title ?? 'Course removed') : ($response->event?->title ?? 'Event removed') }}</h2><p>{{ $response->user?->name ?? 'Participant removed' }} · {{ optional($response->submitted_at)->format('d M Y H:i') }} · Overall {{ $response->overall_rating }}/5</p></div>
                    <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
                </div>
                <div class="eh-modal-body">
                    @foreach($fields as $field => $label)
                        <h3 style="margin:0 0 4px;font-size:.9rem">{{ $label }}</h3>
                        <p style="margin:0 0 14px;white-space:pre-wrap;color:#344054">{{ $response->{$field} ?: 'No answer given.' }}</p>
                    @endforeach
                </div>
                <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Close</button></div>
            </div>
        </div>
    @endforeach
@endforeach

<script>
document.querySelectorAll('[data-period-select]').forEach(function (select) {
    select.addEventListener('change', function () {
        select.form.querySelectorAll('[data-period-custom]').forEach(function (input) { input.hidden = select.value !== 'custom'; });
    });
});
</script>
@endsection
