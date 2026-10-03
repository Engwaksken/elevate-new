@php
    $type = $section['type'] ?? 'text';
    $index = $index ?? 0;
    $label = \App\Services\CmsContentService::BLOCKS[$type] ?? ($type === 'section' ? 'Section / Grid' : ucfirst(str_replace('_', ' ', $type)));
    $widgetLabels = ['heading' => 'Heading', 'text' => 'Text', 'image' => 'Image', 'button' => 'Button', 'spacer' => 'Spacer', 'divider' => 'Divider', 'video' => 'Video', 'html' => 'HTML'];
    $columnWidths = ['1/1' => 'Full width', '1/2' => '1/2', '1/3' => '1/3', '2/3' => '2/3', '1/4' => '1/4', '3/4' => '3/4', '1/6' => '1/6', '5/6' => '5/6'];
@endphp
<div class="cms-block" data-block data-block-type="{{ $type }}">
    <div class="cms-block__head">
        <span class="cms-block__type"><i class="fas fa-grip-vertical"></i> {{ $label }}</span>
        <div class="cms-block__actions">
            <button type="button" class="btn-icon" title="Move up" data-block-move="up"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="btn-icon" title="Move down" data-block-move="down"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="btn-icon danger" title="Remove" data-block-remove><i class="fas fa-trash"></i></button>
        </div>
    </div>
    <div class="cms-block__body">
        <input type="hidden" name="sections[{{ $index }}][type]" value="{{ $type }}" data-block-type-field>
        @if($type === 'hero')
            <div class="cms-grid-2">
                <div><label>Eyebrow</label><input name="sections[{{ $index }}][eyebrow]" value="{{ $section['eyebrow'] ?? '' }}" data-block-field="eyebrow"></div>
                <div><label>Title</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title"></div>
            </div>
            <label>Text</label><textarea name="sections[{{ $index }}][text]" data-block-field="text" rows="2">{{ $section['text'] ?? '' }}</textarea>
            <div class="cms-grid-2">
                <div><label>Primary button label</label><input name="sections[{{ $index }}][primary_label]" value="{{ $section['primary_label'] ?? '' }}" data-block-field="primary_label"></div>
                <div><label>Primary button URL</label><input name="sections[{{ $index }}][primary_url]" value="{{ $section['primary_url'] ?? '' }}" data-block-field="primary_url" placeholder="/path or https://"></div>
                <div><label>Secondary button label</label><input name="sections[{{ $index }}][secondary_label]" value="{{ $section['secondary_label'] ?? '' }}" data-block-field="secondary_label"></div>
                <div><label>Secondary button URL</label><input name="sections[{{ $index }}][secondary_url]" value="{{ $section['secondary_url'] ?? '' }}" data-block-field="secondary_url" placeholder="/path or https://"></div>
            </div>
            <label>Image URL (optional)</label><input name="sections[{{ $index }}][image_url]" value="{{ $section['image_url'] ?? '' }}" data-block-field="image_url" placeholder="https://...">
        @elseif($type === 'text')
            <label>Heading</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Content (Markdown)</label><textarea name="sections[{{ $index }}][content]" data-block-field="content" rows="6">{{ $section['content'] ?? '' }}</textarea>
        @elseif($type === 'image_text')
            <label>Heading</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Content (Markdown)</label><textarea name="sections[{{ $index }}][content]" data-block-field="content" rows="5">{{ $section['content'] ?? '' }}</textarea>
            <label>Image URL</label><input name="sections[{{ $index }}][image_url]" value="{{ $section['image_url'] ?? '' }}" data-block-field="image_url" placeholder="https://...">
            <label class="cms-check"><input type="checkbox" name="sections[{{ $index }}][reverse]" value="1" data-block-field="reverse" @checked(!empty($section['reverse']))> Image on the right</label>
        @elseif($type === 'features')
            <label>Heading</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Intro</label><textarea name="sections[{{ $index }}][intro]" data-block-field="intro" rows="2">{{ $section['intro'] ?? '' }}</textarea>
            <div class="cms-items" data-items>
                @foreach($section['items'] ?? [['icon' => '', 'title' => '', 'text' => '']] as $j => $item)
                    <div class="cms-item" data-item>
                        <div class="cms-grid-3">
                            <div><label>Icon (Font Awesome)</label><input name="sections[{{ $index }}][items][{{ $j }}][icon]" value="{{ $item['icon'] ?? '' }}" data-item-field="icon" placeholder="fa-graduation-cap"></div>
                            <div><label>Title</label><input name="sections[{{ $index }}][items][{{ $j }}][title]" value="{{ $item['title'] ?? '' }}" data-item-field="title"></div>
                            <div class="cms-item__row"><label>Text</label><input name="sections[{{ $index }}][items][{{ $j }}][text]" value="{{ $item['text'] ?? '' }}" data-item-field="text"><button type="button" class="btn-icon danger" data-item-remove><i class="fas fa-xmark"></i></button></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-item="features"><i class="fas fa-plus"></i> Add feature</button>
        @elseif($type === 'stats')
            <label>Heading</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <div class="cms-items" data-items>
                @foreach($section['items'] ?? [['value' => '', 'label' => '']] as $j => $item)
                    <div class="cms-item" data-item>
                        <div class="cms-grid-3">
                            <div><label>Value</label><input name="sections[{{ $index }}][items][{{ $j }}][value]" value="{{ $item['value'] ?? '' }}" data-item-field="value"></div>
                            <div><label>Label</label><input name="sections[{{ $index }}][items][{{ $j }}][label]" value="{{ $item['label'] ?? '' }}" data-item-field="label"></div>
                            <div class="cms-item__row"><label>&nbsp;</label><button type="button" class="btn-icon danger" data-item-remove><i class="fas fa-xmark"></i></button></div>
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-item="stats"><i class="fas fa-plus"></i> Add statistic</button>
        @elseif($type === 'cta')
            <label>Heading</label><input name="sections[{{ $index }}][title]" value="{{ $section['title'] ?? '' }}" data-block-field="title">
            <label>Text</label><textarea name="sections[{{ $index }}][content]" data-block-field="content" rows="2">{{ $section['content'] ?? '' }}</textarea>
            <div class="cms-grid-2">
                <div><label>Button label</label><input name="sections[{{ $index }}][button_label]" value="{{ $section['button_label'] ?? '' }}" data-block-field="button_label"></div>
                <div><label>Button URL</label><input name="sections[{{ $index }}][button_url]" value="{{ $section['button_url'] ?? '' }}" data-block-field="button_url"></div>
            </div>
        @elseif($type === 'section')
            <div class="cms-grid-2 cms-section-settings">
                <div>
                    <label>Background</label>
                    <input name="sections[{{ $index }}][background]" value="{{ $section['background'] ?? '' }}" data-block-field="background" placeholder="#hex colour, or type 'soft'">
                    <span class="cms-hint">Leave blank for none, type <code>soft</code> for cream, or a hex colour like <code>#f5f0ea</code>.</span>
                </div>
                <div>
                    <label>&nbsp;</label>
                    <label class="cms-check"><input type="checkbox" name="sections[{{ $index }}][full_width]" value="1" data-block-field="full_width" @checked(!empty($section['full_width']))> Full width (edge-to-edge)</label>
                </div>
            </div>
            <div class="cms-columns" data-columns>
                @foreach($section['columns'] ?? [] as $c => $column)
                    @php($cw = $column['width'] ?? '1/1')
                    <div class="cms-column" data-column>
                        <div class="cms-column__head">
                            <span class="cms-column__title"><i class="fas fa-columns"></i> Column</span>
                            <div class="cms-column__actions">
                                <select name="sections[{{ $index }}][columns][{{ $c }}][width]" data-column-width>
                                    @foreach($columnWidths as $value => $text)
                                        <option value="{{ $value }}" @selected($cw === $value)>{{ $text }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn-icon" title="Move up" data-column-move="up"><i class="fas fa-arrow-up"></i></button>
                                <button type="button" class="btn-icon" title="Move down" data-column-move="down"><i class="fas fa-arrow-down"></i></button>
                                <button type="button" class="btn-icon danger" title="Remove column" data-column-remove><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <div class="cms-column__widgets" data-widgets>
                            @foreach($column['widgets'] ?? [] as $w => $widget)
                                @php($wtype = $widget['type'] ?? 'text')
                                @php($wpath = "sections[{$index}][columns][{$c}][widgets][{$w}]")
                                <div class="cms-widget" data-widget data-widget-type="{{ $wtype }}">
                                    <div class="cms-widget__head">
                                        <span class="cms-widget__type">{{ $widgetLabels[$wtype] ?? ucfirst(str_replace('_', ' ', $wtype)) }}</span>
                                        <div class="cms-widget__actions">
                                            <button type="button" class="btn-icon" title="Move up" data-widget-move="up"><i class="fas fa-arrow-up"></i></button>
                                            <button type="button" class="btn-icon" title="Move down" data-widget-move="down"><i class="fas fa-arrow-down"></i></button>
                                            <button type="button" class="btn-icon danger" title="Remove widget" data-widget-remove><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <input type="hidden" name="{{ $wpath }}[type]" value="{{ $wtype }}" data-widget-type-field>
                                    <div class="cms-widget__body">
                                        @if($wtype === 'heading')
                                            <div class="cms-grid-2">
                                                <div><label>Level</label>
                                                    <select name="{{ $wpath }}[level]" data-widget-field="level">
                                                        @foreach(['h2', 'h3', 'h4'] as $lv)<option value="{{ $lv }}" @selected(($widget['level'] ?? 'h2') === $lv)>{{ strtoupper($lv) }}</option>@endforeach
                                                    </select>
                                                </div>
                                                <div><label>Text</label><input name="{{ $wpath }}[text]" value="{{ $widget['text'] ?? '' }}" data-widget-field="text"></div>
                                            </div>
                                        @elseif($wtype === 'text')
                                            <label>Content (Markdown)</label><textarea name="{{ $wpath }}[content]" data-widget-field="content" rows="4">{{ $widget['content'] ?? '' }}</textarea>
                                        @elseif($wtype === 'image')
                                            <div class="cms-grid-2">
                                                <div><label>Image URL</label><input name="{{ $wpath }}[image_url]" value="{{ $widget['image_url'] ?? '' }}" data-widget-field="image_url" placeholder="https://..."></div>
                                                <div><label>Alt text</label><input name="{{ $wpath }}[alt]" value="{{ $widget['alt'] ?? '' }}" data-widget-field="alt"></div>
                                            </div>
                                        @elseif($wtype === 'button')
                                            <div class="cms-grid-3">
                                                <div><label>Label</label><input name="{{ $wpath }}[label]" value="{{ $widget['label'] ?? '' }}" data-widget-field="label"></div>
                                                <div><label>URL</label><input name="{{ $wpath }}[url]" value="{{ $widget['url'] ?? '' }}" data-widget-field="url" placeholder="/path or https://"></div>
                                                <div><label>Style</label>
                                                    <select name="{{ $wpath }}[style]" data-widget-field="style">
                                                        @foreach(['primary' => 'Primary', 'outline' => 'Outline', 'gold' => 'Gold'] as $sv => $sl)<option value="{{ $sv }}" @selected(($widget['style'] ?? 'primary') === $sv)>{{ $sl }}</option>@endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @elseif($wtype === 'spacer')
                                            <label>Height (px)</label><input type="number" name="{{ $wpath }}[height]" value="{{ $widget['height'] ?? '' }}" data-widget-field="height" placeholder="e.g. 40" min="0">
                                        @elseif($wtype === 'divider')
                                            <p class="cms-widget__note">Horizontal rule — no settings.</p>
                                        @elseif($wtype === 'video')
                                            <label>Embed URL (YouTube / Vimeo)</label><input name="{{ $wpath }}[embed_url]" value="{{ $widget['embed_url'] ?? '' }}" data-widget-field="embed_url" placeholder="https://www.youtube.com/embed/...">
                                        @elseif($wtype === 'html')
                                            <label>HTML (sanitized server-side)</label><textarea name="{{ $wpath }}[content]" data-widget-field="content" rows="4">{{ $widget['content'] ?? '' }}</textarea>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="cms-column__palette">
                            <span class="cms-column__palette-label">Add widget:</span>
                            @foreach(['heading', 'text', 'image', 'button', 'spacer', 'divider', 'video', 'html'] as $wt)
                                <button type="button" class="btn btn-outline btn-sm" data-add-widget="{{ $wt }}"><i class="fas fa-plus"></i> {{ $widgetLabels[$wt] }}</button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline btn-sm" data-add-column><i class="fas fa-plus"></i> Add column</button>
        @endif
    </div>
</div>
