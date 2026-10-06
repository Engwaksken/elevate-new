@extends('layouts.app')
@section('title', 'Support Request #'.$ticket->id.' | ElevateHer360')

@section('content')
<div class="page-header">
    <div><span class="eh-kicker">Support request #{{ $ticket->id }}</span><h1>{{ $ticket->subject }}</h1><p>Submitted {{ $ticket->created_at?->format('d M Y, H:i') ?: '—' }}</p></div>
    <div class="page-actions"><a href="{{ route('participant.support-tickets.index') }}" class="btn btn-outline">Back to my requests</a></div>
</div>

<div class="sp-detail">
<section class="sp-card" aria-labelledby="sp-status-title">
    <h2 id="sp-status-title">Status</h2>
    <p class="sp-status"><span class="status-chip {{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></p>
    @if($ticket->status_updated_at)<p class="sp-muted">Last updated {{ $ticket->status_updated_at->format('d M Y, H:i') }}</p>@endif
</section>

<section class="sp-card" aria-labelledby="sp-details-title">
    <h2 id="sp-details-title">Request details</h2>
    <dl class="sp-dl">
        <div><dt>Category</dt><dd>{{ $ticket->category ?: '—' }}</dd></div>
        <div><dt>Priority</dt><dd>{{ $ticket->priority ? ucfirst($ticket->priority) : '—' }}</dd></div>
        <div class="sp-dl-full"><dt>Description</dt><dd>{!! nl2br(e($ticket->description)) !!}</dd></div>
    </dl>
</section>
</div>
<style>
.sp-detail{display:grid;grid-template-columns:280px minmax(0,1fr);gap:18px;align-items:start}
.sp-card{padding:22px 24px;border:1px solid #eadede;border-radius:16px;background:#fff;box-shadow:0 1px 2px rgba(16,24,40,.04)}
.sp-card h2{margin:0 0 14px;font-size:1.1rem}
.sp-status{margin:0}
.sp-muted{margin:10px 0 0;color:#667085;font-size:.82rem}
.sp-dl{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px;margin:0}
.sp-dl dt{margin-bottom:4px;color:#667085;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em}
.sp-dl dd{margin:0;color:#1f2937;line-height:1.55}
.sp-dl-full{grid-column:1/-1;padding-top:14px;border-top:1px solid #f0eaea}
@media(max-width:900px){.sp-detail{grid-template-columns:1fr}}
@media(max-width:640px){.sp-card{padding:18px 16px}.sp-dl{grid-template-columns:1fr}}
.sp-card .status-chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;background:#f2f4f7;color:#475467;font-size:.72rem;font-weight:800;letter-spacing:.02em}
.sp-card .status-chip.open{background:#eff8ff;color:#175cd3}
.sp-card .status-chip.in_progress{background:#fffaeb;color:#b54708}
.sp-card .status-chip.resolved,.sp-card .status-chip.closed{background:#ecfdf3;color:#067647}
</style>
@endsection
