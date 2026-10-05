@extends('layouts.app')
@section('title', 'Support Request #'.$ticket->id.' | ElevateHer360')

@section('content')
<div class="page-header">
    <div><span class="eh-kicker">Support request #{{ $ticket->id }}</span><h1>{{ $ticket->subject }}</h1><p>Submitted {{ $ticket->created_at?->format('d M Y, H:i') ?: '—' }}</p></div>
    <div class="page-actions"><a href="{{ route('participant.support-tickets.index') }}" class="btn btn-outline">Back to my requests</a></div>
</div>

<section class="eh-tab-section">
    <h2>Status tracking</h2>
    <p><span class="status-chip {{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></p>
    @if($ticket->status_updated_at)<p>Last status update: {{ $ticket->status_updated_at->format('d M Y, H:i') }}</p>@endif
</section>

<section class="eh-tab-section">
    <h2>Request details</h2>
    <dl><dt>Category</dt><dd>{{ $ticket->category ?: '—' }}</dd><dt>Priority</dt><dd>{{ $ticket->priority ? ucfirst($ticket->priority) : '—' }}</dd><dt>Description</dt><dd>{!! nl2br(e($ticket->description)) !!}</dd></dl>
</section>
@endsection
