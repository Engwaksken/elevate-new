@extends('layouts.admin')
@section('title','MEAL Dashboard | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Monitoring, Evaluation, Accountability & Learning</span><h1>MEAL Dashboard</h1><p>Programme performance, results verification and participant outcome monitoring.</p></div>
</div>

<div class="admin-stats-grid compact">
@php
$cards=[
['active_indicators','Active Indicators','fa-chart-line'],
['pending_results','Pending Results','fa-clock'],
['verified_outcomes','Verified Outcomes','fa-circle-check'],
['completed_learners','Completed Learners','fa-user-graduate'],
['certificates','Certificates','fa-certificate'],
['active_mentorships','Active Mentorships','fa-handshake'],
['results_frameworks','Results Frameworks','fa-diagram-project'],
['framework_results','Framework Results','fa-bullseye'],
];
@endphp
@foreach($cards as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<div class="admin-page-actions">
<a href="{{ route('admin.indicators.index') }}" class="btn btn-outline"><i class="fas fa-chart-line"></i> Indicators</a>
<a href="{{ route('admin.results-framework.index') }}" class="btn btn-outline"><i class="fas fa-diagram-project"></i> Results Framework</a>
@if(Route::has('admin.jobs.outcomes.index'))<a href="{{ route('admin.jobs.outcomes.index') }}" class="btn btn-outline"><i class="fas fa-briefcase"></i> Participant Outcomes</a>@endif
</div>
</div>
@endsection
