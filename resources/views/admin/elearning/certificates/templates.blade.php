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

<div class="admin-panel">
<div class="cert-template-grid">
@forelse($templates as $template)
<article class="cert-template-card {{ $template->is_active ? '' : 'is-inactive' }}">
    <a class="cert-template-thumb {{ $template->orientation }}" href="{{ route('admin.elearning.certificates.templates.preview',$template) }}" target="_blank" rel="noopener">
        <img src="{{ route('admin.elearning.certificates.templates.preview',$template) }}" alt="{{ $template->name }} preview" loading="lazy">
    </a>
    <div class="cert-template-body">
        <strong>{{ $template->name }}</strong>
        <span class="cert-context">
            <i class="fas {{ ['event' => 'fa-calendar-days', 'course' => 'fa-graduation-cap'][$template->context_type] ?? 'fa-star' }}"></i>
            {{ $template->context_type === 'default' ? 'Default template' : ucfirst($template->context_type) }}
        </span>
        @foreach($template->courses as $course)<span>{{ $course->title }}</span>@endforeach
        @if($template->courses->isEmpty() && $template->course)<span>{{ $template->course->title }}</span>@endif
        @if($template->event)<span>{{ $template->event->title }}</span>@endif
        <span class="cert-template-meta">{{ ucfirst($template->orientation) }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</span>
    </div>
    <div class="cert-template-actions">
        <a href="{{ route('admin.elearning.certificates.templates.design',$template) }}" class="btn btn-primary btn-sm"><i class="fas fa-pen-ruler"></i> Design fields</a>
        <form method="POST" action="{{ route('admin.elearning.certificates.templates.toggle',$template) }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline btn-sm"><i class="fas {{ $template->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i> {{ $template->is_active ? 'Deactivate' : 'Activate' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.elearning.certificates.templates.destroy',$template) }}" data-delete-form data-confirm="Delete the template “{{ $template->name }}”? Certificates already generated keep their PDF.">
            @csrf
            @method('DELETE')
            <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Delete</button>
        </form>
    </div>
</article>
@empty
<div class="admin-empty"><i class="fas fa-image"></i><strong>No certificate templates uploaded</strong><span>Certificates use the built-in design until you upload one.</span></div>
@endforelse
</div>

{{ $templates->links() }}
</div>

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
