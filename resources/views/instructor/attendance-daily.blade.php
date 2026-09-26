@extends('layouts.admin')
@section('title','Daily Attendance | ElevateHer360')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Course Attendance</span><h1>Daily Attendance — {{ $course->title }}</h1><p>Track attendance by participant and training day.</p></div>
<div class="admin-page-actions">
<a href="{{ route('instructor.attendance.create',$course) }}" class="btn btn-primary"><i class="fas fa-plus"></i> Record Attendance</a>
</div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
<div><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
<button class="btn btn-primary btn-sm">Apply</button>
</form>

<div class="admin-table-wrap">
<table class="admin-table attendance-matrix">
<thead>
<tr><th>Participant</th>
@foreach($sessions as $session)<th>{{ $session->session_date->format('d M') }}</th>@endforeach
<th>P</th><th>A</th><th>L</th><th>E</th>
</tr>
</thead>
<tbody>
@forelse($matrix as $row)
<tr>
<td><strong>{{ $row['user']?->name }}</strong><small class="admin-cell-hint">{{ $row['user']?->email }}</small></td>
@foreach($sessions as $session)
@php($status=$row['days'][$session->session_date->format('Y-m-d')] ?? null)
<td><span class="attendance-dot {{ $status ?: 'none' }}" title="{{ $status ?: 'No record' }}">{{ $status ? strtoupper(substr($status,0,1)) : '—' }}</span></td>
@endforeach
<td>{{ $row['present'] }}</td><td>{{ $row['absent'] }}</td><td>{{ $row['late'] }}</td><td>{{ $row['excused'] }}</td>
</tr>
@empty<tr><td colspan="{{ $sessions->count()+5 }}"><div class="admin-empty">No enrolled participants found.</div></td></tr>@endforelse
</tbody>
</table>
</div>
</div>
@endsection
