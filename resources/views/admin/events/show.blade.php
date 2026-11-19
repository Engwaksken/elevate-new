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

<div class="appraisal-admin-kra-tabs" data-event-tabs>
<button type="button" class="appraisal-admin-kra-tab active" data-event-tab="checkin"><i class="fas fa-qrcode"></i> Check-in</button>
<button type="button" class="appraisal-admin-kra-tab" data-event-tab="outputs"><i class="fas fa-chart-line"></i> Outputs</button>
<button type="button" class="appraisal-admin-kra-tab" data-event-tab="people"><i class="fas fa-users"></i> People &amp; Appreciation</button>
<button type="button" class="appraisal-admin-kra-tab" data-event-tab="settings"><i class="fas fa-gears"></i> Settings &amp; Reminders</button>
</div>

@php
    $attendedIds = $people->filter(fn ($row) => in_array($row['attendance'], ['present', 'late'], true))->map(fn ($row) => $row['user']->id)->values();
    $defaultThanks = "Thank you for being part of {$event->title}. Your time, energy and contributions made a real difference, and we are grateful to have you with us.";
@endphp
<div class="appraisal-admin-kra-panel" data-event-panel="people">
@if($errors->any())<div class="alert alert-error" role="alert">{{ $errors->first() }}</div>@endif
<div class="event-ops-grid">
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Invite People</h2><p>Invite staff or participants. Each person gets an in-app notification and an email.</p></div></div>
<form method="POST" action="{{ route('admin.events.invitations.store', $event) }}">@csrf
<div class="form-group"><label>People to invite</label>
<x-people-picker id="event-invitees" name="invitee_ids" :required="true" :source="route('admin.events.people', ['event' => $event->id])" :groups="$inviteGroups" :exclude="$event->invitations->pluck('user_id')->all()" placeholder="Search people by name or email, or add a whole group above…" /></div>
<div class="form-group"><label for="invitation-note">Personal note (optional)</label><textarea id="invitation-note" name="invitation_note" rows="3" maxlength="2000" placeholder="Add a short note to the invitation…">{{ old('invitation_note') }}</textarea></div>
<div class="eh-form-actions"><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send Invitations</button></div>
</form>
</section>

<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Appreciation Messages</h2><p>Select people in the list below, then thank them all at once.</p></div></div>
<div class="event-operation-actions">
<button type="button" class="btn btn-outline btn-sm" data-appreciation-select="all"><i class="fas fa-check-double"></i> Select everyone</button>
<button type="button" class="btn btn-outline btn-sm" data-appreciation-select="attended" @disabled($attendedIds->isEmpty())><i class="fas fa-user-check"></i> Select attendees ({{ $attendedIds->count() }})</button>
<button type="button" class="btn btn-outline btn-sm" data-appreciation-select="none"><i class="fas fa-xmark"></i> Clear</button>
<button type="button" class="btn btn-primary btn-sm" data-modal-open="appreciation-modal" data-appreciation-open disabled><i class="fas fa-heart"></i> <span>Send Appreciation (<b data-appreciation-count>0</b>)</span></button>
</div>
</section>
</div>

<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Invited &amp; Registered</h2><p>{{ $people->count() }} {{ \Illuminate\Support\Str::plural('person', $people->count()) }} connected to this event.</p></div></div>
<div class="admin-table-wrap"><table class="admin-table" id="event-people-table"><thead><tr>
<th style="width:34px"><input type="checkbox" data-appreciation-all aria-label="Select everyone"></th><th>Name</th><th>Type</th><th>Invitation</th><th>Registration</th><th>Attendance</th><th class="table-actions">Actions</th>
</tr></thead><tbody>
@forelse($people as $row)
@php $person = $row['user']; @endphp
<tr>
<td><input type="checkbox" form="appreciation-form" name="user_ids[]" value="{{ $person->id }}" data-appreciation-row data-attended="{{ in_array($row['attendance'], ['present', 'late'], true) ? 1 : 0 }}" aria-label="Select {{ $person->name }}"></td>
<td><strong>{{ $person->name }}</strong><small class="admin-cell-hint">{{ $person->email }}</small></td>
<td>{{ ucfirst((string) $person->user_type) }}</td>
<td>@if($row['invitation'])<span class="status-chip {{ $row['invitation']->status === 'registered' ? 'active' : 'pending' }}">{{ ucfirst($row['invitation']->status) }}</span><small class="admin-cell-hint">{{ $row['invitation']->created_at?->format('d M Y') }}{{ $row['invitation']->inviter ? ' · by '.$row['invitation']->inviter->name : '' }}</small>@else<span class="admin-cell-hint">Not invited</span>@endif</td>
<td>{{ $row['registration'] ? ucfirst((string) $row['registration']->status) : '—' }}</td>
<td>{{ $row['attendance'] ? ucfirst(str_replace('_', ' ', $row['attendance'])) : 'Not recorded' }}</td>
<td class="table-actions">@if($row['invitation'])<form method="POST" action="{{ route('admin.events.invitations.destroy', [$event, $row['invitation']]) }}">@csrf @method('DELETE')<button class="btn-icon danger" title="Withdraw invitation" aria-label="Withdraw invitation for {{ $person->name }}"><i class="fas fa-user-xmark"></i></button></form>@endif</td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty">Nobody has been invited or registered yet.</div></td></tr>
@endforelse
</tbody></table></div>
</section>
</div>

<div class="eh-modal" id="appreciation-modal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="appreciation-title"><div class="eh-modal-dialog">
<form method="POST" action="{{ route('admin.events.appreciation.send', $event) }}" id="appreciation-form">@csrf
<div class="eh-modal-header"><div><h2 id="appreciation-title">Send Appreciation</h2><p>To <span data-appreciation-count>0</span> selected {{ \Illuminate\Support\Str::plural('person') }}.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label for="appreciation-subject">Title *</label><input id="appreciation-subject" name="title" maxlength="190" required value="{{ old('title', 'Thank you for joining '.$event->title) }}"></div>
<div class="form-group full"><label for="appreciation-message">Message *</label><textarea id="appreciation-message" name="message" rows="6" maxlength="5000" required>{{ old('message', $defaultThanks) }}</textarea></div>
<div class="form-group full"><label class="eh-choice-card"><input type="checkbox" name="send_email" value="1" checked><span class="eh-choice-card__text">Also send by email</span></label><small class="form-hint">Everyone selected always receives an in-app notification.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-heart"></i> Send Appreciation</button></div>
</form></div></div>

<div class="appraisal-admin-kra-panel active" data-event-panel="checkin">
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Quick Check-in QR</h2><p>Display at the event for signed-in participants.</p></div></div><div class="event-qr-wrap">@if($event->checkin_token)<div class="event-qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(190)->margin(1)->generate(route('events.checkin',[$event,$event->checkin_token])) !!}</div>@endif</div></section>
</div>

<div class="appraisal-admin-kra-panel" data-event-panel="outputs">
<section class="admin-panel"><div class="admin-panel-head"><div><h2>Event Outputs</h2><p>Feedback, reminders, attendance and MEAL reporting.</p></div></div><div class="event-operation-actions"><a href="{{ route('admin.events.feedback',$event) }}" class="btn btn-outline"><i class="fas fa-comments"></i> Feedback</a><a href="{{ route('admin.events.reminder-logs',$event) }}" class="btn btn-outline"><i class="fas fa-envelope-open-text"></i> Reminder Logs</a><a href="{{ route('admin.events.attendance.csv',$event) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Attendance CSV</a><a href="{{ route('admin.events.meal-report',['from'=>$event->starts_at->format('Y-m-d'),'to'=>$event->starts_at->format('Y-m-d')]) }}" class="btn btn-outline"><i class="fas fa-chart-line"></i> MEAL Report</a></div></section>
</div>

<div class="appraisal-admin-kra-panel" data-event-panel="settings">
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

@php
    $reminderLabel = function (int $minutes): string {
        return match (true) {
            $minutes % 10080 === 0 => ($minutes / 10080).' '.\Illuminate\Support\Str::plural('week', $minutes / 10080),
            $minutes % 1440 === 0 => ($minutes / 1440).' '.\Illuminate\Support\Str::plural('day', $minutes / 1440),
            $minutes % 60 === 0 => ($minutes / 60).' '.\Illuminate\Support\Str::plural('hour', $minutes / 60),
            default => $minutes.' '.\Illuminate\Support\Str::plural('minute', $minutes),
        };
    };
    $methodLabel = ['email' => 'Email', 'system' => 'In-app', 'both' => 'Email + in-app'];
@endphp
<section class="admin-panel">
<div class="admin-panel-head"><div><h2>Automatic Reminders</h2><p>Sent automatically to everyone registered for or invited to this event, staff and participants alike.</p></div><a href="{{ route('admin.events.reminder-logs',$event) }}" class="btn btn-outline btn-sm"><i class="fas fa-envelope-open-text"></i> Delivery log</a></div>
@if($reminders->isEmpty())
<div class="admin-empty">No reminders. Add one below so people are reminded before the event.</div>
@else
<div class="admin-table-wrap"><table class="admin-table"><thead><tr><th>When</th><th>Channel</th><th>Sends at</th><th class="table-actions">Actions</th></tr></thead><tbody>
@foreach($reminders as $reminder)
@php $sendsAt = $event->starts_at->copy()->subMinutes($reminder->minutes_before); @endphp
<tr><td><strong>{{ $reminderLabel((int) $reminder->minutes_before) }} before</strong></td><td>{{ $methodLabel[$reminder->delivery_method] ?? ucfirst($reminder->delivery_method) }}</td><td>{{ $sendsAt->format('d M Y H:i') }}@if($sendsAt->isPast())<small class="admin-cell-hint">{{ $event->starts_at->isPast() ? 'Event has started' : 'Due, sending now' }}</small>@endif</td>
<td class="table-actions"><form method="POST" action="{{ route('admin.events.reminders.destroy',[$event,$reminder]) }}">@csrf @method('DELETE')<button class="btn-icon danger" title="Remove reminder" aria-label="Remove the {{ $reminderLabel((int) $reminder->minutes_before) }} reminder"><i class="fas fa-trash"></i></button></form></td></tr>
@endforeach
</tbody></table></div>
@endif
<form method="POST" action="{{ route('admin.events.reminders.store',$event) }}" class="modal-grid" style="margin-top:14px">@csrf
<div class="form-group"><label for="reminder-preset">Add a reminder</label><select id="reminder-preset" name="preset" data-reminder-preset>
@foreach(['same_day'=>'3 hours before','1_day'=>'1 day before','2_days'=>'2 days before','3_days'=>'3 days before','7_days'=>'1 week before','14_days'=>'2 weeks before','custom'=>'Custom…'] as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
</select></div>
<div class="form-group"><label for="reminder-method">Send by</label><select id="reminder-method" name="delivery_method">@foreach($methodLabel as $value=>$label)<option value="{{ $value }}" @selected($value==='both')>{{ $label }}</option>@endforeach</select></div>
<div class="form-group" data-reminder-custom hidden><label for="reminder-custom-value">Custom time before</label><input id="reminder-custom-value" type="number" name="custom_value" min="1" placeholder="e.g. 30"></div>
<div class="form-group" data-reminder-custom hidden><label for="reminder-custom-unit">Unit</label><select id="reminder-custom-unit" name="custom_unit">@foreach(['minutes','hours','days','weeks'] as $unit)<option value="{{ $unit }}">{{ ucfirst($unit) }}</option>@endforeach</select></div>
<div class="form-group full"><button class="btn btn-primary btn-sm"><i class="fas fa-bell"></i> Add Reminder</button></div>
</form>
</section>

<section class="admin-panel"><div class="admin-panel-head"><div><h2>Calendar</h2><p>Add this event to a calendar.</p></div></div><div class="event-operation-actions"><a href="{{ route('admin.events.calendar',['month'=>$event->starts_at->format('Y-m')]) }}" class="btn btn-outline"><i class="fas fa-calendar-days"></i> Month Calendar</a><a href="{{ route('events.calendar',$event) }}" class="btn btn-outline"><i class="fas fa-calendar-plus"></i> Download .ics</a></div></section>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-event-tabs]');
    if (!root) return;
    const buttons = [...root.querySelectorAll('[data-event-tab]')];
    const panels = [...document.querySelectorAll('[data-event-panel]')];
    const activate = (button) => {
        buttons.forEach((b) => b.classList.toggle('active', b === button));
        panels.forEach((p) => p.classList.toggle('active', p.dataset.eventPanel === button.dataset.eventTab));
    };
    buttons.forEach((button) => button.addEventListener('click', () => activate(button)));
    // Invitations and appreciation redirect back to #people; validation errors also land there.
    const wanted = location.hash.slice(1) || @json($errors->any() ? 'people' : '');
    const start = buttons.find((b) => b.dataset.eventTab === wanted);
    if (start) activate(start);

    const preset = document.querySelector('[data-reminder-preset]');
    const syncPreset = () => document.querySelectorAll('[data-reminder-custom]').forEach((el) => {
        el.hidden = preset.value !== 'custom';
        el.querySelectorAll('input').forEach((i) => { i.required = preset.value === 'custom'; });
    });
    preset?.addEventListener('change', syncPreset);
    if (preset) syncPreset();

    // Appreciation: row checkboxes post with the modal form (form="appreciation-form").
    const rows = [...document.querySelectorAll('[data-appreciation-row]')];
    const all = document.querySelector('[data-appreciation-all]');
    const openBtn = document.querySelector('[data-appreciation-open]');
    const sync = () => {
        const n = rows.filter((r) => r.checked).length;
        document.querySelectorAll('[data-appreciation-count]').forEach((el) => { el.textContent = n; });
        if (openBtn) openBtn.disabled = n === 0;
        if (all) { all.checked = n > 0 && n === rows.length; all.indeterminate = n > 0 && n < rows.length; }
    };
    rows.forEach((r) => r.addEventListener('change', sync));
    all?.addEventListener('change', () => { rows.forEach((r) => { r.checked = all.checked; }); sync(); });
    document.querySelectorAll('[data-appreciation-select]').forEach((btn) => btn.addEventListener('click', () => {
        const mode = btn.dataset.appreciationSelect;
        rows.forEach((r) => { r.checked = mode === 'all' || (mode === 'attended' && r.dataset.attended === '1'); });
        sync();
    }));
    sync();
});
</script>
@endsection
