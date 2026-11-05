@php
    $type = $section['type'] ?? 'text';
    $index = $index ?? 0;
    $label = \App\Services\CmsContentService::BLOCKS[$type] ?? ucfirst(str_replace('_', ' ', $type));
    $icon = ['hero' => 'fa-flag', 'text' => 'fa-align-left', 'image_text' => 'fa-image', 'features' => 'fa-table-cells-large', 'stats' => 'fa-chart-simple', 'cta' => 'fa-bullhorn', 'section' => 'fa-table-columns', 'gallery' => 'fa-images', 'video' => 'fa-film'][$type] ?? 'fa-square';
    $n = fn ($f) => "sections[{$index}][{$f}]";
    $style = $section['style'] ?? null;
    // Older grid sections stored only a background colour; show it in the new panel.
    if (! is_array($style) && $type === 'section' && (! empty($section['background']) || ! empty($section['full_width']))) {
        $style = app(\App\Services\CmsContentService::class)->parseStyle(['full_width' => $section['full_width'] ?? false], (string) ($section['background'] ?? ''));
    }
    $isTemplate = $index === '__INDEX__';
@endphp
<div class="cms-block @if(!empty($section['hidden'])) is-hidden @endif" data-block data-block-type="{{ $type }}" @unless($isTemplate) data-collapsed @endunless>
    <div class="cms-block__head">
        <button type="button" class="cms-block__toggle" data-block-toggle aria-label="Expand or collapse">
            <i class="fas fa-chevron-down cms-block__chevron"></i>
            <span class="cms-block__type"><i class="fas {{ $icon }}"></i> {{ $label }}</span>
            <span class="cms-block__summary" data-block-summary>{{ $section['label'] ?? '' ?: ($section['title'] ?? ($section['eyebrow'] ?? '')) }}</span>
            <span class="cms-block__hidden-chip">Hidden</span>
        </button>
        <div class="cms-block__actions">
            <label class="cms-block__visibility" title="Hide this section on the live site without deleting it"><input type="checkbox" name="{{ $n('hidden') }}" value="1" data-block-field="hidden" data-block-hidden @checked(!empty($section['hidden']))> Hide</label>
            <button type="button" class="btn-icon" title="Duplicate" data-block-duplicate><i class="fas fa-clone"></i></button>
            <button type="button" class="btn-icon" title="Move up" data-block-move="up"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="btn-icon" title="Move down" data-block-move="down"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="btn-icon danger" title="Remove" data-block-remove><i class="fas fa-trash"></i></button>
        </div>
    </div>
    <div class="cms-block__body">
        <input type="hidden" name="{{ $n('type') }}" value="{{ $type }}" data-block-type-field>
        <label>Admin label <small>(only shown here, helps you find sections)</small></label>
        <input name="{{ $n('label') }}" value="{{ $section['label'] ?? '' }}" data-block-field="label" data-block-label maxlength="80" placeholder="e.g. Main banner">

        @if($type === 'hero')
            <div class="cms-grid-2">
                <div><label>Eyebrow</label><input name="{{ $n('eyebrow') }}" value="{{ $section['eyebrow'] ?? '' }}" data-block-field="eyebrow"></div>
                <div><label>Title</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title"></div>
            </div>
            <label>Text</label><textarea name="{{ $n('text') }}" data-block-field="text" rows="2">{{ $section['text'] ?? '' }}</textarea>
            <div class="cms-grid-2">
                <div><label>Primary button label</label><input name="{{ $n('primary_label') }}" value="{{ $section['primary_label'] ?? '' }}" data-block-field="primary_label"></div>
                <div><label>Primary button URL</label><input name="{{ $n('primary_url') }}" value="{{ $section['primary_url'] ?? '' }}" data-block-field="primary_url" placeholder="/path or https://"></div>
                <div><label>Secondary button label</label><input name="{{ $n('secondary_label') }}" value="{{ $section['secondary_label'] ?? '' }}" data-block-field="secondary_label"></div>
                <div><label>Secondary button URL</label><input name="{{ $n('secondary_url') }}" value="{{ $section['secondary_url'] ?? '' }}" data-block-field="secondary_url" placeholder="/path or https://"></div>
            </div>
            <label>Side image (optional)</label>
            @include('cms.fields.media', ['name' => $n('image_url'), 'value' => $section['image_url'] ?? '', 'attr' => 'data-block-field', 'field' => 'image_url'])
            <span class="cms-hint">For a full background photo or video behind the text, use <strong>Background &amp; layout</strong> below.</span>
        @elseif($type === 'text')
            <label>Heading</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Content (Markdown)</label><textarea name="{{ $n('content') }}" data-block-field="content" rows="6">{{ $section['content'] ?? '' }}</textarea>
        @elseif($type === 'image_text')
            <label>Heading</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Content (Markdown)</label><textarea name="{{ $n('content') }}" data-block-field="content" rows="5">{{ $section['content'] ?? '' }}</textarea>
            <label>Image</label>
            @include('cms.fields.media', ['name' => $n('image_url'), 'value' => $section['image_url'] ?? '', 'attr' => 'data-block-field', 'field' => 'image_url'])
            <label class="cms-check"><input type="checkbox" name="{{ $n('reverse') }}" value="1" data-block-field="reverse" @checked(!empty($section['reverse']))> Image on the right</label>
        @elseif($type === 'features')
            <label>Heading</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Intro</label><textarea name="{{ $n('intro') }}" data-block-field="intro" rows="2">{{ $section['intro'] ?? '' }}</textarea>
            <div class="cms-items" data-items>
                @foreach($section['items'] ?? [['icon' => '', 'title' => '', 'text' => '']] as $j => $item)
                    @include('cms.builder-item', ['kind' => 'features', 'item' => $item, 'ipath' => "sections[{$index}][items][{$j}]"])
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-item="features"><i class="fas fa-plus"></i> Add feature</button>
        @elseif($type === 'stats')
            <label>Heading</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <div class="cms-items" data-items>
                @foreach($section['items'] ?? [['value' => '', 'label' => '']] as $j => $item)
                    @include('cms.builder-item', ['kind' => 'stats', 'item' => $item, 'ipath' => "sections[{$index}][items][{$j}]"])
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-item="stats"><i class="fas fa-plus"></i> Add statistic</button>
        @elseif($type === 'cta')
            <label>Heading</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Text</label><textarea name="{{ $n('content') }}" data-block-field="content" rows="2">{{ $section['content'] ?? '' }}</textarea>
            <div class="cms-grid-2">
                <div><label>Button label</label><input name="{{ $n('button_label') }}" value="{{ $section['button_label'] ?? '' }}" data-block-field="button_label"></div>
                <div><label>Button URL</label><input name="{{ $n('button_url') }}" value="{{ $section['button_url'] ?? '' }}" data-block-field="button_url"></div>
            </div>
        @elseif($type === 'gallery')
            <label>Heading (optional)</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Intro (optional)</label><textarea name="{{ $n('intro') }}" data-block-field="intro" rows="2">{{ $section['intro'] ?? '' }}</textarea>
            <div class="cms-style__layout">
                <div><label>Columns</label><select name="{{ $n('columns') }}" data-block-field="columns">@foreach([2, 3, 4, 5, 6] as $c)<option value="{{ $c }}" @selected((int) ($section['columns'] ?? 3) === $c)>{{ $c }} per row</option>@endforeach</select></div>
                <div><label>Image shape</label><select name="{{ $n('ratio') }}" data-block-field="ratio">@foreach(['square' => 'Square', 'landscape' => 'Landscape (4:3)', 'portrait' => 'Portrait (3:4)', 'auto' => 'Original'] as $v => $l)<option value="{{ $v }}" @selected(($section['ratio'] ?? 'square') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div><label>Gap</label><select name="{{ $n('gap') }}" data-block-field="gap">@foreach(['none' => 'None', 'sm' => 'Small', 'md' => 'Medium', 'lg' => 'Large'] as $v => $l)<option value="{{ $v }}" @selected(($section['gap'] ?? 'md') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div><label>Captions</label><select name="{{ $n('captions') }}" data-block-field="captions">@foreach(['below' => 'Below image', 'overlay' => 'On image', 'hidden' => 'Hidden'] as $v => $l)<option value="{{ $v }}" @selected(($section['captions'] ?? 'below') === $v)>{{ $l }}</option>@endforeach</select></div>
            </div>
            <div class="cms-items cms-items--gallery" data-items>
                @foreach($section['items'] ?? [] as $j => $item)
                    @include('cms.builder-item', ['kind' => 'gallery', 'item' => $item, 'ipath' => "sections[{$index}][items][{$j}]"])
                @endforeach
            </div>
            <div class="action-group">
                <button type="button" class="btn btn-outline btn-sm" data-add-item="gallery"><i class="fas fa-plus"></i> Add image</button>
                <button type="button" class="btn btn-outline btn-sm" data-gallery-bulk><i class="fas fa-upload"></i> Upload several images</button>
                <input type="file" hidden multiple accept="image/png,image/jpeg,image/webp,image/gif" data-gallery-files>
            </div>
        @elseif($type === 'video')
            <div class="cms-grid-2">
                <div><label>Heading (optional)</label><input name="{{ $n('title') }}" value="{{ $section['title'] ?? '' }}" data-block-field="title"></div>
                <div><label>Player width</label><select name="{{ $n('width') }}" data-block-field="width">@foreach(['narrow' => 'Narrow', 'normal' => 'Normal', 'wide' => 'Wide'] as $v => $l)<option value="{{ $v }}" @selected(($section['width'] ?? 'normal') === $v)>{{ $l }}</option>@endforeach</select></div>
            </div>
            <label>Text (optional)</label><textarea name="{{ $n('text') }}" data-block-field="text" rows="2">{{ $section['text'] ?? '' }}</textarea>
            <label>Video</label>
            @include('cms.fields.media', ['name' => $n('video_url'), 'value' => $section['video_url'] ?? '', 'attr' => 'data-block-field', 'field' => 'video_url', 'kind' => 'video'])
            <label>Poster image (for uploaded videos)</label>
            @include('cms.fields.media', ['name' => $n('poster_url'), 'value' => $section['poster_url'] ?? '', 'attr' => 'data-block-field', 'field' => 'poster_url'])
            <label class="cms-check"><input type="checkbox" name="{{ $n('autoplay') }}" value="1" data-block-field="autoplay" @checked(!empty($section['autoplay']))> Autoplay (muted)</label>
        @elseif($type === 'section')
            <div class="cms-columns" data-columns>
                @foreach($section['columns'] ?? [] as $c => $column)
                    @include('cms.builder-column', ['column' => $column, 'cpath' => "sections[{$index}][columns][{$c}]"])
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-column><i class="fas fa-plus"></i> Add column</button>
        @endif

        @include('cms.style-panel', ['index' => $index, 'style' => $style])
    </div>
</div>
