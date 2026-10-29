@extends('layouts.app')
@section('title','Dashboard - ElevateHer360')
@section('content')

<div class="page-header">
<div>
    <span class="eh-kicker">Participant Dashboard</span>
    <h1>Welcome, {{ $user->profile?->given_name ?: $user->name }}</h1>
    <p>Continue your learning, mentorship, career and opportunity journey.</p>
    @if($user->participant_code)<p><span class="participant-code" title="Your participant ID">ID {{ $user->participant_code }}</span></p>@endif
</div>
<div class="page-actions">
    @if(Route::has('profile.edit'))
    <a href="{{ route('profile.edit') }}" class="btn btn-outline"><i class="fas fa-user-pen"></i> Profile</a>
    @endif
    @if(Route::has('notifications.index'))
    <a href="{{ route('notifications.index') }}" class="btn btn-primary">
        <i class="fas fa-bell"></i> Notifications
        @if($unreadNotifications>0)<span class="eh-badge">{{ $unreadNotifications }}</span>@endif
    </a>
    @endif
</div>
</div>

<div class="eh-stats">
<div class="eh-stat"><span class="eh-stat-label">Learning</span><strong class="eh-stat-value">{{ $enrolments->count() }}</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Mentorship</span><strong class="eh-stat-value">{{ $activeMentorship ? 1 : 0 }}</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Applications</span><strong class="eh-stat-value">{{ $jobApplications->count() }}</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Resumes</span><strong class="eh-stat-value">{{ $resumeCount }}</strong></div>
</div>

<div class="eh-tabs" data-eh-tabs>
<div class="eh-tab-nav">
    <button class="eh-tab-button active" data-eh-tab="overview"><i class="fas fa-house"></i> Overview</button>
    <button class="eh-tab-button" data-eh-tab="learning"><i class="fas fa-graduation-cap"></i> Learning</button>
    <button class="eh-tab-button" data-eh-tab="mentorship"><i class="fas fa-user-group"></i> Mentorship</button>
    <button class="eh-tab-button" data-eh-tab="career"><i class="fas fa-file-lines"></i> Career</button>
    <button class="eh-tab-button" data-eh-tab="opportunities"><i class="fas fa-briefcase"></i> Opportunities</button>
    <button class="eh-tab-button" data-eh-tab="calendar"><i class="fas fa-calendar-days"></i> Calendar</button>
    <button class="eh-tab-button" data-eh-tab="notifications"><i class="fas fa-bell"></i> Updates</button>
</div>

<div class="eh-tab-content">
<section class="eh-tab-pane active" data-eh-pane="overview">
<div class="eh-tab-section">
@php($quickLinks = collect([
    ['learning.my-courses', 'fa-graduation-cap', 'Learning', 'Continue your courses and track progress.'],
    ['mentorship.dashboard', 'fa-user-group', 'Mentorship', 'Review mentor relationships, sessions and goals.'],
    ['career.resume.index', 'fa-file-lines', 'Resume Builder', 'Create and manage your professional resume.'],
    ['jobs.index', 'fa-briefcase', 'Jobs', 'Browse career opportunities and applications.'],
    ['library.index', 'fa-book-open', 'Library', 'Access digital learning and career resources.'],
])->filter(fn ($link) => Route::has($link[0])))
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Area</th><th>What you can do</th><th class="table-actions">Actions</th></tr></thead><tbody>
@foreach($quickLinks as [$route, $icon, $label, $description])
<tr><td><a href="{{ route($route) }}"><strong><i class="fas {{ $icon }}" style="color:#800000;margin-right:6px"></i>{{ $label }}</strong></a></td><td>{{ $description }}</td><td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route($route) }}"><i class="fas fa-arrow-right"></i> Open</a></td></tr>
@endforeach
</tbody></table></div></div>
</div>
</section>

<section class="eh-tab-pane" data-eh-pane="learning">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Course</th><th>Progress</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($enrolments->filter(fn ($enrolment) => $enrolment->course) as $enrolment)
<tr><td><strong>{{ $enrolment->course->title }}</strong></td><td>{{ number_format((float)$enrolment->progress_percent,0) }}% complete</td><td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('learning.course.dashboard',$enrolment->course) }}"><i class="fas fa-arrow-right"></i> Open</a></td></tr>
@empty
<tr><td colspan="3"><div class="admin-empty"><i class="fas fa-graduation-cap"></i><strong>No recent courses</strong><span>Your recent learning activity will appear here.</span></div></td></tr>
@endforelse
</tbody></table></div></div></div>
</section>

<section class="eh-tab-pane" data-eh-pane="mentorship">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Mentor</th><th>Status</th><th>Next session</th></tr></thead><tbody>
@if($activeMentorship)
@php($nextSession=$activeMentorship->sessions->first())
<tr><td><strong>{{ data_get($activeMentorship,'mentor.name','Mentor connection') }}</strong></td><td><span class="status-chip {{ $activeMentorship->status }}">{{ ucfirst($activeMentorship->status) }}</span></td><td>{{ $nextSession ? optional($nextSession->scheduled_at)->format('d M Y H:i') : '-' }}</td></tr>
@else
<tr><td colspan="3"><div class="admin-empty"><i class="fas fa-user-group"></i><strong>No active mentor match</strong><span>Your mentorship connection will appear here when available.</span></div></td></tr>
@endif
</tbody></table></div></div></div>
</section>

<section class="eh-tab-pane" data-eh-pane="career">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Resume</th><th>Type</th></tr></thead><tbody>
@forelse($resumes as $resume)
<tr><td><strong>{{ $resume->title }}</strong></td><td>@if($resume->is_default)<span class="status-chip active">Default resume</span>@else Resume @endif</td></tr>
@empty
<tr><td colspan="2"><div class="admin-empty"><i class="fas fa-file-lines"></i><strong>No resume yet</strong><span>Create your professional resume from the career section.</span>@if(Route::has('career.resume.index'))<a class="btn btn-primary btn-sm" href="{{ route('career.resume.index') }}">Resume Builder</a>@endif</div></td></tr>
@endforelse
</tbody></table></div></div></div>
</section>

<section class="eh-tab-pane" data-eh-pane="opportunities">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Job</th><th>Employer</th><th>Status</th></tr></thead><tbody>
@forelse($jobApplications as $application)
<tr><td><strong>{{ $application->job?->title ?: 'Job Application' }}</strong></td><td>{{ data_get($application,'job.employer.company_name') ?: '-' }}</td><td><span class="status-chip {{ $application->status }}">{{ ucfirst(str_replace('_',' ',$application->status)) }}</span></td></tr>
@empty
<tr><td colspan="3"><div class="admin-empty"><i class="fas fa-briefcase"></i><strong>No recent applications</strong><span>Explore opportunities and start applying.</span>@if(Route::has('jobs.index'))<a class="btn btn-primary btn-sm" href="{{ route('jobs.index') }}">Browse Jobs</a>@endif</div></td></tr>
@endforelse
</tbody></table></div></div></div>
</section>

<section class="eh-tab-pane" data-eh-pane="calendar">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Event</th><th>Starts</th></tr></thead><tbody>
@forelse($upcomingEvents as $event)
<tr><td><strong>{{ $event->title }}</strong></td><td>{{ optional($event->starts_at)->format('d M Y H:i') ?: '-' }}</td></tr>
@empty
<tr><td colspan="2"><div class="admin-empty"><i class="fas fa-calendar"></i><strong>No upcoming events</strong><span>Your upcoming activities will appear here.</span></div></td></tr>
@endforelse
</tbody></table></div></div></div>
</section>

<section class="eh-tab-pane" data-eh-pane="notifications">
<div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Notification</th><th>Received</th></tr></thead><tbody>
@forelse($notifications as $notification)
<tr><td><strong>{{ $notification->title }}</strong>@if($notification->message)<small class="admin-cell-hint">{{ Str::limit($notification->message,120) }}</small>@endif</td><td>{{ optional($notification->created_at)->diffForHumans() }}</td></tr>
@empty
<tr><td colspan="2"><div class="admin-empty"><i class="fas fa-bell-slash"></i><strong>No recent notifications</strong><span>You are all caught up.</span></div></td></tr>
@endforelse
</tbody></table></div></div></div>
</section>


</div>
</div>
@include('participant.partials.phase25-dashboard-cards')

@endsection
