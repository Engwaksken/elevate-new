@extends('layouts.admin')
@section('title', 'Edit '.$page->title.' | Website')
@section('content')
@php($cms = app(\App\Services\CmsContentService::class))
@php($isHome = $page->slug === 'home')
@php($liveUrl = $cms->publicUrl($page))
@php($isLegal = array_key_exists($page->slug, \App\Services\CmsContentService::LEGAL))
<div class="admin-page-header">
    <div>
        <a href="{{ route('admin.cms.index') }}" class="cms-crumb"><i class="fas fa-arrow-left"></i> Website</a>
        <h1>{{ $isHome ? 'Home page' : $page->title }}</h1>
        <p>{{ $isHome ? 'Build the public home page from sections. Each section can have its own colour, gradient, image or video background.' : ($isLegal ? 'Edit the document text, preview it in the professional legal layout, then publish. The contents list is built from the ## headings.' : 'Edit this page’s sections and text, preview the draft, then publish.') }}</p>
    </div>
    <div class="admin-page-actions">@if($liveUrl)<a href="{{ $liveUrl }}" class="btn btn-outline" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View live page</a>@endif</div>
</div>

<form method="POST" action="{{ route('admin.cms.update', $page) }}" enctype="multipart/form-data" class="cms-editor">@csrf @method('PUT')
<div class="admin-panel">
    <h2 class="cms-panel-title"><i class="fas fa-file-lines"></i> Page details</h2>
    <div class="cms-grid-2">
        <div class="form-group"><label>{{ $isHome ? 'Browser tab title' : 'Page title' }}</label><input name="title" value="{{ old('title', $page->title) }}" required maxlength="190"></div>
        <div class="form-group"><label>Summary / meta description <small>(shown in search results)</small></label><textarea name="summary" rows="2">{{ old('summary', $page->summary) }}</textarea></div>
    </div>
</div>

@if($isLegal)
    @php($legalBody = old('body', $page->body))
    @php($legalPrefilled = blank($legalBody))
    @if($legalPrefilled)@php($legalBody = $cms->legalEditorText($page->slug))@endif
    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-scale-balanced"></i> Document text</h2>
        @if($legalPrefilled)
            <div class="warning-box" style="margin-bottom:12px"><strong>Draft text loaded.</strong> This is the built-in {{ $page->title }} drafted from how the platform works. Replace every <code>[... to be confirmed]</code> placeholder with your organisation's details and have it checked by a legal adviser before publishing.</div>
        @endif
        <div class="form-group">
            <label>Content - Markdown or HTML</label>
            <textarea name="body" rows="28" class="cms-legal-textarea">{{ $legalBody }}</textarea>
            <small>Each <code>## Heading</code> (or <code>&lt;h2&gt;</code>) becomes a numbered section in the contents list. Lists, links, tables and bold text are supported; scripts and unsafe links are removed.</small>
        </div>
    </div>
@else
<div class="admin-panel">
    @php($sections = old('sections', data_get($page->settings, 'sections', [])))
    {{-- A home page without sections starts from the built-in page, so publishing keeps the existing content. --}}
    @php($loadedOriginal = $isHome && empty($sections) && ! old('sections'))
    @if($loadedOriginal)
        @php($sections = \App\Services\CmsContentService::defaultHomeSections())
        <div class="info-box" style="margin-bottom:12px">The original home page content and layout have been loaded below. Edit anything, then save or publish.</div>
    @endif
    @include('cms.builder', ['sections' => $sections, 'starter' => $isHome])
</div>

<details class="admin-panel cms-advanced" @if(!$isHome && blank(data_get($page->settings, 'sections'))) open @endif>
    <summary><i class="fas fa-sliders"></i> {{ $isHome ? 'Extra content, image and links' : 'Text content, image and links' }}</summary>
    <div class="form-group"><label>Page content (Markdown){{ $isHome ? ' - shown after the sections' : '' }}</label><textarea name="body" rows="12">{{ old('body', $page->body) }}</textarea><small>Headings (#), lists, links and emphasis. Unsafe HTML and links are removed.</small></div>
    <div class="form-group"><label>Page image (used when the page has no sections)</label>
        @if($img = $cms->mediaUrl($page->image_path))<div class="cms-current-image"><img src="{{ $img }}" alt=""><label><input type="checkbox" name="remove_image" value="1"> Remove image</label></div>@endif
        <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp"><small>PNG, JPG or WEBP, up to 5 MB.</small>
    </div>
    <div class="form-group"><label>Page action buttons</label>
        @include('admin.cms.partials.links', ['links' => old('links', data_get($page->settings, 'links', []))])
    </div>
    @if(in_array($page->slug, ['mentor-signup','employer-signup'], true))
        <div class="cms-grid-2">
            <div class="form-group"><label>Submit button text</label><input name="button_label" value="{{ old('button_label', data_get($page->settings, 'button_label', 'Submit registration')) }}"></div>
            <div class="form-group"><label>&nbsp;</label><label class="cms-check"><input type="checkbox" name="signup_open" value="1" @checked(old('signup_open', data_get($page->settings, 'signup_open', true)))> Accept new registrations</label></div>
        </div>
        <div class="form-group"><label>Form field labels - one per line: field_name | Label</label><textarea name="field_labels_text" rows="6">{{ old('field_labels_text', collect(data_get($page->settings, 'field_labels', []))->map(fn($label, $key) => $key.' | '.$label)->join("\n")) }}</textarea><small>Fields: name, email, phone, country, organisation, job_title, industry, years_experience, linkedin_url, skills, languages, mentoring_areas, professional_bio, company_name, company_type, website, contact_person, location, description.</small></div>
    @endif
</details>

@endif

@include('admin.cms.partials.actions', ['previewUrl' => route('admin.cms.preview', $page)])
</form>

@unless(array_key_exists($page->slug, \App\Services\CmsContentService::PAGES))
<form method="POST" action="{{ route('admin.cms.destroy', $page) }}" onsubmit="return confirm('Delete this page?')" style="margin-top:16px">@csrf @method('DELETE')<button class="btn btn-danger"><i class="fas fa-trash"></i> Delete page</button></form>
@endunless
@endsection
