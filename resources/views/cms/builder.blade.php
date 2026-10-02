@php($sections = is_array($sections ?? null) ? $sections : [])
<div class="cms-builder" data-cms-builder>
    <div class="cms-builder__bar">
        <div class="cms-builder__title"><strong>Page sections</strong><span>Build your page visually by adding, arranging and removing blocks. Use the Markdown content box below for any extra text.</span></div>
        <div class="cms-builder__add">
            @foreach(\App\Services\CmsContentService::BLOCKS as $type => $label)
                <button type="button" class="btn btn-outline btn-sm" data-add-block="{{ $type }}"><i class="fas fa-plus"></i> {{ $label }}</button>
            @endforeach
        </div>
    </div>
    <div class="cms-builder__blocks" data-cms-blocks>
        @foreach($sections as $i => $section)
            @include('cms.builder-block', ['index' => $i, 'section' => $section])
        @endforeach
    </div>
    <div class="cms-builder__empty" data-cms-empty @if(count($sections)) hidden @endif>No sections yet — add a block above, or use the Markdown content box below.</div>
</div>

<template data-block-template="hero">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'hero']])</template>
<template data-block-template="text">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'text']])</template>
<template data-block-template="image_text">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'image_text']])</template>
<template data-block-template="features">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'features', 'items' => [['icon' => '', 'title' => '', 'text' => '']]]])</template>
<template data-block-template="stats">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'stats', 'items' => [['value' => '', 'label' => '']]]])</template>
<template data-block-template="cta">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'cta']])</template>

<template data-item-template="features">
    <div class="cms-item" data-item>
        <div class="cms-grid-3">
            <div><label>Icon (Font Awesome)</label><input data-item-field="icon" placeholder="fa-graduation-cap"></div>
            <div><label>Title</label><input data-item-field="title"></div>
            <div class="cms-item__row"><label>Text</label><input data-item-field="text"><button type="button" class="btn-icon danger" data-item-remove><i class="fas fa-xmark"></i></button></div>
        </div>
    </div>
</template>

<template data-item-template="stats">
    <div class="cms-item" data-item>
        <div class="cms-grid-3">
            <div><label>Value</label><input data-item-field="value"></div>
            <div><label>Label</label><input data-item-field="label"></div>
            <div class="cms-item__row"><label>&nbsp;</label><button type="button" class="btn-icon danger" data-item-remove><i class="fas fa-xmark"></i></button></div>
        </div>
    </div>
</template>
