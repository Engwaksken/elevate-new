@once
<style>
    .lf-list { display:flex; flex-direction:column; gap:8px; margin:8px 0; padding:0; list-style:none; }
    .lf-item { display:flex; flex-wrap:wrap; align-items:center; gap:8px 12px; padding:8px 10px; border:1px solid var(--border-color, #e4e7ec); border-radius:10px; }
    .lf-main { display:flex; align-items:center; gap:10px; flex:1 1 220px; min-width:0; }
    .lf-main i { font-size:1.15rem; opacity:.8; }
    .lf-name { display:block; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .lf-meta { display:block; font-size:.8rem; opacity:.75; }
    .lf-badge { display:inline-block; margin-left:6px; padding:1px 8px; border-radius:999px; font-size:.72rem; font-weight:600; background:rgba(122,31,92,.1); }
    .lf-actions { display:flex; flex-wrap:wrap; gap:6px; }
    .lf-actions form { display:inline; margin:0; }
    .lf-list.compact .lf-item { padding:6px 8px; }
    .lf-title { margin:12px 0 4px; font-size:.9rem; font-weight:600; }
</style>
@endonce

@if($title !== '' && count($items))
    <p class="lf-title">{{ $title }}</p>
@endif

@if(count($items))
    <ul {{ $attributes->merge(['class' => 'lf-list'.($compact ? ' compact' : '')]) }}>
        @foreach($items as $item)
            <li class="lf-item" data-learning-file="{{ $item['id'] }}">
                <div class="lf-main">
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                    <div style="min-width:0">
                        <span class="lf-name" title="{{ $item['name'] }}">{{ $item['name'] }}</span>
                        <span class="lf-meta">
                            {{ strtoupper($item['extension'] ?: 'file') }}@if($item['size_label']) · {{ $item['size_label'] }}@endif
                            @unless($item['can_download'])<span class="lf-badge" title="This file can be viewed in the platform but not downloaded">View only</span>@endunless
                        </span>
                    </div>
                </div>
                <div class="lf-actions">
                    <a class="btn btn-outline btn-sm" href="{{ $item['view_url'] }}" target="_blank" rel="noopener"
                       data-file-preview data-file-preview-title="{{ $item['name'] }}"
                       @if($item['can_download']) data-file-download-url="{{ $item['download_url'] }}" @else data-file-preview-no-download @endif>
                        <i class="fas fa-eye"></i> View
                    </a>
                    @if($item['can_download'])
                        <a class="btn btn-outline btn-sm" href="{{ $item['download_url'] }}" data-learning-file-download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    @endif
                    @if($item['delete_url'])
                        <form method="POST" action="{{ $item['delete_url'] }}" onsubmit="return confirm('Remove this file?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-outline btn-sm" type="submit" title="Remove {{ $item['name'] }}"><i class="fas fa-trash"></i> Remove</button>
                        </form>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
@elseif($empty !== '')
    <p class="lf-meta">{{ $empty }}</p>
@endif
