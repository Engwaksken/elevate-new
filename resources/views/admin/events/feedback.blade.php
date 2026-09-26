@extends('layouts.admin')
@section('title','Event Feedback | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Event Evaluation</span><h1>{{ $event->title }}</h1><p>Participant feedback and event quality indicators.</p></div><div class="admin-page-actions"><a href="{{ route('admin.events.view',$event) }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Event View</a></div></div>
<div class="admin-stats-grid compact">
@foreach([['responses','Responses','fa-comments'],['overall','Overall / 5','fa-star'],['relevance','Relevance / 5','fa-bullseye'],['recommend','Recommend / 5','fa-thumbs-up']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format((float)($stats[$k]??0),$k==='responses'?0:2) }}</strong></div></div>
@endforeach
</div>
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Participant</th><th>Overall</th><th>Relevance</th><th>Facilitation</th><th>Organisation</th><th>Recommend</th><th>Key Learning</th></tr></thead><tbody>
@forelse($responses as $response)
<tr><td><strong>{{ $response->user?->name ?: 'Participant' }}</strong><small class="admin-cell-hint">{{ $response->user?->email }}</small></td><td>{{ $response->overall_rating }}/5</td><td>{{ $response->relevance_rating }}/5</td><td>{{ $response->facilitation_rating }}/5</td><td>{{ $response->organisation_rating }}/5</td><td>{{ $response->recommend_rating }}/5</td><td>{{ Str::limit($response->key_learning,80) ?: '—' }}</td></tr>
@empty<tr><td colspan="7"><div class="admin-empty">No feedback submitted yet.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $responses->links() }}</div></div>
@endsection
