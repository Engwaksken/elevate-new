{{-- Certificate template form fields. Expects $courses, $events, $prefix and optional $template (edit) and $useOld. --}}
@php
    $template ??= null;
    $useOld ??= false;
    $value = fn (string $field, $default = null) => $useOld ? old($field, $default) : $default;
    $context = $value('context_type', $template?->context_type ?? 'course');
    $selectedCourses = array_map('intval', $useOld
        ? old('course_ids', [])
        : ($template ? $template->courses->pluck('id')->whenEmpty(fn ($ids) => collect([$template->course_id])->filter())->all() : []));
@endphp
<div class="modal-grid" data-template-form>
    <div class="form-group full"><label for="{{ $prefix }}-name">Template name</label><input id="{{ $prefix }}-name" name="name" value="{{ $value('name', $template?->name) }}" required maxlength="190"></div>

    <div class="form-group">
        <label for="{{ $prefix }}-context">Use for</label>
        <select id="{{ $prefix }}-context" name="context_type" required data-template-context>
            <option value="course" @selected($context==='course')>Selected courses</option>
            <option value="event" @selected($context==='event')>A specific event</option>
            <option value="default" @selected($context==='default')>Default (all others)</option>
        </select>
    </div>

    <div class="form-group">
        <label for="{{ $prefix }}-orientation">Orientation</label>
        @php $orientation = $value('orientation', $template?->orientation ?? 'landscape'); @endphp
        <select id="{{ $prefix }}-orientation" name="orientation">
            <option value="landscape" @selected($orientation==='landscape')>Landscape</option>
            <option value="portrait" @selected($orientation==='portrait')>Portrait</option>
        </select>
    </div>

    <div class="form-group full" data-template-for="course">
        <div class="cert-course-picker-head">
            <label>Courses <span class="form-hint" style="display:inline">(choose one or more)</span></label>
            <input type="search" class="cert-course-filter" placeholder="Filter courses..." data-course-filter aria-label="Filter courses">
        </div>
        <div class="cert-course-picker" data-course-list>
            @forelse($courses as $course)
                <label class="permission-check" for="{{ $prefix }}-course-{{ $course->id }}" data-course-name="{{ \Illuminate\Support\Str::lower($course->title.' '.$course->code) }}">
                    <input id="{{ $prefix }}-course-{{ $course->id }}" type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array($course->id, $selectedCourses, true))>
                    <span>{{ $course->title }}{{ $course->code ? ' · '.$course->code : '' }}</span>
                </label>
            @empty
                <div class="admin-readonly">No courses yet.</div>
            @endforelse
        </div>
        <div class="cert-course-picker-actions">
            <button type="button" class="btn btn-outline btn-sm" data-course-select="all">Select all shown</button>
            <button type="button" class="btn btn-outline btn-sm" data-course-select="none">Clear</button>
            <span class="form-hint" data-course-count></span>
        </div>
    </div>

    <div class="form-group full" data-template-for="event">
        <label for="{{ $prefix }}-event">Event</label>
        @php $eventId = (int) $value('event_id', $template?->event_id); @endphp
        <select id="{{ $prefix }}-event" name="event_id">
            <option value="">Select event</option>
            @foreach($events as $event)
                <option value="{{ $event->id }}" @selected($eventId===$event->id)>{{ $event->title }}{{ $event->starts_at ? ' · '.$event->starts_at->format('d M Y') : '' }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group full">
        <label for="{{ $prefix }}-background">Template image</label>
        <input id="{{ $prefix }}-background" type="file" name="background" accept=".png,.jpg,.jpeg,.webp" @unless($template) required @endunless>
        <small class="form-hint">{{ $template ? 'Leave empty to keep the current image. ' : '' }}Leave the centre clear: the participant's full name, course, cohort, period and certificate number are printed there automatically.</small>
    </div>
</div>
