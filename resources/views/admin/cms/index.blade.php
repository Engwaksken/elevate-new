@extends('layouts.admin')
@section('title', 'Website | Frontend CMS')
@section('content')
@php($cms = app(\App\Services\CmsContentService::class))
@php($bySlug = $pages->keyBy('slug'))
@php($home = $bySlug->get('home'))
@php($header = $bySlug->get('site-header'))
@php($footer = $bySlug->get('site-footer'))
@php($chrome = $cms->siteChrome())
@php($homeSections = collect(data_get($home?->settings, 'sections', [])))
<div class="admin-page-header">
    <div><h1>Website</h1><p>Manage the public website: home page sections, header, footer and content pages. Save drafts, preview, then publish.</p></div>
    <div class="admin-page-actions">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="btn btn-outline"><i class="fas fa-arrow-up-right-from-square"></i> View website</a>
        <button type="button" class="btn btn-primary" data-modal-open="createPageModal"><i class="fas fa-plus"></i> Add page</button>
    </div>
</div>

<div class="cms-dash">
    @if($home)
    <a class="cms-dash__card cms-dash__card--hero" href="{{ route('admin.cms.edit', $home) }}">
        <span class="cms-dash__icon"><i class="fas fa-house"></i></span>
        <span class="cms-dash__body">
            <strong>Home page</strong>
            <span>{{ $homeSections->count() }} {{ \Illuminate\Support\Str::plural('section', $homeSections->count()) }}@if($homeSections->where('hidden', true)->count()) · {{ $homeSections->where('hidden', true)->count() }} hidden @endif · backgrounds, image grids, video</span>
            <span class="status-chip {{ $home->published_at ? 'active' : '' }}">{{ $home->published_at ? 'Published '.$home->published_at->diffForHumans() : 'Built-in page is live' }}</span>
        </span>
        <span class="cms-dash__go">Edit <i class="fas fa-arrow-right"></i></span>
    </a>
    @endif
    @if($header)
    <a class="cms-dash__card" href="{{ route('admin.cms.edit', $header) }}">
        <span class="cms-dash__icon"><i class="fas fa-window-maximize"></i></span>
        <span class="cms-dash__body">
            <strong>Header &amp; site identity</strong>
            <span>{{ $chrome['site_name'] }} · logo, menu, colours, announcement bar</span>
            <span class="status-chip {{ $header->published_at ? 'active' : '' }}">{{ $header->published_at ? 'Published' : 'Default header' }}</span>
        </span>
        <span class="cms-dash__go">Edit <i class="fas fa-arrow-right"></i></span>
    </a>
    @endif
    @if($footer)
    <a class="cms-dash__card" href="{{ route('admin.cms.edit', $footer) }}">
        <span class="cms-dash__icon"><i class="fas fa-window-minimize"></i></span>
        <span class="cms-dash__body">
            <strong>Footer</strong>
            <span>Logo, about, contact, social links, link columns</span>
            <span class="status-chip {{ $footer->published_at ? 'active' : '' }}">{{ $footer->published_at ? 'Published' : 'Default footer' }}</span>
        </span>
        <span class="cms-dash__go">Edit <i class="fas fa-arrow-right"></i></span>
    </a>
    @endif
    @if(Route::has('admin.platform-settings.index'))
    <a class="cms-dash__card" href="{{ route('admin.platform-settings.index') }}#branding">
        <span class="cms-dash__icon"><i class="fas fa-swatchbook"></i></span>
        <span class="cms-dash__body">
            <strong>Brand colours &amp; favicon</strong>
            <span>Primary colours, fonts, platform logo and browser icon</span>
            <span class="status-chip">Platform settings</span>
        </span>
        <span class="cms-dash__go">Open <i class="fas fa-arrow-right"></i></span>
    </a>
    @endif
</div>

<div class="admin-panel">
    <h2 class="cms-panel-title"><i class="fas fa-file-lines"></i> Pages</h2>
    <table class="admin-table"><thead><tr><th>Page</th><th>Status</th><th>Last updated</th><th>Actions</th></tr></thead><tbody>
    @foreach($pages->reject(fn ($p) => in_array($p->slug, ['home', 'site-header', 'site-footer'], true)) as $page)
        @php($url = $cms->publicUrl($page))
        <tr>
            <td><strong>{{ $page->title }}</strong><small>{{ $url ? parse_url($url, PHP_URL_PATH) : $page->slug }}@unless(array_key_exists($page->slug, \App\Services\CmsContentService::PAGES)) · custom page @endunless</small></td>
            <td>@if($page->published_at)<span class="status-chip active">Published</span>@else<span class="status-chip">{{ array_key_exists($page->slug, \App\Services\CmsContentService::PAGES) ? 'Built-in content' : 'Draft' }}</span>@endif</td>
            <td>{{ $page->updated_at?->format('d M Y H:i') }}</td>
            <td><a class="btn btn-outline btn-sm" href="{{ route('admin.cms.edit', $page) }}"><i class="fas fa-pen"></i> Edit</a> <a href="{{ route('admin.cms.preview', $page) }}" target="_blank" rel="noopener">Preview</a>@if($url) · <a href="{{ $url }}" target="_blank" rel="noopener">View</a>@endif</td>
        </tr>
    @endforeach
    </tbody></table>
</div>

<div class="admin-panel"><h2 class="cms-panel-title"><i class="fas fa-layer-group"></i> Catalogue content</h2><p class="cms-hint">Courses, jobs, events and other listings shown on the website are managed in their own modules.</p><div class="action-group">
@foreach(['admin.elearning.courses.index'=>'Courses','admin.jobs.index'=>'Jobs','admin.library.index'=>'Library','admin.events.index'=>'Events','admin.mentorship.mentors.index'=>'Mentors','admin.jobs.employers.index'=>'Employers'] as $route=>$label)@if(Route::has($route))<a class="btn btn-outline btn-sm" href="{{ route($route) }}">Manage {{ $label }}</a>@endif @endforeach
</div></div>

<div class="eh-modal" id="createPageModal" aria-hidden="true" @if($errors->any()) data-modal-autoopen @endif><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Add page</h2><p>Creates a draft page you can build with sections and publish.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.cms.store') }}">@csrf
<input type="hidden" name="_cms_page" value="1">
<div class="eh-modal-body">
<div class="modal-grid">
<div class="form-group"><label>Title</label><input name="title" value="{{ old('title') }}" required maxlength="190"></div>
<div class="form-group"><label>Slug (URL: /pages/your-slug)</label><input name="slug" value="{{ old('slug') }}" required pattern="[a-z][a-z0-9-]*" maxlength="100"></div>
</div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create draft</button></div>
</form></div></div>
@endsection
