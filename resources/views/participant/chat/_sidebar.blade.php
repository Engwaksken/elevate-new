@php
    use App\Support\ChatPresenter as Chat;
    $viewerId = (int) auth()->id();
@endphp
<aside class="ehc-side" aria-label="Conversations">
    <div class="ehc-side-head">
        <h1>Chats</h1>
        <button type="button" class="ehc-icon-btn is-brand" data-modal-open="chat-create" aria-label="New conversation" title="New conversation"><i class="fas fa-pen-to-square" aria-hidden="true"></i></button>
    </div>
    <div class="ehc-search">
        <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
        <input type="search" placeholder="Search conversations" aria-label="Search conversations" data-ehc-search>
    </div>
    @if(count($conversations))
        <ul class="ehc-list" data-ehc-list>
            @foreach($conversations as $item)
                @php
                    $itemTitle = Chat::title($item, $viewerId);
                    $isGroup = $item->type === 'group';
                    $isActive = isset($activeId) && (int) $activeId === (int) $item->id;
                    $mine = (int) $item->created_by === $viewerId;
                @endphp
                <li data-ehc-filter="{{ \Illuminate\Support\Str::lower($itemTitle.' '.$item->members->pluck('name')->join(' ')) }}">
                    <a @class(['ehc-item', 'is-active' => $isActive]) href="{{ route('participant.chat.show', $item) }}" @if($isActive) aria-current="page" @endif>
                        <span @class(['ehc-avatar', 'is-group' => $isGroup]) style="--ehc-av:{{ Chat::color(Chat::avatarSeed($item, $viewerId)) }}" aria-hidden="true">
                            @if($isGroup)<i class="fas fa-user-group"></i>@else{{ Chat::initials($itemTitle) }}@endif
                        </span>
                        <span class="ehc-item-copy">
                            <span class="ehc-item-top"><strong>{{ $itemTitle }}</strong><time datetime="{{ ($item->latestMessage?->created_at ?? $item->updated_at)?->toIso8601String() }}">{{ Chat::listTime($item->latestMessage?->created_at ?? $item->updated_at) }}</time></span>
                            <p>{{ Chat::preview($item, $viewerId) }}</p>
                        </span>
                    </a>
                    @if($mine)
                        <span class="ehc-item-tools" style="position:absolute;right:14px;top:50%;transform:translateY(-50%)">
                            @if($isGroup)<button type="button" class="ehc-icon-btn" data-modal-open="chat-edit-{{ $item->id }}" aria-label="Rename {{ $itemTitle }}" title="Rename group"><i class="fas fa-pen" aria-hidden="true"></i></button>@endif
                            <button type="button" class="ehc-icon-btn" data-modal-open="chat-delete-{{ $item->id }}" aria-label="Delete conversation with {{ $itemTitle }}" title="Delete conversation"><i class="fas fa-trash" aria-hidden="true"></i></button>
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="ehc-side-empty" data-ehc-noresults hidden>No conversations match your search.</p>
        @if($conversations instanceof \Illuminate\Contracts\Pagination\Paginator && $conversations->hasPages())
            <div class="ehc-side-foot admin-pagination">{{ $conversations->onEachSide(0)->links() }}</div>
        @endif
    @else
        <div class="ehc-side-empty">
            <p style="margin:0 0 12px">No conversations yet.</p>
            <button type="button" class="btn btn-primary btn-sm" data-modal-open="chat-create"><i class="fas fa-plus" aria-hidden="true"></i> Start one</button>
        </div>
    @endif
</aside>
