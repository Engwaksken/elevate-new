@extends('layouts.admin')
@section('title','Executive Dashboard | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
<span class="admin-eyebrow">Executive Reporting</span>
<h1>Executive Dashboard</h1>
<p>Organisation-wide operational snapshot across programmes, learning, mentorship, jobs, MEAL, HR, assets and procurement.</p>
</div>
</div>

@php
$groups=[
    'People & Learning'=>[
        ['participants','Participants','fa-users'],
        ['staff','Staff','fa-user-tie'],
        ['published_courses','Published Courses','fa-graduation-cap'],
        ['active_enrolments','Active Enrolments','fa-user-check'],
        ['completed_learners','Completed Learners','fa-circle-check'],
        ['certificates','Certificates','fa-award'],
    ],
    'Programmes & Outcomes'=>[
        ['active_programmes','Active Programmes','fa-diagram-project'],
        ['active_mentorships','Active Mentorships','fa-handshake'],
        ['job_applications','Job Applications','fa-briefcase'],
        ['verified_outcomes','Verified Outcomes','fa-bullseye'],
        ['approved_workplans','Approved Workplans','fa-list-check'],
        ['pending_indicator_results','Pending Indicator Results','fa-clock'],
    ],
    'Operations'=>[
        ['active_employees','Active Employees','fa-id-badge'],
        ['pending_leave','Pending Leave','fa-calendar-minus'],
        ['active_assets','Active Assets','fa-laptop'],
        ['open_purchase_requests','Open Purchase Requests','fa-cart-shopping'],
    ],
];
@endphp

@foreach($groups as $group=>$items)
<section class="admin-panel executive-section">
<div class="admin-panel-head"><div><h2>{{ $group }}</h2></div></div>
<div class="admin-stats-grid compact">
@foreach($items as [$key,$label,$icon])
<div class="admin-stat">
<span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
<div><small>{{ $label }}</small><strong>{{ number_format((float)($stats[$key] ?? 0)) }}</strong></div>
</div>
@endforeach
</div>
</section>
@endforeach

<div class="admin-panel">
<div class="admin-panel-head"><div><h2>Quick Management Access</h2><p>Open the main administration areas from one place.</p></div></div>
<div class="executive-links">
@foreach([
['admin.programmes.index','Programmes','fa-diagram-project'],
['admin.elearning.courses.index','Courses','fa-graduation-cap'],
['admin.mentorship.mentors.index','Mentorship','fa-handshake'],
['admin.jobs.index','Jobs','fa-briefcase'],
['admin.hr.employees.index','Human Resources','fa-users-gear'],
['admin.procurement.requests.index','Procurement','fa-cart-shopping'],
['admin.assets.index','Assets','fa-laptop'],
['admin.settings.index','Settings','fa-gear'],
] as [$route,$label,$icon])
@if(Route::has($route))
<a href="{{ route($route) }}"><i class="fas {{ $icon }}"></i><span>{{ $label }}</span><i class="fas fa-chevron-right"></i></a>
@endif
@endforeach
</div>
</div>
@endsection
