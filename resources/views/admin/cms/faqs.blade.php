@extends('layouts.admin')
@section('title', 'FAQs | Website')
@section('content')
@php
    $saved = data_get($page->settings, 'faqs');
    $faqs = old('faqs', is_array($saved) && $saved !== [] ? $saved : (blank($page->body) ? \App\Services\CmsContentService::defaultFaqs() : []));
    $loadedDefaults = ! old('faqs') && (! is_array($saved) || $saved === []) && blank($page->body);
    $categories = collect($faqs)->pluck('category')->merge(collect(\App\Services\CmsContentService::defaultFaqs())->pluck('category'))->filter()->unique()->values();
@endphp
<div class="admin-page-header">
    <div>
        <a href="{{ route('admin.cms.index') }}" class="cms-crumb"><i class="fas fa-arrow-left"></i> Website</a>
        <h1>Frequently asked questions</h1>
        <p>Edit the questions and answers, group them by topic, then publish. Answers support Markdown: **bold**, lists and [links](/learning).</p>
    </div>
    <div class="admin-page-actions"><a href="{{ route('public.faqs') }}" class="btn btn-outline" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View live page</a></div>
</div>
@if($loadedDefaults)<div class="info-box">{{ count($faqs) }} suggested questions have been loaded. Review the answers so they match how your organisation works, then publish.</div>@endif

<form method="POST" action="{{ route('admin.cms.update', $page) }}" class="cms-editor">@csrf @method('PUT')
<input type="hidden" name="faqs_submitted" value="1">
<div class="admin-panel">
    <h2 class="cms-panel-title"><i class="fas fa-file-lines"></i> Page details</h2>
    <div class="cms-grid-2">
        <div class="form-group"><label>Page title</label><input name="title" value="{{ old('title', $page->title) }}" required maxlength="190"></div>
        <div class="form-group"><label>Subtitle</label><input name="summary" value="{{ old('summary', $page->summary) }}" maxlength="300" placeholder="Quick answers about accounts, learning, mentorship, jobs and more."></div>
    </div>
</div>

<div class="admin-panel">
    <div class="faq-editor__bar">
        <h2 class="cms-panel-title" style="margin:0"><i class="fas fa-circle-question"></i> Questions <span class="status-chip" data-faq-count>{{ count($faqs) }}</span></h2>
        <div class="action-group">
            <button type="button" class="btn btn-outline btn-sm" data-faq-toggle-all><i class="fas fa-up-right-and-down-left-from-center"></i> Expand / collapse all</button>
            <button type="button" class="btn btn-outline btn-sm" data-faq-load-defaults><i class="fas fa-rotate-left"></i> Load suggested FAQs</button>
        </div>
    </div>
    <datalist id="faq-categories">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
    <div class="faq-editor__rows" data-faq-rows>
        @foreach($faqs as $i => $faq)
            @include('admin.cms.partials.faq-row', ['i' => $i, 'faq' => $faq])
        @endforeach
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-faq-add><i class="fas fa-plus"></i> Add question</button>
    <template data-faq-template>@include('admin.cms.partials.faq-row', ['i' => '__I__', 'faq' => [], 'open' => true])</template>
    <template data-faq-defaults>
        @foreach(\App\Services\CmsContentService::defaultFaqs() as $j => $faq)
            @include('admin.cms.partials.faq-row', ['i' => $j, 'faq' => $faq])
        @endforeach
    </template>
</div>

<details class="admin-panel cms-advanced" @if(filled($page->body)) open @endif>
    <summary><i class="fas fa-sliders"></i> Optional introduction text</summary>
    <div class="form-group"><label>Shown above the questions (Markdown)</label><textarea name="body" rows="5">{{ old('body', $page->body) }}</textarea></div>
</details>

@include('admin.cms.partials.actions', ['previewUrl' => route('admin.cms.preview', $page)])
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelector('[data-faq-rows]');
    const count = document.querySelector('[data-faq-count]');
    if (!rows) return;
    const reindex = () => {
        rows.querySelectorAll('[data-faq-row]').forEach((row, i) => {
            row.querySelectorAll('[data-faq-field]').forEach((el) => { el.name = `faqs[${i}][${el.dataset.faqField}]`; });
            const num = row.querySelector('[data-faq-num]');
            if (num) num.textContent = i + 1;
        });
        count.textContent = rows.querySelectorAll('[data-faq-row]').length;
    };
    const syncTitle = (row) => {
        const q = row.querySelector('[data-faq-field="question"]').value.trim();
        row.querySelector('[data-faq-title]').textContent = q || 'New question';
    };
    document.querySelector('[data-faq-add]').addEventListener('click', () => {
        const row = document.querySelector('[data-faq-template]').content.firstElementChild.cloneNode(true);
        rows.appendChild(row);
        reindex();
        row.querySelector('[data-faq-field="question"]').focus();
    });
    document.querySelector('[data-faq-load-defaults]').addEventListener('click', () => {
        if (rows.children.length && !confirm('Replace the current questions with the suggested FAQs? Nothing changes on the live site until you save or publish.')) return;
        rows.innerHTML = '';
        rows.appendChild(document.querySelector('[data-faq-defaults]').content.cloneNode(true));
        reindex();
    });
    document.querySelector('[data-faq-toggle-all]').addEventListener('click', () => {
        const all = [...rows.querySelectorAll('[data-faq-row]')];
        const open = all.some((r) => !r.open);
        all.forEach((r) => { r.open = open; });
    });
    rows.addEventListener('click', (e) => {
        const row = e.target.closest('[data-faq-row]');
        if (!row) return;
        if (e.target.closest('[data-faq-remove]')) {
            e.preventDefault();
            if (confirm('Remove this question?')) { row.remove(); reindex(); }
        } else if (e.target.closest('[data-faq-move]')) {
            e.preventDefault();
            const dir = e.target.closest('[data-faq-move]').dataset.faqMove;
            if (dir === 'up' && row.previousElementSibling) rows.insertBefore(row, row.previousElementSibling);
            if (dir === 'down' && row.nextElementSibling) rows.insertBefore(row.nextElementSibling, row);
            reindex();
        }
    });
    rows.addEventListener('input', (e) => {
        const row = e.target.closest('[data-faq-row]');
        if (row && e.target.matches('[data-faq-field="question"]')) syncTitle(row);
        if (row && e.target.matches('[data-faq-field="category"]')) row.querySelector('[data-faq-cat]').textContent = e.target.value.trim() || 'General';
    });
    reindex();
});
</script>
@endpush
