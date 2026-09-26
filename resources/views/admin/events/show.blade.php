@extends('layouts.admin')
@section('title',$event->title.' | Event Operations')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Event Operations</span><h1>{{ $event->title }}</h1><p>{{ $event->starts_at->format('d M Y H:i') }} · {{ $event->venue ?: ucfirst($event->delivery_mode) }}</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.events.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Events</a><a href="{{ route('admin.events.attendance',$event) }}" class="btn btn-primary"><i class="fas fa-user-check"></i> Attendance</a></div>
</div>
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="admin-stats-grid compact">
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-users"></i></span><div><small>Registered</small><strong>{{ $registered }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-user-check"></i></span><div><small>Attended</small><strong>{{ $attended }}</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-percent"></i></span><div><small>Attendance Rate</small><strong>{{ number_format($attendanceRate,1) }}%</strong></div></div>
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-bell"></i></span><div><small>Reminders</small><strong>{{ $reminders->count() }}</strong></div></div>
</div>

<div class="event-ops-grid">
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Quick Check-in QR</h2><p>Display at the event for signed-in participants.</p></div></div><div class="event-qr-wrap">@if($event->checkin_token)<div class="event-qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(190)->margin(1)->generate(route('events.checkin',[$event,$event->checkin_token])) !!}</div>@endif</div></section>
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Event Outputs</h2><p>Feedback, reminders, attendance and MEAL reporting.</p></div></div><div class="event-operation-actions"><a href="{{ route('admin.events.feedback',$event) }}" class="btn btn-outline"><i class="fas fa-comments"></i> Feedback</a><a href="{{ route('admin.events.reminder-logs',$event) }}" class="btn btn-outline"><i class="fas fa-envelope-open-text"></i> Reminder Logs</a><a href="{{ route('admin.events.attendance.csv',$event) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Attendance CSV</a><a href="{{ route('admin.events.meal-report',['from'=>$event->starts_at->format('Y-m-d'),'to'=>$event->starts_at->format('Y-m-d')]) }}" class="btn btn-outline"><i class="fas fa-chart-line"></i> MEAL Report</a></div></section>
</div>

<div class="event-ops-grid">
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Evaluation & Certificate Settings</h2><p>Control participant feedback and certificate eligibility.</p></div></div>
<form method="POST" action="{{ route('admin.events.evaluation-settings',$event) }}">@csrf
<div class="modal-grid">
<div class="form-group full"><div class="eh-choice-grid">
<label class="eh-choice-card"><input type="checkbox" name="feedback_enabled" value="1" @checked($event->feedback_enabled)><span class="eh-choice-card__text">Enable participant feedback</span></label>
<label class="eh-choice-card"><input type="checkbox" name="certificate_enabled" value="1" @checked($event->certificate_enabled)><span class="eh-choice-card__text">Enable attendance certificates</span></label>
<label class="eh-choice-card"><input type="checkbox" name="certificate_requires_feedback" value="1" @checked($event->certificate_requires_feedback)><span class="eh-choice-card__text">Require feedback before certificate</span></label>
</div></div>
<div class="form-group full"><label>Certificate Title</label><input name="certificate_title" value="{{ $event->certificate_title }}" placeholder="e.g. Certificate of Attendance"><small class="form-hint">Optional. Defaults to Certificate of Attendance.</small></div>
<div class="form-group"><label>Signatory Name</label><input name="certificate_signatory_name" value="{{ $event->certificate_signatory_name }}" placeholder="e.g. Executive Director"></div>
<div class="form-group"><label>Signatory Title</label><input name="certificate_signatory_title" value="{{ $event->certificate_signatory_title }}" placeholder="e.g. Executive Director, WITU"></div>
</div>
<div class="eh-form-actions"><button class="btn btn-primary">Save Settings</button></div>
</form>
</section>

<section class="admin-panel"><div class="admin-panel-head"><div><h2>Calendar & Reminders</h2><p>Use the existing calendar and reminder tools for this event.</p></div></div><div class="event-operation-actions"><a href="{{ route('admin.events.calendar',['month'=>$event->starts_at->format('Y-m')]) }}" class="btn btn-outline"><i class="fas fa-calendar-days"></i> Month Calendar</a><a href="{{ route('events.calendar',$event) }}" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Download .ics</a></div></section>
</div>
@endsection
