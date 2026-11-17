<details class="dl-dropdown" data-dl-row-menu>
    <summary class="dl-more" aria-label="More actions for {{ $item->name }}" title="More actions"><i class="fas fa-ellipsis"></i></summary>
    <div class="dl-menu right">
        @foreach($actions as $key => [$label, $icon, $href, $kind])
            @if($key === 'delete')<hr>@endif
            @if($kind === 'link' || $kind === 'blank')
                <a href="{{ $href }}" @if($kind === 'blank') target="_blank" rel="noopener" @endif><i class="fas {{ $icon }}"></i> {{ $label }}</a>
            @else
                <button type="button" class="{{ $key === 'delete' ? 'danger' : '' }}" data-dl-action="{{ $kind }}" data-url="{{ $href }}" data-name="{{ $item->name }}" data-rename="{{ $rename }}"><i class="fas {{ $icon }}"></i> {{ $label }}</button>
            @endif
        @endforeach
    </div>
</details>
