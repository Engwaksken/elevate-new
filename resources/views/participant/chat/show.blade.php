@extends('layouts.app')
@php
    use App\Support\ChatPresenter as Chat;
    $viewerId = (int) auth()->id();
    $title = Chat::title($conversation, $viewerId);
    $isGroup = $conversation->type === 'group';
    $isOwner = (int) $conversation->created_by === $viewerId;
    $others = $conversation->members->where('id', '!=', $viewerId);
    $subtitle = $isGroup
        ? $conversation->members->count().' members · '.$conversation->members->sortBy(fn ($m) => $m->id === $viewerId ? 'zzz' : $m->name)->map(fn ($m) => $m->id === $viewerId ? 'You' : $m->name)->join(', ')
        : 'Direct message';
    $grouping = 300; // seconds: consecutive messages from one sender within this window share a bubble group
@endphp
@section('title', $title.' | Participant Chat')
@section('content')
@include('participant.chat._styles')
<div class="ehc is-thread" data-type="{{ $conversation->type }}">
    @include('participant.chat._sidebar', ['activeId' => $conversation->id])

    <section class="ehc-main" aria-label="Conversation with {{ $title }}" style="position:relative">
        <header class="ehc-head">
            <a class="ehc-icon-btn ehc-back" href="{{ route('participant.chat.index') }}" aria-label="All conversations"><i class="fas fa-arrow-left" aria-hidden="true"></i></a>
            <span @class(['ehc-avatar', 'is-group' => $isGroup]) style="--ehc-av:{{ Chat::color(Chat::avatarSeed($conversation, $viewerId)) }}" aria-hidden="true">
                @if($isGroup)<i class="fas fa-user-group"></i>@else{{ Chat::initials($title) }}@endif
            </span>
            <div class="ehc-head-copy"><h2>{{ $title }}</h2><p>{{ $subtitle }}</p></div>
            <div class="ehc-head-actions">
                <button type="button" class="ehc-icon-btn" data-modal-open="chat-members" aria-label="{{ $isGroup ? 'Members' : 'Conversation details' }}" title="{{ $isGroup ? 'Members' : 'Details' }}"><i class="fas {{ $isGroup ? 'fa-users' : 'fa-circle-info' }}" aria-hidden="true"></i></button>
                @if($isOwner && $isGroup)<button type="button" class="ehc-icon-btn" data-modal-open="chat-edit-{{ $conversation->id }}" aria-label="Rename group" title="Rename group"><i class="fas fa-pen" aria-hidden="true"></i></button>@endif
                @if($isOwner)<button type="button" class="ehc-icon-btn" data-modal-open="chat-delete-{{ $conversation->id }}" aria-label="Delete conversation" title="Delete conversation"><i class="fas fa-trash" aria-hidden="true"></i></button>@endif
            </div>
        </header>

        <div class="ehc-thread" id="latest" role="log" aria-live="polite" aria-label="Messages"
             data-messages-url="{{ route('participant.chat.messages.index', $conversation) }}"
             data-last-id="{{ $conversation->messages->last()?->id ?? 0 }}"
             data-group-window="{{ $grouping }}">
            @php $previous = null; $messages = $conversation->messages->values(); @endphp
            @forelse($messages as $index => $message)
                @php
                    $next = $messages[$index + 1] ?? null;
                    $day = $message->created_at->toDateString();
                    $joinsPrevious = $previous && $previous->sender_id === $message->sender_id && $previous->created_at->toDateString() === $day && $message->created_at->getTimestamp() - $previous->created_at->getTimestamp() <= $grouping;
                    $joinsNext = $next && $next->sender_id === $message->sender_id && $next->created_at->toDateString() === $day && $next->created_at->getTimestamp() - $message->created_at->getTimestamp() <= $grouping;
                    $mine = (int) $message->sender_id === $viewerId;
                    $edited = $message->updated_at && $message->updated_at->gt($message->created_at);
                @endphp
                @if(! $previous || $previous->created_at->toDateString() !== $day)
                    <div class="ehc-day" data-day="{{ $day }}">{{ Chat::dayLabel($message->created_at) }}</div>
                @endif
                <div @class(['ehc-row', 'is-mine' => $mine, 'is-first' => ! $joinsPrevious, 'is-last' => ! $joinsNext])
                     data-id="{{ $message->id }}" data-sender="{{ $message->sender_id }}" data-day="{{ $day }}" data-ts="{{ $message->created_at->getTimestamp() }}"
                     @if($mine) data-update-url="{{ route('participant.chat.messages.update', [$conversation, $message]) }}" data-delete-url="{{ route('participant.chat.messages.destroy', [$conversation, $message]) }}" @endif>
                    <span class="ehc-avatar is-sm" style="--ehc-av:{{ Chat::color('u'.$message->sender_id) }}" aria-hidden="true">{{ Chat::initials($message->sender?->name) }}</span>
                    <div class="ehc-bubble">
                        <span class="ehc-sender">{{ $message->sender?->name }}</span>
                        <p class="ehc-text">{{ $message->body }}</p>
                        <time class="ehc-meta" datetime="{{ $message->created_at->toIso8601String() }}" title="{{ $message->created_at->format('d M Y · H:i') }}">@if($edited)<span data-edited>edited · </span>@endif{{ $message->created_at->format('H:i') }}</time>
                    </div>
                    @if($mine)
                        <div class="ehc-msg-tools">
                            <button type="button" class="ehc-icon-btn" data-ehc-edit aria-label="Edit message" title="Edit"><i class="fas fa-pen" aria-hidden="true"></i></button>
                            <button type="button" class="ehc-icon-btn" data-ehc-delete aria-label="Delete message" title="Delete"><i class="fas fa-trash" aria-hidden="true"></i></button>
                        </div>
                    @endif
                </div>
                @php $previous = $message; @endphp
            @empty
                <div class="ehc-thread-empty" data-ehc-empty><i class="fas fa-comment-dots" aria-hidden="true"></i><strong>No messages yet</strong><p>This is the beginning of your conversation{{ $isGroup ? ' in '.$title : ' with '.$title }}. Say hello!</p></div>
            @endforelse
        </div>
        <button type="button" class="ehc-icon-btn ehc-jump" data-ehc-jump aria-label="Jump to latest message"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>

        <form class="ehc-compose" method="POST" action="{{ route('participant.chat.messages.store', $conversation) }}" data-ehc-compose>
            @csrf
            <div style="flex:1">
                <div class="ehc-compose-field">
                    <label for="chat-message-body" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0 0 0 0)">Message</label>
                    <textarea id="chat-message-body" name="body" rows="1" maxlength="5000" required placeholder="Write a message…" autocomplete="off"></textarea>
                </div>
                <small>Enter to send · Shift + Enter for a new line</small>
            </div>
            <button class="ehc-icon-btn is-brand ehc-send" type="submit" aria-label="Send message" title="Send"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
        </form>
    </section>
</div>

{{-- Members / details --}}
<div class="eh-modal" id="chat-members" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-members-title" @if($errors->has('participant_ids')) data-modal-autoopen @endif>
    <div class="eh-modal-dialog">
        <div class="eh-modal-header"><div><h2 id="chat-members-title">{{ $isGroup ? 'Group members' : 'Conversation details' }}</h2><p>{{ $isGroup ? $title : 'Direct message' }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <div class="eh-modal-body">
            <ul class="ehc-members">
                @foreach($conversation->members->sortBy('name') as $member)
                    <li><span class="ehc-avatar is-sm" style="--ehc-av:{{ Chat::color('u'.$member->id) }}" aria-hidden="true">{{ Chat::initials($member->name) }}</span><span>{{ $member->name }} @if($member->id === $viewerId)<small>(you)</small>@endif @if((int) $conversation->created_by === $member->id)<small>· {{ $isGroup ? 'created the group' : 'started the chat' }}</small>@endif</span></li>
                @endforeach
            </ul>
            @if($isGroup && $participants->isNotEmpty())
                <form method="POST" action="{{ route('participant.chat.invite', $conversation) }}" id="chat-invite-form">@csrf
                    @error('participant_ids')<div class="alert alert-danger" role="alert">{{ $message }}</div>@enderror
                    <label class="ehc-label" for="chat-invite-members">Add people</label>
                    <select class="ehc-field" id="chat-invite-members" name="participant_ids[]" multiple required>@foreach($participants as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select>
                    <span class="ehc-hint">Hold Ctrl (Cmd on Mac) to pick several people.</span>
                </form>
            @endif
        </div>
        <div class="eh-modal-footer">
            <button type="button" class="btn btn-outline" data-modal-close>Close</button>
            @if($isGroup && $participants->isNotEmpty())<button class="btn btn-primary" type="submit" form="chat-invite-form"><i class="fas fa-user-plus" aria-hidden="true"></i> Add to group</button>@endif
        </div>
    </div>
</div>

{{-- Shared edit / delete message modals; JS points their forms at the chosen message. --}}
<div class="eh-modal" id="chat-msg-edit" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-msg-edit-title"><div class="eh-modal-dialog"><form method="POST" data-ehc-edit-form>@csrf @method('PUT')
    <div class="eh-modal-header"><div><h2 id="chat-msg-edit-title">Edit message</h2></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <div class="eh-modal-body"><label class="ehc-label" for="chat-msg-edit-body">Message</label><textarea class="ehc-field" id="chat-msg-edit-body" name="body" required maxlength="5000" rows="4"></textarea></div>
    <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div>
</form></div></div>
<div class="eh-modal" id="chat-msg-delete" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-msg-delete-title"><div class="eh-modal-dialog"><form method="POST" data-ehc-delete-form>@csrf @method('DELETE')
    <div class="eh-modal-header"><div><h2 id="chat-msg-delete-title">Delete message?</h2><p>It will be removed for everyone in this conversation.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep message</button><button class="btn btn-danger" type="submit">Delete message</button></div>
</form></div></div>

@include('participant.chat._modals')

<script>
document.addEventListener('DOMContentLoaded', () => {
    const thread = document.querySelector('.ehc-thread');
    const form = document.querySelector('[data-ehc-compose]');
    if (!thread || !form) return;
    const input = form.querySelector('textarea');
    const sendBtn = form.querySelector('[type=submit]');
    const jump = document.querySelector('[data-ehc-jump]');
    const csrf = form.querySelector('input[name=_token]').value;
    const windowSecs = Number(thread.dataset.groupWindow || 300);
    let lastId = Number(thread.dataset.lastId || 0);

    const headers = {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'};
    const nearBottom = () => thread.scrollHeight - thread.scrollTop - thread.clientHeight < 120;
    const toBottom = (smooth) => thread.scrollTo({top: thread.scrollHeight, behavior: smooth ? 'smooth' : 'instant'});
    toBottom(false);

    const openModal = (id) => {
        const modal = document.getElementById(id);
        modal.classList.add('is-open'); modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        modal.querySelector('textarea,button[type=submit]')?.focus();
    };
    const closeModal = (modal) => {
        modal.classList.remove('is-open'); modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    };

    // ---- Rendering --------------------------------------------------------
    const initials = (name) => (name || '?').trim().split(/\s+/).slice(0, 2).map(p => p[0].toUpperCase()).join('') || '?';
    const lastRow = () => [...thread.querySelectorAll('.ehc-row')].pop();

    const buildRow = (m) => {
        const row = document.createElement('div');
        row.className = 'ehc-row is-first is-last' + (m.mine ? ' is-mine' : '');
        Object.assign(row.dataset, {id: m.id, sender: m.sender_id, day: m.day, ts: m.ts});
        if (m.update_url) { row.dataset.updateUrl = m.update_url; row.dataset.deleteUrl = m.delete_url; }

        const av = document.createElement('span');
        av.className = 'ehc-avatar is-sm'; av.setAttribute('aria-hidden', 'true');
        av.style.setProperty('--ehc-av', m.color || '#800000'); av.textContent = initials(m.sender);

        const bubble = document.createElement('div'); bubble.className = 'ehc-bubble';
        const sender = document.createElement('span'); sender.className = 'ehc-sender'; sender.textContent = m.sender || '';
        const text = document.createElement('p'); text.className = 'ehc-text'; text.textContent = m.body;
        const time = document.createElement('time'); time.className = 'ehc-meta'; time.title = m.created_at || ''; time.textContent = m.time || '';
        bubble.append(sender, text, time);
        row.append(av, bubble);

        if (m.mine) {
            const tools = document.createElement('div'); tools.className = 'ehc-msg-tools';
            tools.innerHTML = '<button type="button" class="ehc-icon-btn" data-ehc-edit aria-label="Edit message" title="Edit"><i class="fas fa-pen" aria-hidden="true"></i></button>'
                + '<button type="button" class="ehc-icon-btn" data-ehc-delete aria-label="Delete message" title="Delete"><i class="fas fa-trash" aria-hidden="true"></i></button>';
            row.append(tools);
        }
        return row;
    };

    const append = (m) => {
        if (thread.querySelector(`.ehc-row[data-id="${m.id}"]`)) return;
        thread.querySelector('[data-ehc-empty]')?.remove();
        const stick = nearBottom() || m.mine;
        const prev = lastRow();
        if (!prev || prev.dataset.day !== m.day) {
            const day = document.createElement('div');
            day.className = 'ehc-day'; day.dataset.day = m.day; day.textContent = m.day_label || m.day;
            thread.append(day);
        }
        const row = buildRow(m);
        if (prev && prev.dataset.day === m.day && prev.dataset.sender === String(m.sender_id) && Number(m.ts) - Number(prev.dataset.ts) <= windowSecs) {
            prev.classList.remove('is-last');
            row.classList.remove('is-first');
        }
        thread.append(row);
        lastId = Math.max(lastId, Number(m.id));
        if (stick) toBottom(true); else jump.classList.add('is-visible');
    };

    // ---- Sending ----------------------------------------------------------
    const autosize = () => { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 160) + 'px'; };
    const syncButton = () => { sendBtn.disabled = input.value.trim() === ''; };
    input.addEventListener('input', () => { autosize(); syncButton(); });
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) { e.preventDefault(); form.requestSubmit(); }
    });
    syncButton();

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = input.value.trim();
        if (!body) return;
        sendBtn.disabled = true;
        try {
            const res = await fetch(form.action, {method: 'POST', headers, body: new FormData(form)});
            if (!res.ok) throw new Error(res.status);
            const data = await res.json();
            input.value = ''; autosize();
            append(data.message);
        } catch (err) {
            form.submit(); // fall back to a normal post so the message is not lost
            return;
        }
        syncButton();
        input.focus();
    });

    // ---- Edit / delete ----------------------------------------------------
    const editModal = document.getElementById('chat-msg-edit');
    const deleteModal = document.getElementById('chat-msg-delete');
    const editForm = editModal.querySelector('form');
    const deleteForm = deleteModal.querySelector('form');
    let target = null;

    thread.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-ehc-edit],[data-ehc-delete]');
        if (!btn) {
            // Touch screens have no hover: tapping your own bubble reveals its edit/delete buttons.
            const own = e.target.closest('.ehc-row.is-mine');
            thread.querySelectorAll('.ehc-row.is-open').forEach(r => { if (r !== own) r.classList.remove('is-open'); });
            own?.classList.toggle('is-open');
            return;
        }
        target = btn.closest('.ehc-row');
        if (btn.hasAttribute('data-ehc-edit')) {
            editForm.action = target.dataset.updateUrl;
            editForm.querySelector('textarea').value = target.querySelector('.ehc-text').textContent;
            openModal('chat-msg-edit');
        } else {
            deleteForm.action = target.dataset.deleteUrl;
            openModal('chat-msg-delete');
        }
    });

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await fetch(editForm.action, {method: 'POST', headers, body: new FormData(editForm)});
            if (!res.ok) throw new Error(res.status);
            const {message} = await res.json();
            target.querySelector('.ehc-text').textContent = message.body;
            const meta = target.querySelector('.ehc-meta');
            if (!meta.querySelector('[data-edited]')) meta.insertAdjacentHTML('afterbegin', '<span data-edited>edited · </span>');
            closeModal(editModal);
        } catch (err) { editForm.submit(); }
    });

    deleteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const res = await fetch(deleteForm.action, {method: 'POST', headers, body: new FormData(deleteForm)});
            if (!res.ok) throw new Error(res.status);
            const prev = target.previousElementSibling, next = target.nextElementSibling;
            // Re-stitch bubble grouping around the removed message.
            if (target.classList.contains('is-first') && next?.classList.contains('ehc-row') && !next.classList.contains('is-first')) next.classList.add('is-first');
            if (target.classList.contains('is-last') && prev?.classList.contains('ehc-row') && !prev.classList.contains('is-last')) prev.classList.add('is-last');
            target.remove();
            // Drop a day divider left with no messages under it.
            thread.querySelectorAll('.ehc-day').forEach(day => { const n = day.nextElementSibling; if (!n || n.classList.contains('ehc-day')) day.remove(); });
            closeModal(deleteModal);
        } catch (err) { deleteForm.submit(); }
    });

    // ---- Live updates -----------------------------------------------------
    thread.addEventListener('scroll', () => { if (nearBottom()) jump.classList.remove('is-visible'); });
    jump.addEventListener('click', () => { toBottom(true); jump.classList.remove('is-visible'); });

    const poll = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(`${thread.dataset.messagesUrl}?after=${lastId}`, {headers});
            if (!res.ok) return;
            const data = await res.json();
            data.messages.forEach(append);
        } catch (err) { /* a brief network blip should not interrupt the conversation */ }
    };
    setInterval(poll, 4000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); });
});
</script>
@endsection
