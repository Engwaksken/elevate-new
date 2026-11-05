@php
    $wtype = $widget['type'] ?? 'text';
    $wpath = $wpath ?? '';
    $wn = fn ($f) => $wpath ? $wpath.'['.$f.']' : null;
    $widgetLabels = \App\Services\CmsContentService::WIDGETS;
@endphp
<div class="cms-widget" data-widget data-widget-type="{{ $wtype }}">
    <div class="cms-widget__head">
        <span class="cms-widget__type">{{ $widgetLabels[$wtype] ?? ucfirst($wtype) }}</span>
        <div class="cms-widget__actions">
            <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
        </div>
    </div>
    <input type="hidden" @if($wn('type')) name="{{ $wn('type') }}" @endif value="{{ $wtype }}" data-widget-type-field>
    <div class="cms-widget__body">
        @if($wtype === 'heading')
            <div class="cms-grid-2">
                <div><label>Level</label><select @if($wn('level')) name="{{ $wn('level') }}" @endif data-widget-field="level">@foreach(['h2', 'h3', 'h4'] as $lv)<option value="{{ $lv }}" @selected(($widget['level'] ?? 'h2') === $lv)>{{ strtoupper($lv) }}</option>@endforeach</select></div>
                <div><label>Text</label><input @if($wn('text')) name="{{ $wn('text') }}" @endif value="{{ $widget['text'] ?? '' }}" data-widget-field="text" placeholder="Heading text"></div>
            </div>
        @elseif($wtype === 'text')
            <label>Content (Markdown)</label><textarea @if($wn('content')) name="{{ $wn('content') }}" @endif data-widget-field="content" rows="4">{{ $widget['content'] ?? '' }}</textarea>
        @elseif($wtype === 'image')
            <label>Image</label>
            @include('cms.fields.media', ['name' => $wn('image_url'), 'value' => $widget['image_url'] ?? '', 'attr' => 'data-widget-field', 'field' => 'image_url', 'kind' => 'image'])
            <label>Alt text</label><input @if($wn('alt')) name="{{ $wn('alt') }}" @endif value="{{ $widget['alt'] ?? '' }}" data-widget-field="alt" placeholder="Describe the image for screen readers">
        @elseif($wtype === 'button')
            <div class="cms-grid-3">
                <div><label>Label</label><input @if($wn('label')) name="{{ $wn('label') }}" @endif value="{{ $widget['label'] ?? '' }}" data-widget-field="label"></div>
                <div><label>URL</label><input @if($wn('url')) name="{{ $wn('url') }}" @endif value="{{ $widget['url'] ?? '' }}" data-widget-field="url" placeholder="/path or https://"></div>
                <div><label>Style</label><select @if($wn('style')) name="{{ $wn('style') }}" @endif data-widget-field="style">@foreach(['primary' => 'Primary', 'outline' => 'Outline', 'gold' => 'Gold'] as $sv => $sl)<option value="{{ $sv }}" @selected(($widget['style'] ?? 'primary') === $sv)>{{ $sl }}</option>@endforeach</select></div>
            </div>
        @elseif($wtype === 'spacer')
            <label>Height (px)</label><input type="number" @if($wn('height')) name="{{ $wn('height') }}" @endif value="{{ $widget['height'] ?? '' }}" data-widget-field="height" placeholder="e.g. 40" min="0" max="200">
        @elseif($wtype === 'divider')
            <p class="cms-widget__note">Horizontal rule - no settings.</p>
        @elseif($wtype === 'video')
            <label>Video</label>
            @include('cms.fields.media', ['name' => $wn('embed_url'), 'value' => $widget['embed_url'] ?? '', 'attr' => 'data-widget-field', 'field' => 'embed_url', 'kind' => 'video'])
        @elseif($wtype === 'html')
            <label>HTML (sanitized server-side)</label><textarea @if($wn('content')) name="{{ $wn('content') }}" @endif data-widget-field="content" rows="4">{{ $widget['content'] ?? '' }}</textarea>
        @endif
    </div>
</div>
