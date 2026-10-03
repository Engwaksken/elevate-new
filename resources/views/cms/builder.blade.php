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

<template data-block-template="section">@include('cms.builder-block', ['index' => '__INDEX__', 'section' => ['type' => 'section', 'background' => '', 'full_width' => false, 'columns' => [['width' => '1/1', 'widgets' => []]]]])</template>

<template data-column-template>
    <div class="cms-column" data-column>
        <div class="cms-column__head">
            <span class="cms-column__title"><i class="fas fa-columns"></i> Column</span>
            <div class="cms-column__actions">
                <select data-column-width>
                    <option value="1/1" selected>Full width</option>
                    <option value="1/2">1/2</option>
                    <option value="1/3">1/3</option>
                    <option value="2/3">2/3</option>
                    <option value="1/4">1/4</option>
                    <option value="3/4">3/4</option>
                    <option value="1/6">1/6</option>
                    <option value="5/6">5/6</option>
                </select>
                <button type="button" class="btn-icon" title="Move up" data-column-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-column-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove column" data-column-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <div class="cms-column__widgets" data-widgets></div>
        <div class="cms-column__palette">
            <span class="cms-column__palette-label">Add widget:</span>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="heading"><i class="fas fa-plus"></i> Heading</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="text"><i class="fas fa-plus"></i> Text</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="image"><i class="fas fa-plus"></i> Image</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="button"><i class="fas fa-plus"></i> Button</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="spacer"><i class="fas fa-plus"></i> Spacer</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="divider"><i class="fas fa-plus"></i> Divider</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="video"><i class="fas fa-plus"></i> Video</button>
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="html"><i class="fas fa-plus"></i> HTML</button>
        </div>
    </div>
</template>

<template data-widget-template="heading">
    <div class="cms-widget" data-widget data-widget-type="heading">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Heading</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="heading" data-widget-type-field>
        <div class="cms-widget__body">
            <div class="cms-grid-2">
                <div><label>Level</label><select data-widget-field="level"><option value="h2" selected>H2</option><option value="h3">H3</option><option value="h4">H4</option></select></div>
                <div><label>Text</label><input data-widget-field="text" placeholder="Heading text"></div>
            </div>
        </div>
    </div>
</template>

<template data-widget-template="text">
    <div class="cms-widget" data-widget data-widget-type="text">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Text</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="text" data-widget-type-field>
        <div class="cms-widget__body">
            <label>Content (Markdown)</label><textarea data-widget-field="content" rows="4"></textarea>
        </div>
    </div>
</template>

<template data-widget-template="image">
    <div class="cms-widget" data-widget data-widget-type="image">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Image</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="image" data-widget-type-field>
        <div class="cms-widget__body">
            <div class="cms-grid-2">
                <div><label>Image URL</label><input data-widget-field="image_url" placeholder="https://..."></div>
                <div><label>Alt text</label><input data-widget-field="alt"></div>
            </div>
        </div>
    </div>
</template>

<template data-widget-template="button">
    <div class="cms-widget" data-widget data-widget-type="button">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Button</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="button" data-widget-type-field>
        <div class="cms-widget__body">
            <div class="cms-grid-3">
                <div><label>Label</label><input data-widget-field="label"></div>
                <div><label>URL</label><input data-widget-field="url" placeholder="/path or https://"></div>
                <div><label>Style</label><select data-widget-field="style"><option value="primary" selected>Primary</option><option value="outline">Outline</option><option value="gold">Gold</option></select></div>
            </div>
        </div>
    </div>
</template>

<template data-widget-template="spacer">
    <div class="cms-widget" data-widget data-widget-type="spacer">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Spacer</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="spacer" data-widget-type-field>
        <div class="cms-widget__body">
            <label>Height (px)</label><input type="number" data-widget-field="height" placeholder="e.g. 40" min="0">
        </div>
    </div>
</template>

<template data-widget-template="divider">
    <div class="cms-widget" data-widget data-widget-type="divider">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Divider</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="divider" data-widget-type-field>
        <div class="cms-widget__body">
            <p class="cms-widget__note">Horizontal rule — no settings.</p>
        </div>
    </div>
</template>

<template data-widget-template="video">
    <div class="cms-widget" data-widget data-widget-type="video">
        <div class="cms-widget__head">
            <span class="cms-widget__type">Video</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="video" data-widget-type-field>
        <div class="cms-widget__body">
            <label>Embed URL (YouTube / Vimeo)</label><input data-widget-field="embed_url" placeholder="https://www.youtube.com/embed/...">
        </div>
    </div>
</template>

<template data-widget-template="html">
    <div class="cms-widget" data-widget data-widget-type="html">
        <div class="cms-widget__head">
            <span class="cms-widget__type">HTML</span>
            <div class="cms-widget__actions">
                <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
            </div>
        </div>
        <input type="hidden" value="html" data-widget-type-field>
        <div class="cms-widget__body">
            <label>HTML (sanitized server-side)</label><textarea data-widget-field="content" rows="4"></textarea>
        </div>
    </div>
</template>
