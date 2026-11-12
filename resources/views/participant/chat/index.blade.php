@extends('layouts.app')
@section('title', 'Participant Chat | ElevateHer360')
@section('content')
<style>
.chat-wrap{max-width:1100px;margin:auto}.chat-card{background:#fff;border:1px solid #e7e4e4;border-radius:16px;padding:20px;box-shadow:0 4px 14px #1018280a}.chat-card h2{margin:0 0 6px}.chat-list{display:grid;gap:10px;margin-top:16px}.chat-row{display:flex;align-items:center;gap:12px;padding:13px;border:1px solid #eee;border-radius:12px;color:inherit;text-decoration:none}.chat-row:hover{border-color:#800000;background:#fffafa}.chat-avatar{width:42px;height:42px;flex:none;display:grid;place-items:center;border-radius:50%;background:#f8eaea;color:#800000;font-weight:800}.chat-copy{min-width:0;flex:1}.chat-copy strong,.chat-copy small{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.chat-copy small,.chat-muted{color:#667085}.chat-actions{display:flex;gap:6px;flex-wrap:wrap}.chat-form{display:grid;gap:12px}.chat-form label{font-weight:700}.chat-form input,.chat-form select{width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:9px;font:inherit}.chat-form select[multiple]{min-height:140px}.chat-empty{padding:24px;text-align:center;color:#667085;border:1px dashed #d0d5dd;border-radius:12px}@media(max-width:760px){.chat-row{align-items:flex-start;flex-wrap:wrap}.chat-actions{width:100%;padding-left:54px}}
</style>
<div class="chat-wrap">
    <div class="page-header"><div><span class="eh-kicker">Community</span><h1>Participant Chat</h1><p>Message another participant or start a group conversation.</p></div><div class="page-actions"><button type="button" class="btn btn-primary" data-modal-open="chat-create"><i class="fas fa-plus"></i> New conversation</button></div></div>
    <section class="chat-card"><h2>Your conversations</h2><p class="chat-muted">Only members of a conversation can read its messages.</p>
        <div class="chat-list">
            @forelse($conversations as $conversation)
                @php
                    $others = $conversation->members->where('id', '!=', auth()->id());
                    $title = $conversation->type === 'group' ? $conversation->title : ($others->first()?->name ?? 'Direct conversation');
                    $mine = (int) $conversation->created_by === (int) auth()->id();
                @endphp
                <div class="chat-row">
                    <span class="chat-avatar" aria-hidden="true"><i class="fas {{ $conversation->type === 'group' ? 'fa-user-group' : 'fa-user' }}"></i></span>
                    <a class="chat-copy" href="{{ route('participant.chat.show', $conversation) }}" style="color:inherit;text-decoration:none">
                        <strong>{{ $title }}</strong><small>{{ $conversation->latestMessage?->sender?->name ? $conversation->latestMessage->sender->name.': ' : '' }}{{ $conversation->latestMessage?->body ?? 'No messages yet' }}</small>
                    </a>
                    <small class="chat-muted">{{ $conversation->messages_count }} {{ \Illuminate\Support\Str::plural('message', $conversation->messages_count) }}</small>
                    @if($mine)<div class="chat-actions">@if($conversation->type === 'group')<button class="btn btn-outline btn-sm" type="button" data-modal-open="chat-edit-{{ $conversation->id }}"><i class="fas fa-pen"></i> Edit</button>@endif<button class="btn btn-outline btn-sm" type="button" data-modal-open="chat-delete-{{ $conversation->id }}"><i class="fas fa-trash"></i> Delete</button></div>@endif
                </div>
            @empty
                <div class="chat-empty">No conversations yet. Start a direct chat or create a group.</div>
            @endforelse
        </div>
        @if($conversations->hasPages())<div class="admin-pagination">{{ $conversations->links() }}</div>@endif
    </section>
</div>

<div class="eh-modal" id="chat-create" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-create-title" @if($errors->any()) data-modal-autoopen @endif>
    <div class="eh-modal-dialog"><form class="chat-form" method="POST" action="{{ route('participant.chat.store') }}">@csrf
        <div class="eh-modal-header"><div><h2 id="chat-create-title">New conversation</h2><p>Choose a direct message or create a group.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
        <div class="eh-modal-body">
            @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
            <label for="chat-create-type">Conversation type</label><select id="chat-create-type" name="type" data-chat-create-type><option value="direct" @selected(old('type','direct')==='direct')>Direct message</option><option value="group" @selected(old('type')==='group')>Group conversation</option></select>
            <div data-chat-create-panel="direct" style="margin-top:14px"><label for="chat-direct-person">Participant</label><select id="chat-direct-person" name="participant_id" required><option value="">Choose a participant</option>@foreach($participants as $person)<option value="{{ $person->id }}" @selected((string)old('participant_id')===(string)$person->id)>{{ $person->name }}</option>@endforeach</select></div>
            <div data-chat-create-panel="group" style="margin-top:14px"><label for="chat-group-title">Group name</label><input id="chat-group-title" name="title" maxlength="120" value="{{ old('title') }}" placeholder="e.g. Digital Skills Cohort"><label for="chat-group-members" style="display:block;margin-top:12px">Invite participants</label><select id="chat-group-members" name="participant_ids[]" multiple aria-describedby="chat-group-help">@foreach($participants as $person)<option value="{{ $person->id }}" @selected(in_array($person->id, old('participant_ids', [])))>{{ $person->name }}</option>@endforeach</select><small id="chat-group-help" class="chat-muted">Select one or more participants. You will be added automatically.</small></div>
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Start conversation</button></div>
    </form></div>
</div>

@foreach($conversations as $conversation)
    @if((int)$conversation->created_by === (int)auth()->id())
        @if($conversation->type === 'group')
        <div class="eh-modal" id="chat-edit-{{ $conversation->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-edit-{{ $conversation->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route('participant.chat.update', $conversation) }}">@csrf @method('PUT')<div class="eh-modal-header"><div><h2 id="chat-edit-{{ $conversation->id }}-title">Edit group</h2><p>Update the conversation name.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><label for="chat-title-{{ $conversation->id }}">Group name</label><input id="chat-title-{{ $conversation->id }}" name="title" maxlength="120" required value="{{ $conversation->title }}" style="width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:9px;font:inherit"></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div></form></div></div>
        @endif
        <div class="eh-modal" id="chat-delete-{{ $conversation->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-delete-{{ $conversation->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route('participant.chat.destroy', $conversation) }}">@csrf @method('DELETE')<div class="eh-modal-header"><div><h2 id="chat-delete-{{ $conversation->id }}-title">Delete conversation?</h2><p>Its messages and membership will also be permanently deleted.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep conversation</button><button class="btn btn-danger" type="submit">Delete conversation</button></div></form></div></div>
    @endif
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.querySelector('[data-chat-create-type]');
    if (!type) return;
    const sync = () => document.querySelectorAll('[data-chat-create-panel]').forEach(panel => {
        const active = panel.dataset.chatCreatePanel === type.value;
        panel.hidden = !active;
        panel.querySelectorAll('input,select').forEach(input => { input.disabled = !active; });
        const control = panel.querySelector('[name="participant_id"]'); if (control) control.required = active;
        const title = panel.querySelector('[name="title"]'); if (title) title.required = active;
        const members = panel.querySelector('[name="participant_ids[]"]'); if (members) members.required = active;
    });
    type.addEventListener('change', sync); sync();
});
</script>
@endsection
