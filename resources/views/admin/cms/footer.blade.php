@extends('layouts.admin')
@section('title', 'Footer | Website')
@section('content')
@php($cms = app(\App\Services\CmsContentService::class))
@php($f = old('footer', (array) data_get($page->settings, 'footer', [])) + ['show_logo' => true, 'logo_height' => 44])
@php($titles = array_replace(['Explore', 'Resources', 'Partners'], array_filter((array) ($f['column_titles'] ?? []))))
@php($logo = $cms->mediaUrl($page->image_path))
@php($brandLogo = app(\App\Services\SettingsService::class)->get('branding.logo_path') ? route('branding.asset', ['type' => 'logo']) : null)
@php($links = old('links', data_get($page->settings, 'links', [])))
{{-- Links saved before columns existed were split evenly across three columns; keep that order. --}}
@if(collect($links)->doesntContain(fn ($l) => isset($l['column'])) && count($links))
    @php($per = (int) ceil(count($links) / 3))
    @php($links = collect($links)->values()->map(fn ($l, $i) => $l + ['column' => intdiv($i, max(1, $per)) + 1])->all())
@endif
<div class="admin-page-header">
    <div>
        <a href="{{ route('admin.cms.index') }}" class="cms-crumb"><i class="fas fa-arrow-left"></i> Website</a>
        <h1>Footer</h1>
        <p>Footer logo, about text, contact details, social links and link columns shown on every public page.</p>
    </div>
    <div class="admin-page-actions"><a href="{{ route('home') }}#main-content" class="btn btn-outline" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View site</a></div>
</div>

<form method="POST" action="{{ route('admin.cms.update', $page) }}" enctype="multipart/form-data" class="cms-editor">@csrf @method('PUT')
<div class="cms-two-col">
<div>
    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-id-card"></i> Branding</h2>
        <div class="form-group"><label>Name shown in the footer</label><input name="title" value="{{ old('title', $page->title) }}" required maxlength="190"></div>
        <div class="form-group"><label>Copyright line <small>(after © {{ date('Y') }})</small></label><input name="summary" value="{{ old('summary', $page->summary) }}" maxlength="190" placeholder="Women in Technology Uganda"></div>
        <div class="cms-logo-field">
            <div class="cms-logo-field__preview cms-logo-field__preview--dark" style="background:{{ $f['background'] ?? '' ?: 'var(--primary, #800000)' }}">@if($logo)<img src="{{ $logo }}" alt="Current footer logo">@elseif($brandLogo)<img src="{{ $brandLogo }}" alt="Platform logo"><small>Using the platform logo</small>@else<small>No logo</small>@endif</div>
            <div>
                <label>Footer logo</label>
                <input type="file" name="image" accept=".png,.jpg,.jpeg,.webp">
                <small>A light / white version of the logo usually reads best on the dark footer.</small>
                @if($logo)<label class="cms-check"><input type="checkbox" name="remove_image" value="1"> Remove footer logo (fall back to the platform logo)</label>@endif
                <label class="cms-check"><input type="checkbox" name="footer[show_logo]" value="1" @checked(!empty($f['show_logo']))> Show a logo in the footer</label>
            </div>
        </div>
        <div class="form-group"><label>Logo height <output data-range-out>{{ (int) $f['logo_height'] }}px</output></label><input type="range" min="24" max="120" step="2" name="footer[logo_height]" value="{{ (int) $f['logo_height'] }}" data-range data-range-unit="px"></div>
        <div class="form-group"><label>About text (Markdown, optional)</label><textarea name="body" rows="4">{{ old('body', $page->body) }}</textarea></div>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-address-book"></i> Contact details</h2>
        <div class="cms-grid-2">
            <div class="form-group"><label>Email</label><input type="email" name="footer[email]" value="{{ $f['email'] ?? '' }}" maxlength="190"></div>
            <div class="form-group"><label>Phone</label><input name="footer[phone]" value="{{ $f['phone'] ?? '' }}" maxlength="60"></div>
        </div>
        <div class="form-group"><label>Address</label><input name="footer[address]" value="{{ $f['address'] ?? '' }}" maxlength="300"></div>
    </div>
</div>
<div>
    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-palette"></i> Colours</h2>
        <div class="cms-grid-2">
            <div class="form-group"><label>Background colour</label>@include('cms.fields.color', ['name' => 'footer[background]', 'value' => $f['background'] ?? '', 'attr' => 'data-footer-color', 'field' => 'bg'])</div>
            <div class="form-group"><label>Text colour</label>@include('cms.fields.color', ['name' => 'footer[text_color]', 'value' => $f['text_color'] ?? '', 'attr' => 'data-footer-color', 'field' => 'fg', 'fallback' => '#ffffff'])</div>
        </div>
        <small class="cms-hint">Leave blank to use the brand colour from Settings › Branding.</small>
    </div>

    <div class="admin-panel">
        <h2 class="cms-panel-title"><i class="fas fa-share-nodes"></i> Social media</h2>
        <div class="cms-social-grid">
            @foreach(\App\Services\CmsContentService::SOCIAL_NETWORKS as $net => [$netLabel, $netIcon])
                <div class="form-group cms-social-field"><label><i class="fab {{ $netIcon }}"></i> {{ $netLabel }}</label><input type="url" name="footer[social][{{ $net }}]" value="{{ data_get($f, 'social.'.$net, '') }}" placeholder="https://"></div>
            @endforeach
        </div>
    </div>

</div>
</div>
    <div class="admin-panel" style="margin-top:18px">
        <h2 class="cms-panel-title"><i class="fas fa-table-columns"></i> Link columns</h2>
        <div class="cms-grid-3">
            @foreach([0, 1, 2] as $c)
                <div class="form-group"><label>Column {{ $c + 1 }} title</label><input name="footer[column_titles][{{ $c }}]" value="{{ $titles[$c] ?? '' }}" maxlength="60"></div>
            @endforeach
        </div>
        @include('admin.cms.partials.links', ['links' => $links, 'columns' => $titles])
    </div>
@include('admin.cms.partials.actions', ['previewUrl' => null])
</form>
@endsection
