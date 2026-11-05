{{-- "Background & layout" options shared by every section block. --}}
@php
    $st = is_array($style ?? null) ? $style : [];
    $sn = fn ($f) => "sections[{$index}][style][{$f}]";
    $bgMode = $st['bg'] ?? 'none';
@endphp
<details class="cms-style" data-style-panel @if($bgMode !== 'none') open @endif>
    <summary><i class="fas fa-palette"></i> Background &amp; layout <span class="cms-style__badge" data-style-badge>{{ \App\Services\CmsContentService::BACKGROUNDS[$bgMode] ?? 'Default' }}</span></summary>
    <div class="cms-style__body">
        <div class="cms-style__modes" role="radiogroup" aria-label="Background type">
            @foreach(\App\Services\CmsContentService::BACKGROUNDS as $mode => $modeLabel)
                <label class="cms-style__mode">
                    <input type="radio" name="{{ $sn('bg') }}" value="{{ $mode }}" data-block-field="style.bg" data-bg-mode @checked($bgMode === $mode)>
                    <span><i class="fas {{ ['none' => 'fa-ban', 'color' => 'fa-fill-drip', 'gradient' => 'fa-swatchbook', 'image' => 'fa-image', 'video' => 'fa-film'][$mode] }}"></i> {{ $modeLabel }}</span>
                </label>
            @endforeach
        </div>

        <div class="cms-grid-3" data-bg-show="color gradient image video">
            <div><label>Background colour</label>@include('cms.fields.color', ['name' => $sn('bg_color'), 'value' => $st['bg_color'] ?? '', 'attr' => 'data-block-field', 'field' => 'style.bg_color'])</div>
            <div data-bg-show="gradient"><label>Second colour</label>@include('cms.fields.color', ['name' => $sn('bg_color2'), 'value' => $st['bg_color2'] ?? '', 'attr' => 'data-block-field', 'field' => 'style.bg_color2', 'fallback' => '#d4af37'])</div>
            <div data-bg-show="gradient"><label>Angle (degrees)</label><input type="number" min="0" max="360" name="{{ $sn('gradient_angle') }}" value="{{ $st['gradient_angle'] ?? 135 }}" data-block-field="style.gradient_angle"></div>
        </div>

        <div data-bg-show="image">
            <label>Background image</label>
            @include('cms.fields.media', ['name' => $sn('bg_image'), 'value' => $st['bg_image'] ?? '', 'attr' => 'data-block-field', 'field' => 'style.bg_image', 'kind' => 'image'])
            <div class="cms-grid-3">
                <div><label>Fit</label><select name="{{ $sn('bg_size') }}" data-block-field="style.bg_size">@foreach(['cover' => 'Cover (fill)', 'contain' => 'Contain (whole image)', 'repeat' => 'Tile / pattern'] as $v => $l)<option value="{{ $v }}" @selected(($st['bg_size'] ?? 'cover') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div><label>Focus</label><select name="{{ $sn('bg_position') }}" data-block-field="style.bg_position">@foreach(['center' => 'Centre', 'top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'] as $v => $l)<option value="{{ $v }}" @selected(($st['bg_position'] ?? 'center') === $v)>{{ $l }}</option>@endforeach</select></div>
                <div><label>&nbsp;</label><label class="cms-check"><input type="checkbox" name="{{ $sn('parallax') }}" value="1" data-block-field="style.parallax" @checked(!empty($st['parallax']))> Fixed (parallax) on desktop</label></div>
            </div>
        </div>

        <div data-bg-show="video">
            <label>Background video</label>
            @include('cms.fields.media', ['name' => $sn('bg_video'), 'value' => $st['bg_video'] ?? '', 'attr' => 'data-block-field', 'field' => 'style.bg_video', 'kind' => 'video'])
            <span class="cms-hint">Plays muted and looped behind the content. MP4 uploads load fastest; keep them short (under ~15 MB).</span>
        </div>

        <div class="cms-grid-3" data-bg-show="image video color gradient">
            <div><label>Overlay colour</label>@include('cms.fields.color', ['name' => $sn('overlay_color'), 'value' => $st['overlay_color'] ?? '', 'attr' => 'data-block-field', 'field' => 'style.overlay_color', 'fallback' => '#000000', 'placeholder' => '#000000'])</div>
            <div><label>Overlay strength <output data-range-out>{{ (int) ($st['overlay_opacity'] ?? 0) }}%</output></label><input type="range" min="0" max="90" step="5" name="{{ $sn('overlay_opacity') }}" value="{{ (int) ($st['overlay_opacity'] ?? 0) }}" data-block-field="style.overlay_opacity" data-range></div>
            <div><label>Text colour</label><select name="{{ $sn('text_tone') }}" data-block-field="style.text_tone">@foreach(['auto' => 'Automatic', 'light' => 'Light text (for dark backgrounds)', 'dark' => 'Dark text (for light backgrounds)'] as $v => $l)<option value="{{ $v }}" @selected(($st['text_tone'] ?? 'auto') === $v)>{{ $l }}</option>@endforeach</select></div>
        </div>

        <div class="cms-style__layout">
            <div><label>Spacing</label><select name="{{ $sn('padding') }}" data-block-field="style.padding">@foreach(['none' => 'None', 'sm' => 'Compact', 'md' => 'Normal', 'lg' => 'Large', 'xl' => 'Extra large'] as $v => $l)<option value="{{ $v }}" @selected(($st['padding'] ?? 'md') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Alignment</label><select name="{{ $sn('align') }}" data-block-field="style.align">@foreach(['left' => 'Left', 'center' => 'Centred'] as $v => $l)<option value="{{ $v }}" @selected(($st['align'] ?? 'left') === $v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Min. height (% of screen)</label><input type="number" min="0" max="100" step="5" name="{{ $sn('min_height') }}" value="{{ (int) ($st['min_height'] ?? 0) ?: '' }}" placeholder="Auto" data-block-field="style.min_height"></div>
            <div><label>Anchor ID</label><input name="{{ $sn('anchor') }}" value="{{ $st['anchor'] ?? '' }}" placeholder="e.g. about" pattern="[a-z][a-z0-9-]*" data-block-field="style.anchor"></div>
        </div>
        <label class="cms-check"><input type="checkbox" name="{{ $sn('full_width') }}" value="1" data-block-field="style.full_width" @checked(!empty($st['full_width']))> Content spans the full screen width (edge-to-edge)</label>
        <span class="cms-hint">Anchor IDs let menu links jump to this section, e.g. a header link to <code>#about</code>.</span>
    </div>
</details>
