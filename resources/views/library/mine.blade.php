@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','My Library - ElevateHer360')
@section('content')
<style>
.ml-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:0 0 20px}
.ml-stat{display:flex;align-items:center;gap:12px;padding:14px 16px;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;background:#fff}
.ml-stat i{width:38px;height:38px;display:grid;place-items:center;border-radius:10px;background:#fff7da;color:#800000}
.ml-stat small{display:block;color:#667085;font-weight:700}.ml-stat strong{font-size:1.3rem;color:#101828}
.ml-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px}
.ml-card{display:flex;gap:12px;padding:12px;border:1px solid #e4e7ec;border-radius:12px;background:#fff}
.ml-cover{flex:0 0 64px;height:88px;border-radius:6px;overflow:hidden;background:#f6f1ea;display:grid;place-items:center;color:#800000}
.ml-cover img{width:100%;height:100%;object-fit:cover}
.ml-card-body{min-width:0;display:flex;flex-direction:column;gap:4px;flex:1}
.ml-card-body strong{color:#101828;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.ml-card-body small{color:#667085}
.ml-bar{height:6px;border-radius:999px;background:#f2e6e6;overflow:hidden}.ml-bar span{display:block;height:100%;background:#800000}
.ml-card-body .btn{align-self:flex-start;margin-top:auto}
@media(max-width:900px){.ml-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="page-header">
    <div><span class="eh-kicker">Digital Library</span><h1>My Library</h1><p>Pick up where you left off, find your bookmarks and see your reading history.</p></div>
    <div class="page-actions"><a href="{{ route('library.index') }}" class="btn btn-primary"><i class="fas fa-book-open"></i> Browse the library</a></div>
</div>

<div class="ml-stats">
    @foreach([['reading','Reading now','fa-book-open-reader'],['finished','Finished','fa-circle-check'],['bookmarks','Bookmarks','fa-bookmark'],['requests','Download requests waiting','fa-hourglass-half']] as [$key,$label,$icon])
        <div class="ml-stat"><i class="fas {{ $icon }}"></i><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
    @endforeach
</div>

@php($progressLabel = fn ($p) => $p->completed_at ? 'Finished' : ($p->current_page ? 'Page '.$p->current_page.' of '.$p->total_pages : 'Started'))
@php($progressWidth = fn ($p) => $p->completed_at ? 100 : (float) $p->percent)

<div class="eh-tabs" data-eh-tabs>
    <div class="eh-tab-nav">
        <button class="eh-tab-button active" data-eh-tab="continue"><i class="fas fa-book-open-reader"></i> Continue reading</button>
        <button class="eh-tab-button" data-eh-tab="bookmarks"><i class="fas fa-bookmark"></i> Bookmarks</button>
        <button class="eh-tab-button" data-eh-tab="history"><i class="fas fa-clock-rotate-left"></i> History</button>
        <button class="eh-tab-button" data-eh-tab="requests"><i class="fas fa-download"></i> Download requests</button>
    </div>
    <div class="eh-tab-content">
        <section class="eh-tab-pane active" data-eh-pane="continue">
            <div class="eh-tab-section">
                @if($continue->isEmpty())
                    <div class="eh-empty"><i class="fas fa-book-open"></i><h3>Nothing in progress</h3><p>Open any book with "Read online" and it will appear here so you can continue later.</p><a class="btn btn-primary" href="{{ route('library.index') }}">Browse the library</a></div>
                @else
                    <div class="ml-grid">
                        @foreach($continue as $p)
                            <article class="ml-card">
                                <div class="ml-cover">@if($p->resource->cover_image_path)<img src="{{ route('library.cover',$p->resource) }}" alt="">@else<i class="fas fa-book"></i>@endif</div>
                                <div class="ml-card-body">
                                    <strong title="{{ $p->resource->title }}">{{ $p->resource->title }}</strong>
                                    <small>{{ $p->resource->author ?: 'ElevateHer360 resource' }}</small>
                                    <div class="ml-bar" title="{{ $progressLabel($p) }}"><span style="width:{{ $progressWidth($p) }}%"></span></div>
                                    <small>{{ $progressLabel($p) }} · {{ $p->last_read_at?->diffForHumans() }}</small>
                                    <a class="btn btn-primary btn-sm" href="{{ route('library.read',$p->resource) }}"><i class="fas fa-play"></i> Continue</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="bookmarks">
            <div class="eh-tab-section"><div class="admin-panel">
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Book</th><th>Category</th><th>Bookmarked</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @forelse($bookmarks as $book)
                        <tr>
                            <td><a href="{{ route('library.show',$book) }}"><strong>{{ $book->title }}</strong></a><small class="admin-cell-hint">{{ $book->author ?: 'ElevateHer360 resource' }}</small></td>
                            <td>{{ $book->category?->name ?? '-' }}</td>
                            <td>{{ \Illuminate\Support\Carbon::parse($book->bookmarked_at)->format('d M Y') }}</td>
                            <td class="table-actions"><div class="action-group">
                                @if($book->file_path)<a class="btn btn-primary btn-sm" href="{{ route('library.read',$book) }}"><i class="fas fa-book-open-reader"></i> Read</a>@endif
                                <form method="POST" action="{{ route('library.bookmark',$book) }}">@csrf<button class="btn btn-outline btn-sm"><i class="fas fa-bookmark"></i> Remove</button></form>
                            </div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="admin-empty">No bookmarks yet. Use "Bookmark" on any book to keep it here.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $bookmarks->links() }}</div>
            </div></div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="history">
            <div class="eh-tab-section"><div class="admin-panel">
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Book</th><th>Progress</th><th>First opened</th><th>Last read</th><th>Times opened</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @forelse($history as $p)
                        <tr>
                            <td><a href="{{ route('library.show',$p->resource) }}"><strong>{{ $p->resource->title }}</strong></a><small class="admin-cell-hint">{{ $p->resource->author ?: 'ElevateHer360 resource' }}</small></td>
                            <td style="min-width:150px"><div class="ml-bar"><span style="width:{{ $progressWidth($p) }}%"></span></div><small class="admin-cell-hint">{{ $progressLabel($p) }}</small></td>
                            <td>{{ $p->first_opened_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $p->last_read_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ number_format($p->opened_count) }}</td>
                            <td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('library.read',$p->resource) }}">{{ $p->completed_at ? 'Read again' : 'Continue' }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="admin-empty">Books you open will be listed here.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $history->links() }}</div>
            </div></div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="requests">
            <div class="eh-tab-section"><div class="admin-panel">
                <div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Book</th><th>Requested</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td><strong>{{ $item->resource?->title ?? 'Removed book' }}</strong>@if($item->reason)<small class="admin-cell-hint">{{ $item->reason }}</small>@endif</td>
                            <td>{{ $item->created_at->format('d M Y') }}</td>
                            <td>
                                @if($item->isUsable())
                                    <span class="status-chip approved">Approved</span><small class="admin-cell-hint">Download until {{ $item->expires_at->format('d M Y') }}</small>
                                @elseif($item->status === 'approved')
                                    <span class="status-chip inactive">Expired</span>
                                @elseif($item->status === 'declined')
                                    <span class="status-chip declined">Not approved</span>@if($item->decision_note)<small class="admin-cell-hint">{{ $item->decision_note }}</small>@endif
                                @else
                                    <span class="status-chip pending">Waiting for reply</span>
                                @endif
                            </td>
                            <td class="table-actions">
                                @if($item->isUsable() && $item->resource && ! $item->resource->trashed())
                                    <a class="btn btn-primary btn-sm" href="{{ route('library.download',$item->resource) }}"><i class="fas fa-download"></i> Download</a>
                                @elseif($item->resource && ! $item->resource->trashed())
                                    <a class="btn btn-outline btn-sm" href="{{ route('library.show',$item->resource) }}">View book</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="admin-empty">You have not asked to download any books.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="admin-pagination">{{ $requests->links() }}</div>
            </div></div>
        </section>
    </div>
</div>
@endsection
