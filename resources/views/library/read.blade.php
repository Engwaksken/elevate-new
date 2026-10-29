@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Reading: '.$resource->title.' - ElevateHer360')
@section('content')
<style>
.lr-bar{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:10px 12px;margin-bottom:12px;border:1px solid #e4e7ec;border-radius:12px;background:#fff;box-shadow:0 2px 8px rgba(16,24,40,.05)}
.lr-title{flex:1 1 220px;min-width:0}
.lr-title strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:#101828}
.lr-title small{color:#667085}
.lr-controls{display:flex;flex-wrap:wrap;align-items:center;gap:6px}
.lr-controls .btn{min-width:40px}
.lr-page{display:inline-flex;align-items:center;gap:6px;font-size:.85rem;color:#475467}
.lr-page input{width:64px;min-height:34px;padding:4px 8px;text-align:center}
.lr-progress{flex-basis:100%;height:4px;border-radius:999px;background:#f2e6e6;overflow:hidden}
.lr-progress span{display:block;height:100%;width:0;background:#800000;transition:width .3s}
.lr-stage{display:flex;justify-content:center;padding:16px;border-radius:12px;background:#e9e9ee;min-height:60vh;overflow:auto}
.lr-stage canvas{max-width:none;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.18)}
.lr-status{padding:40px;text-align:center;color:#475467}
.lr-done{margin-top:12px;padding:12px 14px;border-radius:10px;background:#ecfdf3;color:#05603a;font-weight:600}
.lr-embed{width:100%;height:78vh;border:1px solid #e4e7ec;border-radius:12px;background:#fff}
</style>

<div class="lr-bar">
    <a href="{{ route('library.show',$resource) }}" class="btn btn-outline btn-sm" aria-label="Back to book details"><i class="fas fa-arrow-left"></i></a>
    <div class="lr-title"><strong>{{ $resource->title }}</strong><small>{{ $resource->author ?: 'ElevateHer360 resource' }}</small></div>

    @if($resource->isPdf())
    <div class="lr-controls" data-reader-controls hidden>
        <button type="button" class="btn btn-outline btn-sm" data-reader-prev aria-label="Previous page"><i class="fas fa-chevron-left"></i></button>
        <label class="lr-page">Page <input type="number" min="1" value="{{ $progress->current_page ?: 1 }}" data-reader-page aria-label="Page number"> of <span data-reader-total>-</span></label>
        <button type="button" class="btn btn-outline btn-sm" data-reader-next aria-label="Next page"><i class="fas fa-chevron-right"></i></button>
        <button type="button" class="btn btn-outline btn-sm" data-reader-zoom="-1" aria-label="Zoom out"><i class="fas fa-magnifying-glass-minus"></i></button>
        <button type="button" class="btn btn-outline btn-sm" data-reader-zoom="1" aria-label="Zoom in"><i class="fas fa-magnifying-glass-plus"></i></button>
    </div>
    @endif

    <div class="lr-controls">
        <form method="POST" action="{{ route('library.bookmark',$resource) }}" data-reader-bookmark>@csrf
            <button class="btn btn-outline btn-sm" aria-pressed="{{ $bookmarked ? 'true' : 'false' }}"><i class="{{ $bookmarked ? 'fas' : 'far' }} fa-bookmark"></i> <span>{{ $bookmarked ? 'Bookmarked' : 'Bookmark' }}</span></button>
        </form>
        @if($canDownload)
            <a class="btn btn-outline btn-sm" href="{{ route('library.download',$resource) }}"><i class="fas fa-download"></i> Download</a>
        @else
            <a class="btn btn-outline btn-sm" href="{{ route('library.show',$resource) }}"><i class="fas fa-paper-plane"></i> Request download</a>
        @endif
    </div>

    @if($resource->isPdf())<div class="lr-progress" aria-hidden="true"><span data-reader-progress style="width:{{ (float) $progress->percent }}%"></span></div>@endif
</div>

@if($resource->isPdf())
    <div class="lr-stage" data-library-reader
         data-src="{{ route('library.read.file',$resource) }}"
         data-progress-url="{{ route('library.progress',$resource) }}"
         data-start-page="{{ $progress->completed_at ? 1 : ($progress->current_page ?: 1) }}"
         data-csrf="{{ csrf_token() }}">
        <div class="lr-status" data-reader-status><i class="fas fa-spinner fa-spin"></i> Opening your book...</div>
        <canvas data-reader-canvas hidden></canvas>
    </div>
    <div class="lr-done" data-reader-done hidden><i class="fas fa-circle-check"></i> You have reached the end of this book. Well done!</div>
    <p class="form-hint" style="margin-top:8px">Your place is saved as you turn pages, so you can come back and continue later. Use the arrow keys to turn pages.</p>
    @vite('resources/js/library-reader.js')
@else
    <iframe class="lr-embed" src="{{ route('library.download',[$resource,'preview'=>1,'embed'=>1]) }}" title="{{ $resource->title }}"></iframe>
    <p class="form-hint" style="margin-top:8px">This file type opens in the viewer above. Page tracking is available for PDF books.</p>
@endif

<script>
// Bookmark without leaving the page.
document.querySelector('[data-reader-bookmark]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const button = form.querySelector('button');
    button.disabled = true;
    try {
        const response = await fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(form) });
        const data = await response.json();
        button.setAttribute('aria-pressed', data.bookmarked ? 'true' : 'false');
        button.querySelector('i').className = (data.bookmarked ? 'fas' : 'far') + ' fa-bookmark';
        button.querySelector('span').textContent = data.bookmarked ? 'Bookmarked' : 'Bookmark';
    } finally {
        button.disabled = false;
    }
});
</script>
@endsection
