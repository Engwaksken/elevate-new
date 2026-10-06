@extends('layouts.app')
@section('title','My Learning - ElevateHer360')
@section('content')
<style>.eh-track-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 22px}.eh-track-card{background:#fff;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px}.eh-track-card i{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:#fff7da;color:#800000}.eh-track-card small{display:block;color:#667085;font-weight:700}.eh-track-card strong{font-size:1.3rem;color:#101828}@media(max-width:900px){.eh-track-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.eh-track-grid{grid-template-columns:1fr}}</style>
<div class="page-header"><div><span class="eh-kicker">Learning</span><h1>My Learning</h1><p>Continue enrolled courses and track your learning progress.</p></div><div class="page-actions"><x-export-buttons /><a href="{{ route('learning.index') }}" class="btn btn-outline">Browse Courses</a><a href="{{ route('calendar.index', ['event_type'=>'course_timetable']) }}" class="btn btn-outline">Calendar</a></div></div>
<div class="eh-track-grid">
@foreach(['enrolled'=>['Enrolled Courses','fa-graduation-cap'],'in_progress'=>['In Progress','fa-spinner'],'completed'=>['Completed','fa-circle-check'],'progress'=>['Overall Progress','fa-chart-line']] as $key=>[$label,$icon])
<div class="eh-track-card"><i class="fas {{ $icon }}"></i><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0, $key === 'progress' ? 1 : 0) }}{{ $key === 'progress' ? '%' : '' }}</strong></div></div>
@endforeach
</div>
<div class="eh-tabs" data-eh-tabs><div class="eh-tab-nav" role="tablist"><button class="eh-tab-button active" data-eh-tab="courses"><i class="fas fa-graduation-cap"></i> My Courses</button><button class="eh-tab-button" data-eh-tab="progress"><i class="fas fa-chart-line"></i> Progress</button></div><div class="eh-tab-content">
<section class="eh-tab-pane active" data-eh-pane="courses"><div class="eh-tab-section"><div class="eh-data-list">
@forelse($enrolments as $enrolment)
<a class="eh-data-row" href="{{ route('learning.course.dashboard', $enrolment->course) }}"><div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-book-open"></i></span><div class="eh-data-row-copy"><strong>{{ $enrolment->course->title }}</strong><span>Status: {{ ucfirst(str_replace('_',' ',$enrolment->status)) }} · {{ number_format((float)$enrolment->progress_percent,0) }}% complete</span></div></div><i class="fas fa-chevron-right"></i></a>
@empty<div class="eh-empty"><h3>No courses yet</h3><p>Browse available courses and start learning.</p><a class="btn btn-primary" href="{{ route('learning.index') }}">Browse Courses</a></div>@endforelse
</div>{{ $enrolments->links() }}</div></section>
<section class="eh-tab-pane" data-eh-pane="progress"><div class="eh-tab-section"><div class="eh-data-list">
@forelse($enrolments as $enrolment)<div class="eh-data-row"><div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-chart-simple"></i></span><div class="eh-data-row-copy"><strong>{{ $enrolment->course->title }}</strong><span>{{ number_format((float)$enrolment->progress_percent,0) }}% complete</span></div></div><strong>{{ number_format((float)$enrolment->progress_percent,0) }}%</strong></div>@empty<p>No learning progress to display yet.</p>@endforelse
</div></div></section>
</div></div>
@endsection
