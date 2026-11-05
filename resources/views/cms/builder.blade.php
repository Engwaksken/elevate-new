@php($sections = is_array($sections ?? null) ? array_values($sections) : [])
@php($blockIcons = ['hero' => 'fa-flag', 'text' => 'fa-align-left', 'image_text' => 'fa-image', 'features' => 'fa-table-cells-large', 'stats' => 'fa-chart-simple', 'cta' => 'fa-bullhorn', 'section' => 'fa-table-columns', 'gallery' => 'fa-images', 'video' => 'fa-film'])
{{-- Guarded so a stale route cache after deploy disables uploads instead of breaking the editor. --}}
<div class="cms-builder" data-cms-builder @if(Route::has('admin.cms.media.store')) data-upload-url="{{ route('admin.cms.media.store', [], false) }}" data-upload-limit="{{ \App\Http\Controllers\Admin\CmsMediaController::serverUploadLimit() }}" @endif>
    <div class="cms-builder__bar">
        <div class="cms-builder__title"><strong>Page sections</strong><span>Add sections, then open <em>Background &amp; layout</em> on any section to give it a colour, gradient, photo or video background.</span></div>
        <div class="cms-builder__tools">
            @if($starter ?? false)<button type="button" class="btn btn-outline btn-sm" data-load-preset data-replace><i class="fas fa-rotate-left"></i> Load original home page</button>@endif
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
        <p>No sections yet. Add one below@if($starter ?? false), or load the original home page content and layout to edit@endif.</p>
        @if($starter ?? false)<button type="button" class="btn btn-primary btn-sm" data-load-preset><i class="fas fa-rotate-left"></i> Load original home page</button>@endif
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
@foreach(['features', 'stats', 'gallery', 'steps'] as $kind)
<template data-item-template="{{ $kind }}">@include('cms.builder-item', ['kind' => $kind, 'item' => [], 'ipath' => ''])</template>
@endforeach
<template data-column-template>@include('cms.builder-column', ['column' => ['width' => '1/1', 'widgets' => []], 'cpath' => ''])</template>
@foreach(array_keys(\App\Services\CmsContentService::WIDGETS) as $wt)
<template data-widget-template="{{ $wt }}">@include('cms.builder-widget', ['widget' => ['type' => $wt], 'wpath' => ''])</template>
@endforeach

@if($starter ?? false)
{{-- The built-in home page as editable sections. --}}
<template data-preset>
@foreach(\App\Services\CmsContentService::defaultHomeSections() as $preset)
@include('cms.builder-block', ['index' => '__INDEX__', 'section' => $preset])
@endforeach
</template>
@endif
