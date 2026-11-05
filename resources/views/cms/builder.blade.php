@php($sections = is_array($sections ?? null) ? array_values($sections) : [])
@php($blockIcons = ['hero' => 'fa-flag', 'text' => 'fa-align-left', 'image_text' => 'fa-image', 'features' => 'fa-table-cells-large', 'stats' => 'fa-chart-simple', 'cta' => 'fa-bullhorn', 'section' => 'fa-table-columns', 'gallery' => 'fa-images', 'video' => 'fa-film'])
{{-- Guarded so a stale route cache after deploy disables uploads instead of breaking the editor. --}}
<div class="cms-builder" data-cms-builder @if(Route::has('admin.cms.media.store')) data-upload-url="{{ route('admin.cms.media.store') }}" @endif>
    <div class="cms-builder__bar">
        <div class="cms-builder__title"><strong>Page sections</strong><span>Add sections, then open <em>Background &amp; layout</em> on any section to give it a colour, gradient, photo or video background.</span></div>
        <div class="cms-builder__tools">
            <button type="button" class="btn btn-outline btn-sm" data-expand-all><i class="fas fa-up-right-and-down-left-from-center"></i> Expand all</button>
            <button type="button" class="btn btn-outline btn-sm" data-collapse-all><i class="fas fa-down-left-and-up-right-to-center"></i> Collapse all</button>
        </div>
    </div>
    <div class="cms-builder__blocks" data-cms-blocks>
        @foreach($sections as $i => $section)
            @include('cms.builder-block', ['index' => $i, 'section' => $section])
        @endforeach
    </div>
    <div class="cms-builder__empty" data-cms-empty @if(count($sections)) hidden @endif>
        <p>No sections yet. Add one below@if($starter ?? false), or start from a ready-made layout you can edit@endif.</p>
        @if($starter ?? false)<button type="button" class="btn btn-primary btn-sm" data-load-preset><i class="fas fa-wand-magic-sparkles"></i> Load starter home page</button>@endif
    </div>
    <div class="cms-builder__add">
        <span class="cms-builder__add-label">Add section</span>
        @foreach(\App\Services\CmsContentService::BLOCKS as $type => $label)
            <button type="button" class="cms-add-tile" data-add-block="{{ $type }}"><i class="fas {{ $blockIcons[$type] ?? 'fa-square' }}"></i><span>{{ $label }}</span></button>
        @endforeach
    </div>
</div>

@foreach(array_keys(\App\Services\CmsContentService::BLOCKS) as $type)
<template data-block-template="{{ $type }}">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => $type] + match ($type) {
    'features' => ['items' => [['icon' => '', 'title' => '', 'text' => '']]],
    'stats' => ['items' => [['value' => '', 'label' => '']]],
    'section' => ['columns' => [['width' => '1/1', 'widgets' => []]]],
    'gallery' => ['items' => [['image_url' => '', 'caption' => '', 'link' => '']]],
    default => [],
}])</template>
@endforeach
@foreach(['features', 'stats', 'gallery'] as $kind)
<template data-item-template="{{ $kind }}">@include('cms.builder-item', ['kind' => $kind, 'item' => [], 'ipath' => ''])</template>
@endforeach
<template data-column-template>@include('cms.builder-column', ['column' => ['width' => '1/1', 'widgets' => []], 'cpath' => ''])</template>
@foreach(array_keys(\App\Services\CmsContentService::WIDGETS) as $wt)
<template data-widget-template="{{ $wt }}">@include('cms.builder-widget', ['widget' => ['type' => $wt], 'wpath' => ''])</template>
@endforeach

@if($starter ?? false)
<template data-preset>
@foreach([
    ['type' => 'hero', 'label' => 'Main banner', 'eyebrow' => 'Your complete growth journey', 'title' => 'Learn, connect, build your career and move into opportunity.', 'text' => 'Training, mentorship, career development, jobs and digital resources under one account.', 'primary_label' => 'Get started', 'primary_url' => '/register', 'secondary_label' => 'Browse courses', 'secondary_url' => '/learning', 'style' => ['bg' => 'gradient', 'bg_color' => '#800000', 'bg_color2' => '#4a0000', 'gradient_angle' => 135, 'padding' => 'lg']],
    ['type' => 'stats', 'label' => 'Impact numbers', 'title' => 'Our impact', 'items' => [['value' => '', 'label' => 'Women trained'], ['value' => '', 'label' => 'Mentors'], ['value' => '', 'label' => 'Partner employers']], 'style' => ['bg' => 'none', 'align' => 'center']],
    ['type' => 'features', 'label' => 'What we offer', 'title' => 'Everything you need to grow', 'intro' => 'One account for learning, mentorship and jobs.', 'items' => [['icon' => 'fa-graduation-cap', 'title' => 'Learning', 'text' => 'Structured courses with certificates.'], ['icon' => 'fa-handshake', 'title' => 'Mentorship', 'text' => 'Guidance from women in tech.'], ['icon' => 'fa-briefcase', 'title' => 'Jobs', 'text' => 'Opportunities from partner employers.']], 'style' => ['bg' => 'color', 'bg_color' => '#fffaf5']],
    ['type' => 'gallery', 'label' => 'Photo grid', 'title' => 'Moments from our programmes', 'columns' => 4, 'ratio' => 'square', 'items' => [['image_url' => '', 'caption' => '', 'link' => '']]],
    ['type' => 'cta', 'label' => 'Closing call to action', 'title' => 'Ready to start?', 'content' => 'Create your free account today.', 'button_label' => 'Register now', 'button_url' => '/register', 'style' => ['bg' => 'color', 'bg_color' => '#800000', 'align' => 'center']],
] as $p => $preset)
@include('cms.builder-block', ['index' => '__INDEX__', 'section' => $preset])
@endforeach
</template>
@endif
