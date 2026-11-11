@extends('layouts.admin')
@section('title','Certificate Templates | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Certificates</span>
        <h1>Certificate Templates</h1>
        <p>Upload one background design for multiple courses, a specific event, or the default certificate. Participant details are printed on top.</p>
    </div>
    <div class="admin-page-actions">
        <x-export-buttons />
        <a href="{{ route('certificates.recommendations.index') }}" class="btn btn-outline"><i class="fas fa-list-check"></i> Recommendations</a>
        <button type="button" class="btn btn-primary" data-modal-open="cert-template-upload"><i class="fas fa-upload"></i> Upload template</button>
    </div>
</div>

<div class="admin-stats-grid compact">
@foreach([
    ['Templates', 'fa-image', number_format($stats['total']), $stats['inactive'].' inactive', []],
    ['Active templates', 'fa-circle-check', number_format($stats['active']), $stats['has_default'] ? 'Default template set' : 'No default template', ['status' => 'active']],
    ['Courses covered', 'fa-graduation-cap', number_format($stats['courses_covered']).' / '.number_format($stats['courses_total']), $stats['events'].' event '.\Illuminate\Support\Str::plural('template', $stats['events']), ['context' => 'course']],
    ['Certificates generated', 'fa-file-pdf', number_format($stats['certificates']), 'Using an uploaded template', null],
] as [$label, $icon, $value, $hint, $filter])
    @if($filter !== null)<a class="admin-stat" href="{{ route('admin.elearning.certificates.templates.index', $filter) }}">@else<div class="admin-stat">@endif
        <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
        <div><small>{{ $label }}</small><strong>{{ $value }}</strong><small class="admin-cell-hint">{{ $hint }}</small></div>
    @if($filter !== null)</a>@else</div>@endif
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search template, course or event..." aria-label="Search templates"></div>
    <select name="context" aria-label="Used for">
        <option value="">All uses</option>
        <option value="course" @selected(request('context')==='course')>Courses</option>
        <option value="event" @selected(request('context')==='event')>Events</option>
        <option value="default" @selected(request('context')==='default')>Default</option>
    </select>
    <select name="status" aria-label="Status">
        <option value="">All statuses</option>
        <option value="active" @selected(request('status')==='active')>Active</option>
        <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
    </select>
    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.elearning.certificates.templates.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table cert-template-table">
<thead>
<tr>
    <th>Preview</th>
    <th>Template</th>
    <th>Used for</th>
    <th>Orientation</th>
    <th>Certificates</th>
    <th>Status</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($templates as $template)
@php($templateCourses = $template->courses->isNotEmpty() ? $template->courses : collect([$template->course])->filter())
<tr class="{{ $template->is_active ? '' : 'is-inactive' }}">
    <td>
        <a class="cert-template-mini {{ $template->orientation }}" href="{{ route('admin.elearning.certificates.templates.preview',$template) }}" target="_blank" rel="noopener" title="Open full-size image">
            <img src="{{ route('admin.elearning.certificates.templates.preview',$template) }}" alt="{{ $template->name }} preview" loading="lazy">
        </a>
    </td>
    <td>
        <strong>{{ $template->name }}</strong>
        <small class="admin-cell-hint">Added {{ $template->created_at?->format('d M Y') }}{{ $template->creator ? ' by '.$template->creator->name : '' }}</small>
    </td>
    <td>
        <span class="cert-context">
            <i class="fas {{ ['event' => 'fa-calendar-days', 'course' => 'fa-graduation-cap'][$template->context_type] ?? 'fa-star' }}"></i>
            {{ $template->context_type === 'default' ? 'Default (all others)' : ($template->context_type === 'event' ? 'Event' : $templateCourses->count().' '.\Illuminate\Support\Str::plural('course', $templateCourses->count())) }}
        </span>
        @if($template->event)
            <small class="admin-cell-hint">{{ $template->event->title }}</small>
        @elseif($templateCourses->isNotEmpty())
            <small class="admin-cell-hint" title="{{ $templateCourses->pluck('title')->join(', ') }}">{{ $templateCourses->take(3)->pluck('title')->join(', ') }}{{ $templateCourses->count() > 3 ? ' +'.($templateCourses->count() - 3).' more' : '' }}</small>
        @endif
    </td>
    <td>{{ ucfirst($template->orientation) }}</td>
    <td>{{ number_format($template->certificates_count) }}</td>
    <td><span class="status-chip {{ $template->is_active ? 'active' : 'inactive' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span></td>
    <td class="table-actions">
        <div class="cert-template-actions-row">
            <a href="{{ route('admin.elearning.certificates.templates.design',$template) }}" class="btn btn-primary btn-sm"><i class="fas fa-pen-ruler"></i> Design fields</a>
            <form method="POST" action="{{ route('admin.elearning.certificates.templates.toggle',$template) }}">
                @csrf @method('PATCH')
                <button class="btn btn-outline btn-sm"><i class="fas {{ $template->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i> {{ $template->is_active ? 'Deactivate' : 'Activate' }}</button>
            </form>
            <form method="POST" action="{{ route('admin.elearning.certificates.templates.destroy',$template) }}" data-delete-form data-confirm="Delete the template “{{ $template->name }}”? Certificates already generated keep their PDF.">
                @csrf
                @method('DELETE')
                <button class="btn-icon danger" title="Delete template" aria-label="Delete {{ $template->name }}"><i class="fas fa-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty"><i class="fas fa-image"></i><strong>{{ request()->hasAny(['search','context','status']) ? 'No templates match these filters' : 'No certificate templates uploaded' }}</strong><span>{{ request()->hasAny(['search','context','status']) ? 'Try changing or clearing the filters.' : 'Certificates use the built-in design until you upload one.' }}</span></div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $templates->links() }}</div>
</div>

<style>
.cert-template-table td{vertical-align:middle}
.cert-template-table tr.is-inactive td{opacity:.65}
.cert-template-table tr.is-inactive td.table-actions{opacity:1}
.cert-template-mini{display:block;width:96px;aspect-ratio:297/210;border:1px solid #e4e7ec;border-radius:6px;overflow:hidden;background:#f2f4f7}
.cert-template-mini.portrait{width:56px;aspect-ratio:210/297}
.cert-template-mini img{width:100%;height:100%;object-fit:cover;display:block;color:transparent;font-size:0}
.cert-template-actions-row{display:flex;align-items:center;justify-content:flex-end;gap:6px;flex-wrap:nowrap;white-space:nowrap}
.cert-template-actions-row form{margin:0}
</style>

<div class="eh-modal" id="cert-template-upload" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cert-template-upload-title" @if($errors->any()) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('admin.elearning.certificates.templates.store') }}" enctype="multipart/form-data">
@csrf
<div class="eh-modal-header">
    <div><h2 id="cert-template-upload-title">Upload certificate template</h2><p>PNG, JPG or WEBP up to 10 MB. Use A4 proportions (landscape 297×210 or portrait 210×297).</p></div>
    <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body">
    @if($errors->any())
        <div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>
    @endif
    <div class="modal-grid">
        <div class="form-group full"><label for="tpl-name">Template name</label><input id="tpl-name" name="name" value="{{ old('name') }}" required maxlength="190"></div>

        <div class="form-group">
            <label for="tpl-context">Use for</label>
            <select id="tpl-context" name="context_type" required data-template-context>
                <option value="course" @selected(old('context_type','course')==='course')>Selected courses</option>
                <option value="event" @selected(old('context_type')==='event')>A specific event</option>
                <option value="default" @selected(old('context_type')==='default')>Default (all others)</option>
            </select>
        </div>

        <div class="form-group">
            <label for="tpl-orientation">Orientation</label>
            <select id="tpl-orientation" name="orientation">
                <option value="landscape" @selected(old('orientation','landscape')==='landscape')>Landscape</option>
                <option value="portrait" @selected(old('orientation')==='portrait')>Portrait</option>
            </select>
        </div>

        <div class="form-group full" data-template-for="course">
            <label>Courses (select one or more)</label>
            <div style="max-height:240px;overflow:auto;border:1px solid #ddd;padding:12px">
            @foreach($courses as $course)
                <label style="display:flex;gap:10px;align-items:center;padding:6px 0" for="tpl-course-{{ $course->id }}">
                    <input style="width:auto" id="tpl-course-{{ $course->id }}" type="checkbox" name="course_ids[]" value="{{ $course->id }}" @checked(in_array($course->id, old('course_ids', [])))>
                    {{ $course->title }}
                </label>
            @endforeach
            </div>
            <small>One uploaded template will be used for every selected course.</small>
        </div>

        <div class="form-group full" data-template-for="event">
            <label for="tpl-event">Event</label>
            <select id="tpl-event" name="event_id">
                <option value="">Select event</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected((int) old('event_id')===$event->id)>{{ $event->title }}{{ $event->starts_at ? ' · '.$event->starts_at->format('d M Y') : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group full">
            <label for="tpl-background">Template image</label>
            <input id="tpl-background" type="file" name="background" accept=".png,.jpg,.jpeg,.webp" required>
            <small class="form-hint">Leave the centre clear: the participant's name, the course or event title, and the certificate number are printed there.</small>
        </div>
    </div>
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary"><i class="fas fa-upload"></i> Upload template</button>
</div>
</form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const context = document.querySelector('[data-template-context]');
    if (!context) return;
    const sync = () => document.querySelectorAll('[data-template-for]').forEach(field => {
        field.hidden = field.dataset.templateFor !== context.value;
        field.querySelectorAll('input,select').forEach(input => input.disabled = field.hidden);
    });
    context.addEventListener('change', sync);
    sync();
});
</script>
@endsection
