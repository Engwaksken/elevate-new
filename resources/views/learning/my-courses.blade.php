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
<section class="eh-tab-pane active" data-eh-pane="courses"><div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Course</th><th>Status</th><th>Progress</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($enrolments as $enrolment)
<tr><td><a href="{{ route('learning.course.dashboard', $enrolment->course) }}"><strong>{{ $enrolment->course->title }}</strong></a></td><td><span class="status-chip {{ $enrolment->status }}">{{ ucfirst(str_replace('_',' ',$enrolment->status)) }}</span></td><td>{{ number_format((float)$enrolment->progress_percent,0) }}%</td><td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('learning.course.dashboard', $enrolment->course) }}"><i class="fas fa-arrow-right"></i> Open</a></td></tr>
@empty<tr><td colspan="4"><div class="admin-empty"><strong>No courses yet</strong><span>Browse available courses and start learning.</span><a class="btn btn-primary btn-sm" href="{{ route('learning.index') }}">Browse Courses</a></div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $enrolments->links() }}</div></div></div></section>
<section class="eh-tab-pane" data-eh-pane="progress"><div class="eh-tab-section"><div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Course</th><th>Progress</th><th style="width:40%">Completion</th></tr></thead><tbody>
@forelse($enrolments as $enrolment)<tr><td><strong>{{ $enrolment->course->title }}</strong></td><td>{{ number_format((float)$enrolment->progress_percent,0) }}%</td><td><div style="height:8px;border-radius:999px;background:#f2e6e6;overflow:hidden"><span style="display:block;height:100%;width:{{ max(0,min(100,(float)$enrolment->progress_percent)) }}%;background:#800000"></span></div></td></tr>@empty<tr><td colspan="3"><div class="admin-empty">No learning progress to display yet.</div></td></tr>@endforelse
</tbody></table></div></div></div></section>
</div></div>
@endsection
