@extends(auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title',$resource->title.' - ElevateHer360')
@section('content')

<div class="page-header">
<div>
<span class="eh-kicker">Digital Library</span>
<h1>{{ $resource->title }}</h1>
<p>{{ $resource->author ?: 'ElevateHer360 resource' }}</p>
</div>
<div class="page-actions">
<a href="{{ route('library.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Library</a>
@auth<a href="{{ route('library.mine') }}" class="btn btn-outline"><i class="fas fa-book-bookmark"></i> My Library</a>@endauth
</div>
</div>

<div class="library-detail-grid">
<section class="library-detail-main">
@if($resource->cover_image_path)
<div class="library-detail-cover">
<img src="{{ route('library.cover',$resource) }}" alt="{{ $resource->title }}">
</div>
@endif

<div class="eh-tab-section">
@if($resource->description)
<div class="library-description">{!! nl2br(e($resource->description)) !!}</div>
@endif

@if(is_array($resource->tags) && count($resource->tags))
<div class="library-tags">
@foreach($resource->tags as $tag)<span>{{ $tag }}</span>@endforeach
</div>
@endif
</div>
</section>

<aside class="library-detail-side">
@if($progress)
<div class="library-info-card" style="margin-bottom:12px">
    <div><span>Your reading</span><strong>{{ $progress->completed_at ? 'Finished' : ($progress->current_page ? 'Page '.$progress->current_page.' of '.$progress->total_pages : 'Started') }}</strong></div>
    <div style="display:block"><div style="height:8px;border-radius:999px;background:#f2e6e6;overflow:hidden"><span style="display:block;height:100%;width:{{ $progress->completed_at ? 100 : (float) $progress->percent }}%;background:#800000"></span></div></div>
    <div><span>Last read</span><strong>{{ $progress->last_read_at?->diffForHumans() ?? '-' }}</strong></div>
</div>
@endif

<div class="library-info-card">
<div><span>Category</span><strong>{{ $resource->category?->name ?: '-' }}</strong></div>
<div><span>Language</span><strong>{{ $resource->language }}</strong></div>
<div><span>Published</span><strong>{{ optional($resource->publication_date)->format('d M Y') ?: '-' }}</strong></div>
<div><span>Views</span><strong>{{ number_format($resource->views_count) }}</strong></div>
<div><span>Downloads</span><strong>{{ number_format($resource->downloads_count) }}</strong></div>
@if($resource->file_path)<div><span>Downloading</span><strong>{{ $resource->requiresDownloadApproval() ? 'On request' : 'Free' }}</strong></div>@endif
</div>

<div class="library-action-stack">
@if($resource->file_path)
    @auth
        <a class="btn btn-primary" href="{{ route('library.read',$resource) }}">
            <i class="fas fa-book-open-reader"></i>
            @if($progress && $progress->current_page && ! $progress->completed_at) Continue reading (page {{ $progress->current_page }}) @elseif($progress && $progress->completed_at) Read again @else Read online @endif
        </a>
    @else
        <a class="btn btn-primary" href="{{ route('library.download',[$resource,'preview'=>1]) }}" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $resource->title }}">
            <i class="fas fa-eye"></i> Read online
        </a>
    @endauth

    @if($canDownload)
        <a class="btn btn-outline" href="{{ route('library.download',$resource) }}"><i class="fas fa-download"></i> Download</a>
    @elseif(auth()->check())
        @if($latestRequest && $latestRequest->status === 'pending')
            <span class="btn btn-outline" aria-disabled="true" style="cursor:default"><i class="fas fa-hourglass-half"></i> Download requested</span>
            <small class="form-hint">Sent {{ $latestRequest->created_at->diffForHumans() }}. You will get a notification when the library team replies.</small>
        @else
            @if($latestRequest && $latestRequest->status === 'declined')
                <small class="form-hint" style="color:#b42318">Your last request was not approved{{ $latestRequest->decision_note ? ': '.$latestRequest->decision_note : '.' }}</small>
            @elseif($latestRequest && $latestRequest->status === 'approved')
                <small class="form-hint">Your download approval has expired. You can ask again.</small>
            @endif
            <button type="button" class="btn btn-outline" data-modal-open="request-download"><i class="fas fa-paper-plane"></i> Request download</button>
            <small class="form-hint">You can read this book online any time. To save a copy, ask the library team.</small>
        @endif
    @endif
@endif

@if($resource->external_url)
<a class="btn btn-outline" href="{{ $resource->external_url }}" target="_blank" rel="noopener noreferrer">
<i class="fas fa-arrow-up-right-from-square"></i> Open External Resource
</a>
@endif

@auth
<form method="POST" action="{{ route('library.bookmark',$resource) }}">
@csrf
@if($bookmarked)
<button class="btn btn-outline" type="submit" aria-pressed="true"><i class="fas fa-bookmark" style="color:#800000"></i> Bookmarked (remove)</button>
@else
<button class="btn btn-outline" type="submit" aria-pressed="false"><i class="far fa-bookmark"></i> Bookmark</button>
@endif
</form>
@endauth
</div>
</aside>
</div>

@auth
@if($resource->file_path && ! $canDownload)
<div class="eh-modal" id="request-download" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="request-download-title">
    <div class="eh-modal-dialog eh-modal-sm"><form method="POST" action="{{ route('library.request-download',$resource) }}">@csrf
        <div class="eh-modal-header"><div><h2 id="request-download-title">Request download</h2><p>{{ $resource->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body"><div class="modal-grid"><div class="form-group full">
            <label for="request-reason">Why do you need a copy? (optional)</label>
            <textarea id="request-reason" name="reason" maxlength="1000" rows="3" placeholder="For example: I want to read it offline because my internet is limited."></textarea>
        </div></div>
        <small class="form-hint">If approved, you can download the book for {{ \App\Models\LibraryDownloadRequest::APPROVAL_DAYS }} days.</small></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send request</button></div>
    </form></div>
</div>
@endif
@endauth
@endsection
