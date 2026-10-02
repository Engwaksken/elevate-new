@extends('layouts.admin')
@section('title', 'Manage Timetable | '.$course->title)
@section('content')
<style>.icm-panel{padding:20px;background:#fff;margin-bottom:20px;border-radius:12px}.icm-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:16px}.icm-card{padding:16px;border:1px solid #ddd;border-radius:10px}.icm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.icm-form-grid .full{grid-column:1/-1}.icm-form-grid input,.icm-form-grid select,.icm-form-grid textarea{width:100%;padding:10px;box-sizing:border-box}.icm-details{margin-top:16px}.icm-notice{padding:12px;background:#fff0c7}@media(max-width:700px){.icm-form-grid{grid-template-columns:1fr}}</style>
<div class="admin-page-header"><div><h1>{{ $course->title }} — Timetable</h1></div><a class="btn btn-outline" href="{{ route('admin.elearning.timetable.index') }}">All timetables</a></div>
@include('instructor.partials.timetable')
@endsection
