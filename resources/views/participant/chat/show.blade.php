@extends('layouts.app')
@section('title', ($conversation->title ?? 'Direct chat').' | Participant Chat')
@section('content')
<style>
.chat-wrap{max-width:1000px;margin:auto}.chat-panel{background:#fff;border:1px solid #e7e4e4;border-radius:16px;overflow:hidden}.chat-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid #eaecf0}.chat-head h1{font-size:1.35rem;margin:0}.chat-head p{margin:4px 0 0;color:#667085}.chat-members{font-size:.9rem}.chat-messages{display:flex;flex-direction:column;gap:12px;padding:20px;min-height:280px;max-height:60vh;overflow:auto;background:#fcfcfd}.chat-message{max-width:min(78%,650px);padding:10px 13px;background:#fff;border:1px solid #eaecf0;border-radius:13px 13px 13px 3px;box-shadow:0 2px 5px #10182808;align-self:flex-start;overflow-wrap:anywhere}.chat-message.mine{background:#800000;color:#fff;border-color:#800000;border-radius:13px 13px 3px 13px;align-self:flex-end}.chat-message strong,.chat-message time{display:block;font-size:.78rem;opacity:.78}.chat-message p{margin:5px 0;white-space:pre-wrap}.chat-compose{display:flex;justify-content:flex-end;gap:10px;padding:14px;border-top:1px solid #eaecf0}.chat-message-actions{display:flex;justify-content:flex-end;gap:6px;margin-top:7px}.chat-message-actions button{font-size:.75rem;padding:4px 8px}.chat-form-field{width:100%;padding:10px;border:1px solid #d0d5dd;border-radius:9px;font:inherit}.chat-bottom{display:grid;grid-template-columns:1fr auto;gap:16px;padding:16px;border-top:1px solid #eaecf0}.chat-invite{display:flex;align-items:end;gap:10px}.chat-invite select{min-width:240px;min-height:80px;padding:8px;border:1px solid #d0d5dd;border-radius:9px;font:inherit}.chat-back{color:#800000;font-weight:700}@media(max-width:650px){.chat-bottom{grid-template-columns:1fr}.chat-invite{display:grid}.chat-invite select{width:100%}.chat-message{max-width:90%}}
</style>
@php
    $others = $conversation->members->where('id', '!=', auth()->id());
    $title = $conversation->type === 'group' ? $conversation->title : ($others->first()?->name ?? 'Direct conversation');
@endphp
<div class="chat-wrap">
    <p><a class="chat-back" href="{{ route('participant.chat.index') }}"><i class="fas fa-arrow-left"></i> All conversations</a></p>
    <section class="chat-panel">
        <header class="chat-head"><div><h1>{{ $title }}</h1><p class="chat-members">{{ $conversation->members->pluck('name')->join(', ') }}</p></div>@if($conversation->type === 'group')<i class="fas fa-users" aria-hidden="true"></i>@endif</header>
        <div class="chat-messages" id="latest" aria-live="polite" data-messages-url="{{ route('participant.chat.messages.index', $conversation) }}" data-viewer-id="{{ auth()->id() }}" data-last-id="{{ $conversation->messages->last()?->id ?? 0 }}">
            @forelse($conversation->messages as $message)
                <article @class(['chat-message', 'mine' => $message->sender_id === auth()->id()])>
                    <strong>{{ $message->sender_id === auth()->id() ? 'You' : $message->sender?->name }}</strong>
                    <p>{{ $message->body }}</p>
                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d M Y · H:i') }}</time>
                    @if($message->sender_id === auth()->id())<div class="chat-message-actions"><button class="btn btn-outline btn-sm" type="button" data-modal-open="chat-edit-{{ $message->id }}">Edit</button><button class="btn btn-outline btn-sm" type="button" data-modal-open="chat-delete-{{ $message->id }}">Delete</button></div>@endif
                </article>
            @empty
                <p class="chat-muted">This is the beginning of the conversation. Say hello!</p>
            @endforelse
        </div>
        <div class="chat-compose"><button type="button" class="btn btn-primary" data-modal-open="chat-add"><i class="fas fa-plus"></i> Add message</button></div>
        @if($conversation->type === 'group')
            <div class="chat-bottom"><div><strong>Group members</strong><p class="chat-members">{{ $conversation->members->pluck('name')->join(', ') }}</p></div>
                @if($participants->isNotEmpty())<form class="chat-invite" method="POST" action="{{ route('participant.chat.invite', $conversation) }}">@csrf<label for="chat-invite-members">Invite participants<br><select id="chat-invite-members" name="participant_ids[]" multiple required>@foreach($participants as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></label><button class="btn btn-outline" type="submit">Add to group</button></form>@endif
            </div>
        @endif
    </section>
</div>
<div class="eh-modal" id="chat-add" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-add-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route('participant.chat.messages.store', $conversation) }}">@csrf<div class="eh-modal-header"><div><h2 id="chat-add-title">Add message</h2><p>Send a message to this conversation.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><label for="chat-message-body">Message</label><textarea class="chat-form-field" id="chat-message-body" name="body" required maxlength="5000" rows="4" placeholder="Write a message…"></textarea></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Send message</button></div></form></div></div>
@foreach($conversation->messages as $message)
    @if($message->sender_id === auth()->id())
        <div class="eh-modal" id="chat-edit-{{ $message->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-edit-{{ $message->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route('participant.chat.messages.update', [$conversation, $message]) }}">@csrf @method('PUT')<div class="eh-modal-header"><div><h2 id="chat-edit-{{ $message->id }}-title">Edit message</h2></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><label for="chat-message-edit-{{ $message->id }}">Message</label><textarea class="chat-form-field" id="chat-message-edit-{{ $message->id }}" name="body" required maxlength="5000" rows="4">{{ $message->body }}</textarea></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Save changes</button></div></form></div></div>
        <div class="eh-modal" id="chat-delete-{{ $message->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="chat-delete-{{ $message->id }}-title"><div class="eh-modal-dialog"><form method="POST" action="{{ route('participant.chat.messages.destroy', [$conversation, $message]) }}">@csrf @method('DELETE')<div class="eh-modal-header"><div><h2 id="chat-delete-{{ $message->id }}-title">Delete message?</h2><p>This action cannot be undone.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Keep message</button><button class="btn btn-danger" type="submit">Delete message</button></div></form></div></div>
    @endif
@endforeach
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const box=document.querySelector('.chat-messages');if(!box)return;
    box.scrollTop=box.scrollHeight;
    let lastId=Number(box.dataset.lastId||0);
    const poll=async()=>{
        try{
            const response=await fetch(`${box.dataset.messagesUrl}?after=${lastId}`,{headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
            if(!response.ok)return;
            const data=await response.json();
            for(const message of data.messages){
                const nearBottom=box.scrollHeight-box.scrollTop-box.clientHeight<90;
                const item=document.createElement('article');item.className='chat-message'+(Number(message.sender_id)===Number(box.dataset.viewerId)?' mine':'');
                const sender=document.createElement('strong');sender.textContent=Number(message.sender_id)===Number(box.dataset.viewerId)?'You':message.sender;
                const body=document.createElement('p');body.textContent=message.body;
                const time=document.createElement('time');time.textContent=message.created_at;
                item.append(sender,body,time);box.append(item);lastId=Math.max(lastId,Number(message.id));box.dataset.lastId=lastId;
                if(nearBottom)box.scrollTop=box.scrollHeight;
            }
        }catch(error){/* A temporary network issue should not interrupt the conversation. */}
    };
    window.setInterval(poll,5000);
});
</script>
@endsection
