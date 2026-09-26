@extends('layouts.admin')
@section('title','Attendance Analytics | ElevateHer360')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Monitoring & Attendance</span><h1>Attendance Analytics</h1><p>Combined reporting across course training sessions and events.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.attendance-analytics.csv',request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Export CSV</a></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div><label>From</label><input type="date" name="from" value="{{ $from->format('Y-m-d') }}"></div>
<div><label>To</label><input type="date" name="to" value="{{ $to->format('Y-m-d') }}"></div>
<button class="btn btn-primary btn-sm">Apply</button>
<a href="{{ route('admin.attendance-analytics.index') }}" class="btn btn-outline btn-sm">Current Month</a>
</form>
</div>

<div class="admin-stats-grid compact">
@foreach([
['course_sessions','Course Sessions','fa-chalkboard-user'],
['course_records','Course Attendance Records','fa-clipboard-user'],
['event_records','Event Attendance Records','fa-calendar-check'],
['total_present','Total Present / Late','fa-user-check'],
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]??0) }}</strong></div></div>
@endforeach
</div>

<div class="attendance-analytics-grid">
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Course Attendance</h2><p>{{ $stats['course_present'] }} present/late record(s).</p></div></div>
@foreach(['present','late','absent','excused'] as $status)
<div class="attendance-breakdown-row"><span>{{ ucfirst($status) }}</span><strong>{{ number_format($courseBreakdown[$status]??0) }}</strong></div>
@endforeach
</section>

<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Event Attendance</h2><p>{{ $stats['event_present'] }} present/late record(s).</p></div></div>
@foreach(['present','late','absent','excused'] as $status)
<div class="attendance-breakdown-row"><span>{{ ucfirst($status) }}</span><strong>{{ number_format($eventBreakdown[$status]??0) }}</strong></div>
@endforeach
</section>
</div>
@endsection
