@extends('layouts.app')

@section('title', 'Support Ticket #'.$ticket->id.' | ElevateHer360')

@section('content')
@php($canManageQueue = auth()->check() && auth()->user()->hasRole(['it-lead', 'it-assistant', 'IT Lead', 'IT Assistant']))

<div class="page-header">
    <div>
        <span class="eh-kicker">Support ticket #{{ $ticket->id }}</span>
        <h1>{{ $ticket->subject }}</h1>
        <p>Submitted by {{ $ticket->requester?->name ?? 'Unknown requester' }} · {{ $ticket->created_at?->format('d M Y, H:i') ?: '—' }}</p>
    </div>
    <div class="page-actions"><a href="{{ route('it-support.tickets.index') }}" class="btn btn-outline">Back to tickets</a></div>
</div>

<section class="eh-tab-section">
    <h2>Ticket details</h2>
    <p><span class="status-chip {{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></p>
    <dl>
        <dt>Category</dt><dd>{{ $ticket->category ?: '—' }}</dd>
        <dt>Priority</dt><dd>{{ $ticket->priority ? ucfirst($ticket->priority) : '—' }}</dd>
        <dt>Assigned to</dt><dd>{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd>
        <dt>Description</dt><dd>{!! nl2br(e($ticket->description)) !!}</dd>
        @if($ticket->status_updated_at)<dt>Last status update</dt><dd>{{ $ticket->status_updated_at->format('d M Y, H:i') }}</dd>@endif
    </dl>
</section>

@if(isset($ticket->responses) && $ticket->responses->isNotEmpty())
<section class="eh-tab-section">
    <h2>Responses</h2>
    @foreach($ticket->responses as $response)
        <article class="eh-tab-section">
            <p>{{ $response->user?->name ?? 'Support' }} · {{ $response->created_at?->format('d M Y, H:i') ?: '—' }}</p>
            <p>{!! nl2br(e($response->message ?? $response->body ?? '')) !!}</p>
        </article>
    @endforeach
</section>
@endif

@if($canManageQueue)
<section class="eh-tab-section">
    <h2>Queue controls</h2>
    <form method="POST" action="{{ route('it-support.tickets.status', $ticket) }}">
        @csrf
        @method('PATCH')
        <div class="form-group">
            <label for="ticket-status">Update status</label>
            <select id="ticket-status" name="status" required>
                @foreach(['in_progress' => 'In progress', 'awaiting_requester' => 'Awaiting requester', 'resolved' => 'Resolved'] as $value => $label)
                    <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Update status</button>
    </form>

    <form method="POST" action="{{ route('it-support.tickets.assignee', $ticket) }}">
        @csrf
        @method('PATCH')
        <div class="form-group">
            <label for="ticket-assignee">Assign to</label>
            <select id="ticket-assignee" name="assignee_id">
                <option value="">Unassigned</option>
                @foreach($assignees as $assignee)
                    <option value="{{ $assignee->id }}" @selected((int) $ticket->assignee_id === $assignee->id)>{{ $assignee->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">Save assignment</button>
    </form>
</section>
@endif
@endsection
