@extends('layouts.admin')
@section('title','Event MEAL Report | ElevateHer360')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">MEAL Reporting</span><h1>Event Participation & Evaluation</h1><p>MEAL-ready event outputs covering registrations, attendance and participant feedback.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.events.meal-report.csv',request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Export CSV</a></div>
</div>
<div class="admin-panel"><form method="GET" class="admin-toolbar"><div><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div><div><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div><button class="btn btn-primary btn-sm">Apply</button></form></div>
<div class="admin-stats-grid compact">
@foreach([['events','Events','fa-calendar-days'],['registered','Registered','fa-users'],['attended','Attended','fa-user-check'],['feedback','Feedback Responses','fa-comments'],['avg_satisfaction','Avg Satisfaction / 5','fa-star']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format((float)($stats[$k]??0),$k==='avg_satisfaction'?2:0) }}</strong></div></div>
@endforeach
</div>
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>Event</th><th>Date</th><th>Registered</th><th>Attended</th><th>Attendance %</th><th>Feedback</th><th>Overall</th><th>Relevance</th><th>Recommend</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td><strong>{{ $row['event']->title }}</strong><small class="admin-cell-hint">{{ ucwords(str_replace('_',' ',$row['event']->event_type)) }}</small></td><td>{{ $row['event']->starts_at->format('d M Y') }}</td><td>{{ $row['registered'] }}</td><td>{{ $row['attended'] }}</td><td>{{ number_format($row['attendance_rate'],1) }}%</td><td>{{ $row['feedback_count'] }}</td><td>{{ number_format($row['overall_rating'],2) }}</td><td>{{ number_format($row['relevance_rating'],2) }}</td><td>{{ number_format($row['recommend_rating'],2) }}</td></tr>
@empty<tr><td colspan="9"><div class="admin-empty">No events in the selected period.</div></td></tr>@endforelse
</tbody></table></div></div>
@endsection
