@php($ipath = $ipath ?? '')
@php($in = fn ($f) => $ipath ? $ipath.'['.$f.']' : null)
<div class="cms-item @if($kind === 'gallery') cms-item--gallery @endif" data-item>
    @if($kind === 'features')
        <div class="cms-grid-3">
            <div><label>Icon (Font Awesome)</label><input @if($in('icon')) name="{{ $in('icon') }}" @endif value="{{ $item['icon'] ?? '' }}" data-item-field="icon" placeholder="fa-graduation-cap"></div>
            <div><label>Title</label><input @if($in('title')) name="{{ $in('title') }}" @endif value="{{ $item['title'] ?? '' }}" data-item-field="title"></div>
            <div class="cms-item__row"><div style="flex:1"><label>Text</label><input @if($in('text')) name="{{ $in('text') }}" @endif value="{{ $item['text'] ?? '' }}" data-item-field="text"></div><button type="button" class="btn-icon danger" data-item-remove title="Remove"><i class="fas fa-xmark"></i></button></div>
        </div>
        <div class="cms-grid-2">
            <div><label>Link (optional)</label><input @if($in('url')) name="{{ $in('url') }}" @endif value="{{ $item['url'] ?? '' }}" data-item-field="url" placeholder="/path or https://"></div>
            <div><label>Link text</label><input @if($in('link_label')) name="{{ $in('link_label') }}" @endif value="{{ $item['link_label'] ?? '' }}" data-item-field="link_label" placeholder="e.g. Explore Learning"></div>
        </div>
    @elseif($kind === 'stats')
        <div class="cms-grid-3">
            <div><label>Value</label><input @if($in('value')) name="{{ $in('value') }}" @endif value="{{ $item['value'] ?? '' }}" data-item-field="value"></div>
            <div><label>Label</label><input @if($in('label')) name="{{ $in('label') }}" @endif value="{{ $item['label'] ?? '' }}" data-item-field="label"></div>
            <div class="cms-item__row"><label>&nbsp;</label><button type="button" class="btn-icon danger" data-item-remove title="Remove"><i class="fas fa-xmark"></i></button></div>
        </div>
        <div class="cms-grid-2">
            <div><label>Icon (Font Awesome, original style)</label><input @if($in('icon')) name="{{ $in('icon') }}" @endif value="{{ $item['icon'] ?? '' }}" data-item-field="icon" placeholder="fa-user"></div>
            <div><label>Description (original style)</label><input @if($in('text')) name="{{ $in('text') }}" @endif value="{{ $item['text'] ?? '' }}" data-item-field="text"></div>
        </div>
    @elseif($kind === 'steps')
        <div class="cms-grid-3">
            <div><label>Step title</label><input @if($in('title')) name="{{ $in('title') }}" @endif value="{{ $item['title'] ?? '' }}" data-item-field="title" placeholder="e.g. Learn"></div>
            <div class="cms-item__row" style="grid-column: span 2"><div style="flex:1"><label>Description</label><input @if($in('text')) name="{{ $in('text') }}" @endif value="{{ $item['text'] ?? '' }}" data-item-field="text"></div><button type="button" class="btn-icon danger" data-item-remove title="Remove"><i class="fas fa-xmark"></i></button></div>
        </div>
    @elseif($kind === 'gallery')
        <div class="cms-item__head"><span><i class="fas fa-grip-vertical"></i> Image</span><span><button type="button" class="btn-icon" title="Move up" data-item-move="up"><i class="fas fa-arrow-up"></i></button><button type="button" class="btn-icon" title="Move down" data-item-move="down"><i class="fas fa-arrow-down"></i></button><button type="button" class="btn-icon danger" data-item-remove title="Remove"><i class="fas fa-xmark"></i></button></span></div>
        @include('cms.fields.media', ['name' => $in('image_url'), 'value' => $item['image_url'] ?? '', 'attr' => 'data-item-field', 'field' => 'image_url'])
        <div class="cms-grid-2">
            <div><label>Caption</label><input @if($in('caption')) name="{{ $in('caption') }}" @endif value="{{ $item['caption'] ?? '' }}" data-item-field="caption"></div>
            <div><label>Link (optional)</label><input @if($in('link')) name="{{ $in('link') }}" @endif value="{{ $item['link'] ?? '' }}" data-item-field="link" placeholder="/path or https://"></div>
        </div>
    @endif
</div>
