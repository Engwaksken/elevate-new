@extends('layouts.admin')
@section('title', 'Jobs Tracking | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Reports</span>
        <h1>Jobs Tracking</h1>
        <p>Latest 200 job tracking events by stage.</p>
    </div>
    <div class="admin-page-actions"><x-export-buttons /></div>
</div>

@if($funnel->isNotEmpty())
    <div class="admin-stats-grid compact">
        @foreach($funnel as $type => $count)
            <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-route"></i></span><div><small>{{ ucwords(str_replace('_', ' ', $type)) }}</small><strong>{{ number_format($count) }}</strong></div></div>
        @endforeach
    </div>
@endif

<div class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr><th>Participant</th><th>Job</th><th>Event</th><th>Employer</th><th>Position</th><th>Date</th></tr>
            </thead>
            <tbody>
                @forelse($events as $e)
                    <tr>
                        <td>{{ $userNames[$e->user_id] ?? 'User #'.$e->user_id }}</td>
                        <td>{{ $e->job_id ? ($jobTitles[$e->job_id] ?? 'Job #'.$e->job_id) : '—' }}</td>
                        <td><span class="status-chip {{ $e->event_type }}">{{ ucwords(str_replace('_', ' ', $e->event_type)) }}</span></td>
                        <td>{{ $e->employer ?: '—' }}</td>
                        <td>{{ $e->position ?: '—' }}</td>
                        <td>{{ $e->event_date ? \Illuminate\Support\Carbon::parse($e->event_date)->format('d M Y') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="admin-empty">No tracking events recorded yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
