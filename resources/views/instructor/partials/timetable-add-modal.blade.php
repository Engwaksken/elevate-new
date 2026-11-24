{{--
    "Add time slots" modal: one set of session details shared by several
    date/time rows. "Add another slot" copies the last row one week later.
--}}
@php
    $restore = (string) old('timetable_form') === 'batch';
    $rows = $restore && is_array(old('slots')) ? array_values(old('slots')) : [['session_date' => '', 'start_time' => '', 'end_time' => '']];
    $zone = $restore ? old('timezone', 'Africa/Kampala') : 'Africa/Kampala';
    $action = route(($staffTimetable ?? false) ? 'admin.elearning.timetable.store' : 'instructor.courses.timetable.store', $course);
@endphp
<div class="eh-modal" id="addTimeSlotsModal" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="addTimeSlotsTitle" @if($restore && $errors->any()) data-modal-autoopen @endif>
    <div class="eh-modal-dialog">
        <form method="POST" action="{{ $action }}" class="eh-modal-form" data-timetable-batch>
            @csrf
            <input type="hidden" name="timetable_form" value="batch">
            <div class="eh-modal-header">
                <div>
                    <h2 id="addTimeSlotsTitle">Add time slots</h2>
                    <p>Enter the session details once, then add one row per date. Times use the selected timezone.</p>
                </div>
                <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
            </div>
            <div class="eh-modal-body">
                @if($restore && $errors->any())
                    <div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>
                @endif

                <div class="icm-form-grid">
                    <div class="full"><label for="batch-title">Session title *</label><input id="batch-title" name="title" required maxlength="190" value="{{ $restore ? old('title') : '' }}" placeholder="e.g. Introduction to digital marketing"></div>
                    <div><label for="batch-zone">Timezone *</label><select id="batch-zone" name="timezone" required>
                        @foreach(timezone_identifiers_list() as $timezone)<option value="{{ $timezone }}" @selected($zone === $timezone)>{{ $timezone }}</option>@endforeach
                    </select></div>
                    <div><label for="batch-venue">Venue / room</label><input id="batch-venue" name="venue" maxlength="190" value="{{ $restore ? old('venue') : '' }}"></div>
                    <div class="full"><label for="batch-link">Online meeting link</label><input id="batch-link" type="url" name="meeting_link" maxlength="2000" value="{{ $restore ? old('meeting_link') : '' }}" placeholder="https://..."></div>
                    <div class="full"><label for="batch-notes">Notes for participants</label><textarea id="batch-notes" name="notes" maxlength="10000">{{ $restore ? old('notes') : '' }}</textarea></div>
                </div>

                <h3 class="tt-rows-title">Dates and times</h3>
                <div class="tt-rows" data-slot-rows>
                    @foreach($rows as $i => $row)
                        <div class="tt-row" data-slot-row>
                            <div><label for="slot-date-{{ $i }}">Date *</label><input id="slot-date-{{ $i }}" type="date" name="slots[{{ $i }}][session_date]" required value="{{ $row['session_date'] ?? '' }}"></div>
                            <div><label for="slot-start-{{ $i }}">Start *</label><input id="slot-start-{{ $i }}" type="time" name="slots[{{ $i }}][start_time]" required value="{{ $row['start_time'] ?? '' }}"></div>
                            <div><label for="slot-end-{{ $i }}">End *</label><input id="slot-end-{{ $i }}" type="time" name="slots[{{ $i }}][end_time]" required value="{{ $row['end_time'] ?? '' }}"></div>
                            <button type="button" class="btn btn-outline btn-sm tt-remove" data-slot-remove aria-label="Remove this slot" @if(count($rows) === 1) hidden @endif><i class="fas fa-xmark"></i></button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-outline btn-sm" data-slot-add><i class="fas fa-plus"></i> Add another slot</button>
                <small class="tt-hint">A new slot copies the last one, one week later. Change the date or times as needed.</small>
            </div>
            <div class="eh-modal-footer">
                <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                <button class="btn btn-primary" data-slot-submit><i class="fas fa-calendar-plus"></i> Add <span data-slot-count>{{ count($rows) }}</span> slot(s)</button>
            </div>
        </form>
    </div>
</div>

<style>
.tt-rows-title{margin:18px 0 8px;font-size:.9rem;color:#172033}
.tt-rows{display:grid;gap:8px;margin-bottom:10px}
.tt-row{display:grid;grid-template-columns:minmax(0,1.4fr) minmax(0,1fr) minmax(0,1fr) auto;gap:8px;align-items:end;padding:10px;border:1px solid #eaecf0;border-radius:8px;background:#fcfcfd}
.tt-row label{display:block;margin-bottom:4px;font-size:.75rem;font-weight:700;color:#344054}
.tt-row input{width:100%;box-sizing:border-box;min-height:38px;padding:6px 10px;border:1px solid #d0d5dd;border-radius:8px}
.tt-remove{min-height:38px}
.tt-hint{display:block;margin-top:6px;color:#667085;font-size:.72rem}
@media(max-width:640px){.tt-row{grid-template-columns:1fr 1fr}.tt-row>div:first-child{grid-column:1/-1}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-timetable-batch]');
    if (!form) return;
    const list = form.querySelector('[data-slot-rows]');
    const count = form.querySelector('[data-slot-count]');

    const renumber = function () {
        const rows = list.querySelectorAll('[data-slot-row]');
        rows.forEach(function (row, index) {
            row.querySelectorAll('input').forEach(function (input) {
                input.name = input.name.replace(/slots\[\d+\]/, 'slots[' + index + ']');
                input.id = input.id.replace(/-\d+$/, '-' + index);
            });
            row.querySelectorAll('label').forEach(function (label) {
                label.htmlFor = label.htmlFor.replace(/-\d+$/, '-' + index);
            });
            row.querySelector('[data-slot-remove]').hidden = rows.length === 1;
        });
        count.textContent = rows.length;
    };

    const nextWeek = function (value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value)) return '';
        const date = new Date(value + 'T00:00:00');
        date.setDate(date.getDate() + 7);
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
    };

    form.querySelector('[data-slot-add]').addEventListener('click', function () {
        const rows = list.querySelectorAll('[data-slot-row]');
        if (rows.length >= 50) return;
        const last = rows[rows.length - 1];
        const copy = last.cloneNode(true);
        const [date, start, end] = copy.querySelectorAll('input');
        const [lastDate, lastStart, lastEnd] = last.querySelectorAll('input');
        date.value = nextWeek(lastDate.value);
        start.value = lastStart.value;
        end.value = lastEnd.value;
        list.appendChild(copy);
        renumber();
        date.focus();
    });

    list.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-slot-remove]');
        if (!remove || list.querySelectorAll('[data-slot-row]').length === 1) return;
        remove.closest('[data-slot-row]').remove();
        renumber();
    });
});
</script>
