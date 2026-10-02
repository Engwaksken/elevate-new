@php
    $formKey = $slot?->id ?? 'new';
    $restore = (string) old('timetable_form') === (string) $formKey;
    $start = $slot?->starts_at?->setTimezone($slot->timezone);
    $end = $slot?->ends_at?->setTimezone($slot->timezone);
    $value = fn ($name, $default = '') => $restore ? old($name, $default) : $default;
    $zone = $value('timezone', $slot?->timezone ?? 'Africa/Kampala');
@endphp
<form method="POST" action="{{ $slot ? route('instructor.courses.timetable.update', [$course, $slot]) : route('instructor.courses.timetable.store', $course) }}" class="icm-form-grid">
    @csrf
    @if($slot) @method('PUT') @endif
    <input type="hidden" name="timetable_form" value="{{ $formKey }}">
    <div class="full"><label for="slot-title-{{ $formKey }}">Session title *</label><input id="slot-title-{{ $formKey }}" name="title" required maxlength="190" value="{{ $value('title', $slot?->title) }}" placeholder="e.g. Introduction to digital marketing"></div>
    <div><label for="slot-date-{{ $formKey }}">Date *</label><input id="slot-date-{{ $formKey }}" type="date" name="session_date" required value="{{ $value('session_date', $start?->format('Y-m-d')) }}"></div>
    <div><label for="slot-zone-{{ $formKey }}">Timezone *</label><select id="slot-zone-{{ $formKey }}" name="timezone" required>
        @foreach(timezone_identifiers_list() as $timezone)<option value="{{ $timezone }}" @selected($zone === $timezone)>{{ $timezone }}</option>@endforeach
    </select></div>
    <div><label for="slot-start-{{ $formKey }}">Start time *</label><input id="slot-start-{{ $formKey }}" type="time" name="start_time" required value="{{ $value('start_time', $start?->format('H:i')) }}"></div>
    <div><label for="slot-end-{{ $formKey }}">End time *</label><input id="slot-end-{{ $formKey }}" type="time" name="end_time" required value="{{ $value('end_time', $end?->format('H:i')) }}"></div>
    <div><label for="slot-venue-{{ $formKey }}">Venue / room</label><input id="slot-venue-{{ $formKey }}" name="venue" maxlength="190" value="{{ $value('venue', $slot?->venue) }}"></div>
    <div><label for="slot-link-{{ $formKey }}">Online meeting link</label><input id="slot-link-{{ $formKey }}" type="url" name="meeting_link" maxlength="2000" value="{{ $value('meeting_link', $slot?->meeting_link) }}" placeholder="https://..."></div>
    <div class="full"><label for="slot-notes-{{ $formKey }}">Notes for participants</label><textarea id="slot-notes-{{ $formKey }}" name="notes" maxlength="10000">{{ $value('notes', $slot?->notes) }}</textarea></div>
    <div><label for="slot-status-{{ $formKey }}">Status</label><select id="slot-status-{{ $formKey }}" name="status">
        <option value="scheduled" @selected($value('status', $slot?->status ?? 'scheduled') === 'scheduled')>Scheduled</option>
        <option value="cancelled" @selected($value('status', $slot?->status ?? 'scheduled') === 'cancelled')>Cancelled</option>
    </select></div>
    <div><button class="btn btn-primary">{{ $slot ? 'Save changes' : 'Add time slot' }}</button></div>
</form>
