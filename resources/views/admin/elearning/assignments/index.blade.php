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
@php
$bulkRoute = route('admin.elearning.assignments.bulk-destroy');
$bulkTableId = 'courseAssignmentsTable';
@endphp
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap"><table class="admin-table" id="{{ $bulkTableId }}"><thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Course</th><th>Instructors</th><th>Cohorts</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($courses as $course)
<tr><td><input type="checkbox" data-row-select value="{{ $course->id }}" aria-label="Select {{ $course->title }}"></td><td><strong>{{ $course->title }}</strong><small class="admin-cell-hint">{{ $course->code }}</small></td><td>{{ $course->instructors_count }}</td><td>{{ $course->cohorts_count }}</td><td class="table-actions"><a class="btn-icon" href="{{ route('admin.elearning.assignments.edit',$course) }}"><i class="fas fa-pen"></i></a></td></tr>
@empty<tr><td colspan="5"><div class="admin-empty">No courses found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $courses->links() }}</div>
</div>
@endsection
