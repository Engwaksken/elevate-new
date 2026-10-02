@php
    $type = $section['type'] ?? 'text';
    $index = $index ?? 0;
    $label = \App\Services\CmsContentService::BLOCKS[$type] ?? ucfirst(str_replace('_', ' ', $type));
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
        @endif
    </div>
</div>
