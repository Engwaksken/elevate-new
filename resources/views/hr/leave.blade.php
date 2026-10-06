@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','My Leave | ElevateHer360')
@section('content')
@php
    $statusLabels = ['draft' => 'Draft', 'submitted' => 'Awaiting supervisor', 'supervisor_approved' => 'Awaiting HR', 'hr_approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
@endphp

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Self-service</span>
    <h1>My Leave</h1>
    <p>Request time off and follow each request through supervisor and HR approval.</p>
</div>
<div class="admin-page-actions">
    @if($employee)<x-export-buttons />@endif
    @if($employee && $leaveTypes->isNotEmpty())<button type="button" class="btn btn-primary" data-modal-open="leave-new"><i class="fas fa-plus"></i> Request leave</button>@endif
</div>
</div>

@if(! $employee)
<div class="admin-panel">
    <div class="admin-empty"><i class="fas fa-id-badge"></i><strong>Your staff record isn’t set up yet</strong><span>Leave is tracked against your HR employee record. Please ask HR to add you under Employees, then you can request leave here.</span></div>
</div>
@else

<div class="admin-stats-grid compact">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-hourglass-half"></i></span><div><small>Awaiting approval</small><strong>{{ number_format($stats['pending']) }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-circle-check"></i></span><div><small>Approved this year</small><strong>{{ number_format($stats['approved']) }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-umbrella-beach"></i></span><div><small>Days taken this year</small><strong>{{ rtrim(rtrim(number_format($stats['days'], 1), '0'), '.') }}</strong></div></div>
</div>

<div class="lv-layout">
<section class="admin-panel">
    <div class="lv-head"><h2>My requests</h2></div>
    <div class="lv-list">
    @forelse($requests as $leave)
        <article class="lv-card lv-{{ $leave->status }}">
            <div class="lv-card-top">
                <strong>{{ $leave->leaveType?->name ?? 'Leave' }}</strong>
                <span class="status-chip {{ $leave->status }}">{{ $statusLabels[$leave->status] ?? ucfirst(str_replace('_', ' ', $leave->status)) }}</span>
            </div>
            <div class="lv-dates"><i class="fas fa-calendar-days"></i> {{ $leave->start_date->format('D d M Y') }} @if(! $leave->start_date->isSameDay($leave->end_date)) – {{ $leave->end_date->format('D d M Y') }} @endif <span>· {{ rtrim(rtrim(number_format((float) $leave->days_requested, 1), '0'), '.') }} {{ \Illuminate\Support\Str::plural('day', (float) $leave->days_requested) }}</span></div>
            @if($leave->reason)<p class="lv-reason">{{ $leave->reason }}</p>@endif
            @if($leave->decision_notes)<p class="lv-note"><i class="fas fa-comment-dots"></i> {{ $leave->decision_notes }}</p>@endif
            <ol class="lv-steps" aria-label="Approval progress">
                @php $step = ['submitted' => 1, 'supervisor_approved' => 2, 'hr_approved' => 3][$leave->status] ?? 0; @endphp
                <li class="{{ $step >= 1 ? 'done' : '' }}">Submitted</li>
                <li class="{{ $step >= 2 ? 'done' : ($step === 1 ? 'current' : '') }}">Supervisor</li>
                <li class="{{ $step >= 3 ? 'done' : ($step === 2 ? 'current' : '') }}">HR</li>
            </ol>
        </article>
    @empty
        <div class="admin-empty"><i class="fas fa-umbrella-beach"></i><strong>No leave requests yet</strong><span>Use “Request leave” to apply for time off.</span></div>
    @endforelse
    </div>
    <div class="admin-pagination">{{ $requests->links() }}</div>
</section>

<aside class="admin-panel">
    <div class="lv-head"><h2>Days left in {{ now()->year }}</h2></div>
    @forelse($leaveTypes as $type)
        <div class="lv-balance"><span>{{ $type->name }}</span><strong>{{ rtrim(rtrim(number_format($balances[$type->id] ?? 0, 1), '0'), '.') }}</strong></div>
    @empty
        <p class="lv-muted">No leave types have been set up yet. Please contact HR.</p>
    @endforelse
    <p class="lv-muted">Pending requests count against these days until they’re decided.</p>
</aside>
</div>

{{-- Request leave --}}
<div class="eh-modal" id="leave-new" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="leave-new-title" @if($errors->any()) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('hr.leave.store') }}">
    @csrf
    <div class="eh-modal-header">
        <div><h2 id="leave-new-title">Request leave</h2><p>Your supervisor reviews it first, then HR gives final approval.</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any())<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
        <div class="modal-grid">
            <div class="form-group full"><label for="leave-type">Leave type *</label>
                <select id="leave-type" name="leave_type_id" required>
                    @foreach($leaveTypes as $type)<option value="{{ $type->id }}" @selected((int) old('leave_type_id') === $type->id)>{{ $type->name }} ({{ rtrim(rtrim(number_format($balances[$type->id] ?? 0, 1), '0'), '.') }} days left)</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label for="leave-start">First day *</label><input id="leave-start" type="date" name="start_date" value="{{ old('start_date') }}" min="{{ today()->toDateString() }}" required></div>
            <div class="form-group"><label for="leave-end">Last day *</label><input id="leave-end" type="date" name="end_date" value="{{ old('end_date') }}" min="{{ today()->toDateString() }}" required></div>
            <div class="form-group full"><label for="leave-reason">Reason</label><textarea id="leave-reason" name="reason" rows="3" maxlength="2000">{{ old('reason') }}</textarea></div>
        </div>
        <p class="lv-muted">Weekends aren’t counted as leave days.</p>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Submit request</button>
    </div>
</form>
</div>
</div>
@endif

<style>
.lv-layout{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:14px;align-items:start}
.lv-head h2{margin:0 0 12px;font-size:1rem}
.lv-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
.lv-card{display:flex;flex-direction:column;gap:6px;padding:12px 14px;border:1px solid #e4e7ec;border-left:3px solid #f79009;border-radius:10px;background:#fff;min-width:0}
.lv-card.lv-hr_approved{border-left-color:#12b76a}
.lv-card.lv-rejected,.lv-card.lv-cancelled{border-left-color:#d92d20}
.lv-card-top{display:flex;justify-content:space-between;align-items:center;gap:8px}
.lv-dates{color:#344054;font-size:.8rem}
.lv-dates span{color:#667085}
.lv-dates i{margin-right:4px;color:#800000}
.lv-reason{margin:0;color:#475467;font-size:.78rem}
.lv-note{margin:0;padding:6px 9px;border-radius:7px;background:#fffaeb;color:#7a2e0e;font-size:.76rem}
.lv-steps{display:flex;margin:4px 0 0;padding:0;list-style:none}
.lv-steps li{flex:1;position:relative;padding-top:16px;color:#98a2b3;font-size:.66rem;font-weight:700;text-align:center}
.lv-steps li::before{content:"";position:absolute;top:4px;left:50%;width:9px;height:9px;margin-left:-4.5px;border-radius:50%;background:#d0d5dd;z-index:1}
.lv-steps li::after{content:"";position:absolute;top:8px;left:-50%;width:100%;height:2px;background:#e4e7ec}
.lv-steps li:first-child::after{display:none}
.lv-steps li.done,.lv-steps li.current{color:#067647}
.lv-steps li.done::before{background:#12b76a}
.lv-steps li.done::after,.lv-steps li.current::after{background:#12b76a}
.lv-steps li.current::before{background:#fff;border:2px solid #12b76a;box-sizing:border-box}
.lv-balance{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px dashed #eef0f3;font-size:.84rem;color:#344054}
.lv-balance strong{font-size:1rem;color:#172033}
.lv-muted{margin:10px 0 0;color:#667085;font-size:.74rem}
@media(max-width:1100px){.lv-list{grid-template-columns:1fr}}
@media(max-width:900px){.lv-layout{grid-template-columns:1fr}}
</style>
@endsection
