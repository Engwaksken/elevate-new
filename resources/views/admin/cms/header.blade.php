@extends('layouts.admin')
@section('title', 'Header | Website')
@section('content')
@php($cms = app(\App\Services\CmsContentService::class))
@php($h = old('header', (array) data_get($page->settings, 'header', [])) + ['logo_height' => 48, 'show_name' => true, 'show_tagline' => true, 'sticky' => true, 'show_partners' => true, 'show_auth' => true])
@php($a = old('announcement', (array) data_get($page->settings, 'announcement', [])))
@php($fresh = ! is_array(data_get($page->settings, 'header')))
@php($logo = $cms->mediaUrl($page->image_path))
@php($brandLogo = app(\App\Services\SettingsService::class)->get('branding.logo_path') ? route('branding.asset', ['type' => 'logo']) : null)
<div class="admin-page-header">
    <div>
        <a href="{{ route('admin.cms.index') }}" class="cms-crumb"><i class="fas fa-arrow-left"></i> Website</a>
        <h1>Header &amp; site identity</h1>
        <p>Site name, tagline, header logo, menu, colours and the announcement bar shown on every public page.</p>
    </div>
    <div class="admin-page-actions"><a href="{{ route('home') }}" class="btn btn-outline" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View site</a></div>
</div>

<form method="POST" action="{{ route('admin.cms.update', $page) }}" enctype="multipart/form-data" class="cms-editor" data-chrome-editor="header">@csrf @method('PUT')

<div class="admin-panel cms-chrome-preview" aria-hidden="true">
    <span class="cms-chrome-preview__label">Live preview</span>
    <div class="cms-preview-announce" data-pv-announce @if(empty($a['enabled'])) hidden @endif style="background:{{ $a['background'] ?? '' ?: '#d4af37' }};color:{{ $a['text_color'] ?? '' ?: '#1b1b1b' }}"><span data-pv-announce-text>{{ $a['text'] ?? '' }}</span> <u data-pv-announce-link>{{ $a['link_label'] ?? '' }}</u></div>
    <div class="cms-preview-header" data-pv-header style="background:{{ $h['background'] ?? '' ?: '#ffffff' }};color:{{ $h['text_color'] ?? '' ?: '#800000' }}">
        <div class="cms-preview-brand">
            <img data-pv-logo src="{{ $logo ?: $brandLogo }}" alt="" style="height:{{ (int) $h['logo_height'] }}px" @if(!($logo ?: $brandLogo)) hidden @endif>
            <div><strong data-pv-name @if(empty($h['show_name'])) hidden @endif>{{ $page->title }}</strong><small data-pv-tagline @if(empty($h['show_tagline'])) hidden @endif>{{ $page->summary }}</small></div>
        </div>
        <nav data-pv-links>@foreach(data_get($page->settings, 'links', []) as $l)<span>{{ $l['label'] }}</span>@endforeach</nav>
        <span class="cms-preview-cta" data-pv-cta @if(empty($h['cta_label'])) hidden @endif>{{ $h['cta_label'] ?? '' }}</span>
    </div>
</div>

<div class="cms-two-col">
<div>
    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-id-card"></i> Site identity</h2>
        <div class="form-group"><label>Site name</label><input name="title" value="{{ old('title', $page->title) }}" required maxlength="190" data-pv-input="name"></div>
        <div class="form-group"><label>Tagline</label><input name="summary" value="{{ old('summary', $page->summary) }}" maxlength="190" data-pv-input="tagline" placeholder="e.g. Women in Technology Uganda"></div>
        <div class="cms-grid-2">
            <label class="cms-check"><input type="checkbox" name="header[show_name]" value="1" @checked(!empty($h['show_name'])) data-pv-toggle="name"> Show site name next to logo</label>
            <label class="cms-check"><input type="checkbox" name="header[show_tagline]" value="1" @checked(!empty($h['show_tagline'])) data-pv-toggle="tagline"> Show tagline</label>
        </div>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-image"></i> Header logo</h2>
        <div class="cms-logo-field">
            <div class="cms-logo-field__preview">@if($logo)<img src="{{ $logo }}" alt="Current header logo">@elseif($brandLogo)<img src="{{ $brandLogo }}" alt="Platform logo"><small>Using the platform logo</small>@else<span class="brand-mark">E360</span>@endif</div>
            <div>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp" data-pv-logo-file>
                <small>PNG with a transparent background works best. Leave empty to keep {{ $logo ? 'the current logo' : 'the platform logo from Settings › Branding' }}.</small>
                @if($logo)<label class="cms-check"><input type="checkbox" name="remove_image" value="1"> Remove header logo (fall back to the platform logo)</label>@endif
            </div>
        </div>
        <div class="form-group"><label>Logo height <output data-range-out>{{ (int) $h['logo_height'] }}px</output></label><input type="range" min="24" max="96" step="2" name="header[logo_height]" value="{{ (int) $h['logo_height'] }}" data-range data-range-unit="px" data-pv-input="logo_height"></div>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-bars"></i> Menu links</h2>
        @include('admin.cms.partials.links', ['links' => old('links', data_get($page->settings, 'links', []))])
    </div>
</div>
<div>
    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-palette"></i> Appearance</h2>
        <div class="cms-grid-2">
            <div class="form-group"><label>Background colour</label>@include('cms.fields.color', ['name' => 'header[background]', 'value' => $h['background'] ?? '', 'attr' => 'data-pv-color', 'field' => 'header-bg', 'fallback' => '#ffffff'])</div>
            <div class="form-group"><label>Text &amp; link colour</label>@include('cms.fields.color', ['name' => 'header[text_color]', 'value' => $h['text_color'] ?? '', 'attr' => 'data-pv-color', 'field' => 'header-fg'])</div>
        </div>
        <label class="cms-check"><input type="checkbox" name="header[sticky]" value="1" @checked(!empty($h['sticky']))> Keep header visible while scrolling</label>
        <label class="cms-check"><input type="checkbox" name="header[show_partners]" value="1" @checked(!empty($h['show_partners']))> Show the “Partners” menu (mentor / employer sign in)</label>
        <label class="cms-check"><input type="checkbox" name="header[show_auth]" value="1" @checked(!empty($h['show_auth']))> Show “Sign In” and “Register” buttons</label>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-hand-pointer"></i> Highlight button</h2>
        <p class="cms-hint">An optional gold button in the header, e.g. “Donate” or “Apply now”.</p>
        <div class="cms-grid-2">
            <div class="form-group"><label>Label</label><input name="header[cta_label]" value="{{ $h['cta_label'] ?? '' }}" maxlength="60" data-pv-input="cta"></div>
            <div class="form-group"><label>Link</label><input name="header[cta_url]" value="{{ $h['cta_url'] ?? '' }}" placeholder="/path or https://"></div>
        </div>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-bullhorn"></i> Announcement bar</h2>
        <label class="cms-check"><input type="checkbox" name="announcement[enabled]" value="1" @checked(!empty($a['enabled'])) data-pv-toggle="announce"> Show a bar above the header</label>
        <div class="form-group"><label>Message</label><input name="announcement[text]" value="{{ $a['text'] ?? '' }}" maxlength="300" data-pv-input="announce_text" placeholder="e.g. Applications for the 2027 cohort are open"></div>
        <div class="cms-grid-2">
            <div class="form-group"><label>Link label</label><input name="announcement[link_label]" value="{{ $a['link_label'] ?? '' }}" maxlength="60" data-pv-input="announce_link"></div>
            <div class="form-group"><label>Link</label><input name="announcement[link_url]" value="{{ $a['link_url'] ?? '' }}" placeholder="/path or https://"></div>
            <div class="form-group"><label>Bar colour</label>@include('cms.fields.color', ['name' => 'announcement[background]', 'value' => $a['background'] ?? '', 'attr' => 'data-pv-color', 'field' => 'announce-bg', 'fallback' => '#d4af37'])</div>
            <div class="form-group"><label>Bar text colour</label>@include('cms.fields.color', ['name' => 'announcement[text_color]', 'value' => $a['text_color'] ?? '', 'attr' => 'data-pv-color', 'field' => 'announce-fg', 'fallback' => '#1b1b1b'])</div>
        </div>
    </div>

    <details class="admin-panel cms-advanced">
        <summary><i class="fas fa-sliders"></i> Advanced: text above the menu</summary>
        <div class="form-group"><label>Markdown shown above the navigation bar (optional)</label><textarea name="body" rows="4">{{ old('body', $page->body) }}</textarea></div>
    </details>
</div>
</div>

@if($fresh)<div class="info-box">These settings are new. The header keeps its current look until you publish.</div>@endif
@include('admin.cms.partials.actions', ['previewUrl' => null])
</form>
@endsection
