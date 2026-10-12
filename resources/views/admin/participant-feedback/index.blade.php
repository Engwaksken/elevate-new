@extends('layouts.admin')
@section('title', 'Participant Feedback | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Monitoring &amp; Learning</span><h1>Participant Feedback</h1><p>Review participant feedback submitted for courses and events.</p></div>
</div>

<div class="admin-panel">
    <h2>Course feedback</h2>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Course</th><th>Participant</th><th>Overall</th><th>Content</th><th>Instructor</th><th>Recommend</th><th>Learning / comments</th><th>Submitted</th></tr></thead>
        <tbody>
        @forelse($courseResponses as $response)
            <tr>
                <td>{{ $response->course?->title ?? 'Course removed' }}</td><td>{{ $response->user?->name ?? 'Participant removed' }}</td>
                <td>{{ $response->overall_rating }}/5</td><td>{{ $response->content_rating }}/5</td><td>{{ $response->instructor_rating }}/5</td><td>{{ $response->recommend_rating }}/5</td>
                <td>{{ \Illuminate\Support\Str::limit(collect([$response->key_learning, $response->what_to_improve, $response->additional_comments])->filter()->implode(' · '), 180) ?: '—' }}</td>
                <td>{{ optional($response->submitted_at)->format('d M Y H:i') ?: '—' }}</td>
            </tr>
        @empty<tr><td colspan="8">No course feedback has been submitted.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div class="admin-pagination">{{ $courseResponses->links() }}</div>
</div>

<div class="admin-panel" style="margin-top:24px">
    <h2>Event feedback</h2>
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Event</th><th>Participant</th><th>Overall</th><th>Relevance</th><th>Facilitation</th><th>Organisation</th><th>Comments</th><th>Submitted</th></tr></thead>
        <tbody>
        @forelse($eventResponses as $response)
            <tr>
                <td>{{ $response->event?->title ?? 'Event removed' }}</td><td>{{ $response->user?->name ?? 'Participant removed' }}</td>
                <td>{{ $response->overall_rating }}/5</td><td>{{ $response->relevance_rating }}/5</td><td>{{ $response->facilitation_rating }}/5</td><td>{{ $response->organisation_rating }}/5</td>
                <td>{{ \Illuminate\Support\Str::limit(collect([$response->key_learning, $response->what_worked, $response->what_to_improve, $response->additional_comments])->filter()->implode(' · '), 180) ?: '—' }}</td>
                <td>{{ optional($response->submitted_at)->format('d M Y H:i') ?: '—' }}</td>
            </tr>
        @empty<tr><td colspan="8">No event feedback has been submitted.</td></tr>@endforelse
        </tbody>
    </table></div>
    <div class="admin-pagination">{{ $eventResponses->links() }}</div>
</div>
@endsection
