@extends('layouts.admin')

@section('title', 'Support Ticket #'.$ticket->id.' | ElevateHer360')

@section('content')
@use('App\Models\ItSupportTicket')
@php($canManageQueue = auth()->user()->can('updateStatus', $ticket))
@php($next = match ($ticket->status) { 'open' => ['in_progress'], 'in_progress' => ['awaiting_requester', 'resolved'], 'awaiting_requester' => ['in_progress', 'resolved'], default => [] })
@php($statusLabel = fn ($s) => $s === 'awaiting_requester' ? 'Awaiting requester' : (ItSupportTicket::STATUSES[$s] ?? ucfirst(str_replace('_', ' ', (string) $s))))

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">IT support · Ticket #{{ $ticket->id }}</span>
        <h1>{{ $ticket->subject }}</h1>
        <p>From {{ $ticket->requester?->name ?? 'Unknown requester' }} · {{ $ticket->created_at?->format('d M Y, H:i') ?: '—' }}</p>
    </div>
    <div class="admin-page-actions"><a href="{{ route('it-support.tickets.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to queue</a></div>
</div>

<div class="itq-detail">
    <section class="admin-panel">
        <p style="margin:0 0 14px"><span class="itq-chip itq-chip--{{ $ticket->status }}">{{ $statusLabel($ticket->status) }}</span></p>
        <dl class="itq-dl">
            <div><dt>Category</dt><dd>{{ ItSupportTicket::categoryLabel($ticket->category) ?: '—' }}</dd></div>
            <div><dt>Priority</dt><dd>{{ $ticket->priority ? (ItSupportTicket::PRIORITIES[$ticket->priority] ?? ucfirst($ticket->priority)) : '—' }}</dd></div>
            <div><dt>Requester</dt><dd>{{ $ticket->requester?->name ?? '—' }}@if($ticket->requester?->email)<small class="admin-cell-hint">{{ $ticket->requester->email }}</small>@endif</dd></div>
            <div><dt>Assigned to</dt><dd>{{ $ticket->assignee?->name ?? 'Unassigned' }}</dd></div>
            @if($ticket->status_updated_at)<div><dt>Last status change</dt><dd>{{ $ticket->status_updated_at->format('d M Y, H:i') }}</dd></div>@endif
        </dl>
        <h2 style="margin:0 0 8px;font-size:1rem">What is happening</h2>
        <div class="itq-body">{!! nl2br(e($ticket->description)) !!}</div>

        @if(isset($ticket->responses) && $ticket->responses->isNotEmpty())
            <h2 style="margin:18px 0 8px;font-size:1rem">Responses</h2>
            @foreach($ticket->responses as $response)
                <div class="itq-body" style="margin-bottom:8px"><small class="admin-cell-hint" style="margin:0 0 4px">{{ $response->user?->name ?? 'Support' }} · {{ $response->created_at?->format('d M Y, H:i') ?: '—' }}</small>{!! nl2br(e($response->message ?? $response->body ?? '')) !!}</div>
            @endforeach
        @endif
    </section>

    @if($canManageQueue)
    <aside class="admin-panel itq-controls">
        <h2 style="margin:0 0 12px;font-size:1rem">Queue controls</h2>
        @if($next !== [])
        <form method="POST" action="{{ route('it-support.tickets.status', $ticket) }}">
            @csrf @method('PATCH')
            <div class="form-group">
                <label for="ticket-status">Move to</label>
                <select id="ticket-status" name="status" required>
                    @foreach($next as $value)<option value="{{ $value }}">{{ $statusLabel($value) }}</option>@endforeach
                </select>
            </div>
            <button class="btn btn-primary" type="submit"><i class="fas fa-arrow-right"></i> Update status</button>
        </form>
        @else
            <p class="admin-cell-hint" style="margin:0 0 16px">This ticket is {{ strtolower($statusLabel($ticket->status)) }}; no further status changes.</p>
        @endif

        <form method="POST" action="{{ route('it-support.tickets.assignee', $ticket) }}">
            @csrf @method('PATCH')
            <div class="form-group">
                <label for="ticket-assignee">Assign to</label>
                <select id="ticket-assignee" name="assignee_id">
                    <option value="">Unassigned</option>
                    @foreach($assignees as $assignee)
                        <option value="{{ $assignee->id }}" @selected((int) $ticket->assignee_id === $assignee->id)>{{ $assignee->name }}</option>
                    @endforeach
                </select>
            </div>
            <button class="btn btn-outline" type="submit"><i class="fas fa-user-gear"></i> Save assignment</button>
        </form>
        @if($assignees->isEmpty())<p class="admin-cell-hint">No active IT Lead or IT Assistant accounts yet, so tickets can't be assigned.</p>@endif
    </aside>
    @endif
</div>

@include('support.tickets._styles')
@endsection
