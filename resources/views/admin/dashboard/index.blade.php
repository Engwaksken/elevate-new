@extends('layouts.admin')
@section('title','Admin Dashboard | ElevateHer360')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Administration</span><h1>Dashboard</h1><p>Organisation-wide overview of people, programmes, learning, mentorship, jobs, MEAL and operations.</p></div>
<div class="admin-page-actions"><span class="admin-date"><i class="fas fa-calendar-day"></i> {{ now()->format('d M Y') }}</span></div>
</div>

@php
$groups=[
'People & Access'=>[
 ['participants','Participants','fa-users'],['staff','Staff','fa-user-shield'],['active_users','Active Users','fa-circle-check']
],
'Programme Structure'=>[
 ['programmes','Programmes','fa-diagram-project'],['projects','Projects','fa-folder-tree'],['cohorts','Cohorts','fa-people-group'],['branches','Branches','fa-building']
],
'Participant Services'=>[
 ['courses','Courses','fa-graduation-cap'],['enrolments','Enrolments','fa-user-check'],['mentor_matches','Mentor Matches','fa-handshake'],['jobs','Jobs','fa-briefcase'],['job_applications','Applications','fa-file-signature'],['library_resources','Library Resources','fa-book-open']
],
'Planning & Operations'=>[
 ['workplans','Workplans','fa-list-check'],['activities','Activities','fa-bars-progress'],['indicators','Indicators','fa-chart-column'],['employees','Employees','fa-id-badge'],['assets','Assets','fa-laptop'],['purchase_requests','Purchase Requests','fa-cart-shopping']
],
];
@endphp

@foreach($groups as $heading=>$cards)
<div class="admin-stat-section-title"><h2>{{ $heading }}</h2></div>
<div class="admin-stats-grid">
@foreach($cards as [$key,$label,$icon])
<div class="admin-stat">
<span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
<div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>
@endforeach

<div class="admin-dashboard-grid">
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Learning Performance</h2><p>Current enrolment completion.</p></div></div>
<div class="admin-progress-summary">
<div class="admin-progress-number">{{ number_format($completionRate,1) }}%</div>
<div class="admin-progress-copy"><strong>Completion Rate</strong><span>{{ number_format($stats['completed_learning'] ?? 0) }} completed from {{ number_format($stats['enrolments'] ?? 0) }} enrolments.</span></div>
</div>
<div class="admin-progress-track"><span style="width:{{ min(100,$completionRate) }}%"></span></div>
</section>

<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Recent Users</h2><p>Latest participant and staff accounts.</p></div>
@if(Route::has('admin.users.index'))<a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">View All</a>@endif
</div>
<div class="admin-activity-list">
@forelse($recentUsers as $user)
<div class="admin-activity-row">
<span class="admin-activity-icon"><i class="fas fa-user"></i></span>
<div><strong>{{ $user->name }}</strong><small>{{ $user->email }} · {{ ucfirst($user->user_type) }} · {{ ucfirst($user->status) }}</small></div>
<time>{{ \Illuminate\Support\Carbon::parse($user->created_at)->diffForHumans() }}</time>
</div>
@empty
<div class="admin-empty">No recent users.</div>
@endforelse
</div>
</section>
</div>
@endsection
