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
        @php $templateCourses = $template->courses->isNotEmpty() ? $template->courses : collect([$template->course])->filter(); @endphp
        @if($template->context_type === 'course' && $templateCourses->isNotEmpty())
            <span class="cert-template-courses" title="{{ $templateCourses->pluck('title')->join(', ') }}">
                {{ $templateCourses->take(3)->pluck('title')->join(', ') }}@if($templateCourses->count() > 3) <em>+{{ $templateCourses->count() - 3 }} more</em>@endif
            </span>
        @endif
        @if($template->event)<span>{{ $template->event->title }}</span>@endif
        <span class="cert-template-meta">{{ ucfirst($template->orientation) }} · {{ $template->is_active ? 'Active' : 'Inactive' }}</span>
    </div>
    <div class="cert-template-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="cert-template-edit-{{ $template->id }}"><i class="fas fa-pen"></i> Edit</button>
        <form method="POST" action="{{ route('admin.elearning.certificates.templates.toggle',$template) }}">
            @csrf @method('PATCH')
            <button class="btn btn-outline btn-sm"><i class="fas {{ $template->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i> {{ $template->is_active ? 'Deactivate' : 'Activate' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.elearning.certificates.templates.destroy',$template) }}" data-delete-form data-confirm="Delete the template “{{ $template->name }}”? Its courses will fall back to the default design.">
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

<div class="eh-modal" id="cert-template-upload" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cert-template-upload-title" @if($errors->any() && ! old('template_id')) data-modal-autoopen @endif>
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
    @include('admin.elearning.certificates._template-fields', ['prefix' => 'tpl-new', 'useOld' => ! old('template_id')])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary"><i class="fas fa-upload"></i> Upload template</button>
</div>
</form>
</div>
</div>

@foreach($templates as $template)
@php $reopen = $errors->any() && (int) old('template_id') === $template->id; @endphp
<div class="eh-modal" id="cert-template-edit-{{ $template->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="cert-template-edit-{{ $template->id }}-title" @if($reopen) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('admin.elearning.certificates.templates.update',$template) }}" enctype="multipart/form-data">
@csrf @method('PUT')
<input type="hidden" name="template_id" value="{{ $template->id }}">
<div class="eh-modal-header">
    <div><h2 id="cert-template-edit-{{ $template->id }}-title">Edit template</h2><p>{{ $template->name }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body">
    @if($reopen)
        <div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>
    @endif
    @include('admin.elearning.certificates._template-fields', ['prefix' => 'tpl-'.$template->id, 'template' => $template, 'useOld' => $reopen])
</div>
<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save changes</button>
</div>
</form>
</div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-template-form]').forEach(form => {
        const context = form.querySelector('[data-template-context]');
        const syncContext = () => form.querySelectorAll('[data-template-for]').forEach(field => {
            field.hidden = field.dataset.templateFor !== context.value;
        });
        context?.addEventListener('change', syncContext);
        syncContext();

        const items = [...form.querySelectorAll('[data-course-name]')];
        const count = form.querySelector('[data-course-count]');
        const syncCount = () => {
            if (count) count.textContent = items.filter(item => item.querySelector('input').checked).length + ' selected';
        };
        form.querySelector('[data-course-filter]')?.addEventListener('input', e => {
            const term = e.target.value.trim().toLowerCase();
            items.forEach(item => { item.hidden = term !== '' && !item.dataset.courseName.includes(term); });
        });
        form.querySelectorAll('[data-course-select]').forEach(button => button.addEventListener('click', () => {
            items.filter(item => !item.hidden).forEach(item => { item.querySelector('input').checked = button.dataset.courseSelect === 'all'; });
            syncCount();
        }));
        items.forEach(item => item.querySelector('input').addEventListener('change', syncCount));
        syncCount();
    });
});
</script>
@endsection
