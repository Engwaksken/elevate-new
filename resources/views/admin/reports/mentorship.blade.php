@extends('layouts.admin')
@section('title', 'Mentorship Tracking | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Reports</span>
        <h1>Mentorship Tracking</h1>
        <p>Mentors, matches, sessions and goals across the programme.</p>
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach([
        ['Mentors', $stats['mentors'], 'fa-user-tie'],
        ['Matches', $stats['matches'], 'fa-people-arrows'],
        ['Sessions', $stats['sessions'], 'fa-calendar-check'],
        ['Goals', $stats['goals'], 'fa-bullseye'],
    ] as [$label, $value, $icon])
        <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></div>
    @endforeach
</div>
@endsection
