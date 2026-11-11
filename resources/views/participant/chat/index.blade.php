@extends('layouts.app')
@section('title', 'Participant Chat | ElevateHer360')
@section('content')
<style>
.chat-wrap{max-width:1100px;margin:auto}.chat-grid{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(280px,.85fr);gap:20px}.chat-card{background:#fff;border:1px solid #e7e4e4;border-radius:16px;padding:20px;box-shadow:0 4px 14px #1018280a}.chat-card h2{margin:0 0 6px}.chat-list{display:grid;gap:10px;margin-top:16px}.chat-row{display:flex;align-items:center;gap:12px;padding:13px;border:1px solid #eee;border-radius:12px;color:inherit;text-decoration:none}.chat-row:hover{border-color:#800000;background:#fffafa}.chat-avatar{width:42px;height:42px;flex:none;display:grid;place-items:center;border-radius:50%;background:#f8eaea;color:#800000;font-weight:800}.chat-copy{min-width:0;flex:1}.chat-copy strong,.chat-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.chat-copy small,.chat-muted{color:#667085}.chat-form{display:grid;gap:12px;margin-top:16px}.chat-form label{font-weight:700}.chat-form input,.chat-form select{width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:9px;font:inherit}.chat-form select[multiple]{min-height:130px}.chat-empty{padding:24px;text-align:center;color:#667085;border:1px dashed #d0d5dd;border-radius:12px}@media(max-width:760px){.chat-grid{grid-template-columns:1fr}}
</style>
<div class="chat-wrap">
    <div class="page-header"><div><span class="eh-kicker">Community</span><h1>Participant Chat</h1><p>Message another participant or start a group conversation.</p></div></div>
    <div class="chat-grid">
        <section class="chat-card"><h2>Your conversations</h2><p class="chat-muted">Only members of a conversation can read its messages.</p>
            <div class="chat-list">
                @forelse($conversations as $conversation)
                    @php
                        $others = $conversation->members->where('id', '!=', auth()->id());
                        $title = $conversation->type === 'group' ? $conversation->title : ($others->first()?->name ?? 'Direct conversation');
                    @endphp
                    <a class="chat-row" href="{{ route('participant.chat.show', $conversation) }}">
                        <span class="chat-avatar" aria-hidden="true"><i class="fas {{ $conversation->type === 'group' ? 'fa-user-group' : 'fa-user' }}"></i></span>
                        <span class="chat-copy"><strong>{{ $title }}</strong><small>{{ $conversation->latestMessage?->sender?->name ? $conversation->latestMessage->sender->name.': ' : '' }}{{ $conversation->latestMessage?->body ?? 'No messages yet' }}</small></span>
                        <small class="chat-muted">{{ $conversation->messages_count }} {{ \Illuminate\Support\Str::plural('message', $conversation->messages_count) }}</small>
                    </a>
                @empty
                    <div class="chat-empty">No conversations yet. Start a direct chat or create a group.</div>
                @endforelse
            </div>
            @if($conversations->hasPages())<div class="admin-pagination">{{ $conversations->links() }}</div>@endif
        </section>
        <aside class="chat-card">
            <h2>Start a conversation</h2>
            <form class="chat-form" method="POST" action="{{ route('participant.chat.store') }}">
                @csrf<input type="hidden" name="type" value="direct">
                <label for="chat-direct-person">Direct message</label>
                <select id="chat-direct-person" name="participant_id" required><option value="">Choose a participant</option>@foreach($participants as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select>
                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Open direct chat</button>
            </form>
            <hr style="border:0;border-top:1px solid #eaecf0;margin:20px 0">
            <form class="chat-form" method="POST" action="{{ route('participant.chat.store') }}">
                @csrf<input type="hidden" name="type" value="group">
                <label for="chat-group-title">Group name</label><input id="chat-group-title" name="title" maxlength="120" required placeholder="e.g. Digital Skills Cohort">
                <label for="chat-group-members">Invite participants</label>
                <select id="chat-group-members" name="participant_ids[]" multiple aria-describedby="chat-group-help">@foreach($participants as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select>
                <small id="chat-group-help" class="chat-muted">Select one or more participants. You will be added automatically.</small>
                <button class="btn btn-outline" type="submit"><i class="fas fa-users"></i> Create group</button>
            </form>
        </aside>
    </div>
</div>
@endsection
