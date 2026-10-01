@extends('layouts.admin')
@section('title','Certificate Templates | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Certificates</span>
        <h1>Certificate Templates</h1>
        <p>Upload the background design for a specific course, a specific event, or the default certificate. Participant details are printed on top.</p>
    </div>
    <div class="admin-page-actions">
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
        @if($template->course)<span>{{ $template->course->title }}</span>@endif
        @if($template->event)<span>{{ $template->event->title }}</span>@endif
        <span class="cert-template-meta">{{ ucfirst($template->orientation) }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</span>
    </div>
    <div class="cert-template-actions">
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
                <option value="course" @selected(old('context_type','course')==='course')>A specific course</option>
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
            <label for="tpl-course">Course</label>
            <select id="tpl-course" name="course_id">
                <option value="">Select course</option>
                @foreach($courses as $course)
                    <option value="{{ $course->id }}" @selected((int) old('course_id')===$course->id)>{{ $course->title }}</option>
                @endforeach
            </select>
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
    });
    context.addEventListener('change', sync);
    sync();
});
</script>
@endsection
