@extends('layouts.admin')
@section('title','Participant Attendance Summary | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Monitoring & Attendance</span><h1>Participant Attendance Summary</h1><p>Overall attendance performance for learners with recorded course attendance.</p></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar eh-filter-row">
<div class="search-box"><i class="fas fa-search"></i><input name="search" value="{{ request('search') }}" placeholder="Search participant name or email..."></div>
<button class="btn btn-primary btn-sm">Search</button>
<a href="{{ route('admin.participant-attendance-summary.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Participant</th><th>Total Records</th><th>Present / Late</th><th>Late</th><th>Absent</th><th>Attendance %</th></tr></thead>
<tbody>
@forelse($participants as $participant)
@php($rate=(int)$participant->attendance_total>0 ? round(((int)$participant->attendance_present/(int)$participant->attendance_total)*100,1) : 0)
<tr>
<td><strong>{{ $participant->name }}</strong><small class="admin-cell-hint">{{ $participant->email }}</small></td>
<td>{{ number_format($participant->attendance_total) }}</td>
<td>{{ number_format($participant->attendance_present) }}</td>
<td>{{ number_format($participant->attendance_late) }}</td>
<td>{{ number_format($participant->attendance_absent) }}</td>
<td><div class="attendance-rate-cell"><strong>{{ number_format($rate,1) }}%</strong><div class="progress-track"><span style="width:{{ min(100,$rate) }}%"></span></div></div></td>
</tr>
@empty<tr><td colspan="6"><div class="admin-empty">No participant attendance data found.</div></td></tr>@endforelse
</tbody></table></div>

<div class="admin-pagination">{{ $participants->links() }}</div>
</div>
@endsection
