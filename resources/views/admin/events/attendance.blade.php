@extends('layouts.admin')
@section('title','Event Attendance | ElevateHer360')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Events</span><h1>Attendance — {{ $event->title }}</h1><p>{{ $event->starts_at->format('d M Y H:i') }}</p></div><div class="admin-page-actions"><a href="{{ route('admin.events.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Events</a></div></div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@php($records=$event->attendanceRecords->keyBy('event_registration_id'))
<form method="POST" action="{{ route('admin.events.attendance.save',$event) }}">@csrf
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Participant</th><th>Email</th><th>Status</th><th>Notes</th></tr></thead><tbody>
@forelse($event->registrations as $registration)
@php($record=$records->get($registration->id))
<tr><td><strong>{{ $registration->user?->name ?: 'Participant' }}</strong></td><td>{{ $registration->user?->email ?: '—' }}</td><td><select name="attendance[{{ $registration->id }}][status]">@foreach(['present','late','absent','excused'] as $status)<option value="{{ $status }}" @selected(($record?->attendance_status ?: 'present')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></td><td><input name="attendance[{{ $registration->id }}][notes]" value="{{ $record?->notes }}" placeholder="Optional note..."></td></tr>
@empty<tr><td colspan="4"><div class="admin-empty">No registrations yet.</div></td></tr>@endforelse
</tbody></table></div></div>
@if($event->registrations->isNotEmpty())<div class="admin-page-actions"><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Attendance</button></div>@endif
</form>
@endsection