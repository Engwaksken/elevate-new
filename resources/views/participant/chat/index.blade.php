@extends('layouts.app')
@section('title', 'Participant Chat | ElevateHer360')
@section('content')
@include('participant.chat._styles')
<div class="ehc is-list">
    @include('participant.chat._sidebar', ['activeId' => null])
    <section class="ehc-main" aria-label="Conversation">
        <div class="ehc-welcome">
            <div class="ehc-welcome-icon" aria-hidden="true"><i class="fas fa-comments"></i></div>
            <h2>Your messages</h2>
            <p>Pick a conversation on the left, or start a new one with another participant or a study group. Only members of a conversation can read its messages.</p>
            <button type="button" class="btn btn-primary" data-modal-open="chat-create"><i class="fas fa-plus" aria-hidden="true"></i> New conversation</button>
        </div>
    </section>
</div>
@include('participant.chat._modals')
@endsection
