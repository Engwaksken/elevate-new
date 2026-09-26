@extends('layouts.admin')
@section('title','Course Assignments | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Programme Delivery</span><h1>Course Assignments</h1><p>Assign instructors and cohorts to courses.</p></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<div class="admin-stats-grid compact">
@foreach([['courses','Courses','fa-graduation-cap'],['assigned','With Instructor','fa-user-tie'],['unassigned','Without Instructor','fa-user-clock'],['cohort_linked','Linked to Cohort','fa-users-rectangle']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar"><div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search course title or code..."></div><button class="btn btn-primary btn-sm">Search</button><a href="{{ route('admin.elearning.assignments.index') }}" class="btn btn-outline btn-sm">Reset</a></form>
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Course</th><th>Instructors</th><th>Cohorts</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($courses as $course)
<tr><td><strong>{{ $course->title }}</strong><small class="admin-cell-hint">{{ $course->code }}</small></td><td>{{ $course->instructors_count }}</td><td>{{ $course->cohorts_count }}</td><td class="table-actions"><a class="btn-icon" href="{{ route('admin.elearning.assignments.edit',$course) }}"><i class="fas fa-pen"></i></a></td></tr>
@empty<tr><td colspan="4"><div class="admin-empty">No courses found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $courses->links() }}</div>
</div>
@endsection
