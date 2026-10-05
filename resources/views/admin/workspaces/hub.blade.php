@extends('layouts.admin')
@section('title', $workspace['title'].' Workspace | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">{{ $workspace['eyebrow'] }}</span>
        <h1>{{ $workspace['title'] }}</h1>
        <p>{{ $workspace['description'] }}</p>
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach($workspace['stats'] as $stat)
        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas {{ $stat['icon'] }}"></i></span>
            <div>
                <small>{{ $stat['label'] }}</small>
                <strong>{{ $stat['value'] === null ? '—' : number_format($stat['value']) }}</strong>
            </div>
        </div>
    @endforeach
</div>

<div class="eh-hub-grid">
    @forelse($workspace['links'] as $link)
        @if(($workspace['key'] ?? null) === 'mentorship' && $link['url'] === route('admin.mentorship.mentors.create'))
            @continue
        @endif
        <a class="eh-hub-card" href="{{ $link['url'] }}">
            <span class="eh-hub-icon"><i class="fas {{ $link['icon'] }}"></i></span>
            <span class="eh-hub-text">
                <strong>{{ $link['label'] }}</strong>
                <small>{{ $link['description'] }}</small>
            </span>
            <i class="fas fa-chevron-right eh-hub-arrow"></i>
        </a>
    @empty
        <div class="admin-panel"><div class="admin-empty">No screens are available in this workspace for your account.</div></div>
    @endforelse
</div>

@push('head')
<style>
    .eh-hub-grid { display:grid; grid-template-columns:repeat(4, minmax(0, 1fr)); gap:12px; }
    .eh-hub-card { display:flex; align-items:center; gap:12px; min-height:92px; padding:16px; background:#fff; border:1px solid var(--ad-border, #e4e7ec); border-radius:12px; color:var(--ad-text, #172033); text-decoration:none; transition:border-color .15s, box-shadow .15s, transform .15s; }
    .eh-hub-card:hover, .eh-hub-card:focus-visible { border-color:var(--ad-primary, #800000); box-shadow:0 8px 22px rgba(16, 24, 40, .08); transform:translateY(-1px); }
    .eh-hub-icon { flex:none; width:42px; height:42px; display:grid; place-items:center; border-radius:10px; background:#fff4f4; color:var(--ad-primary, #800000); }
    .eh-hub-text { flex:1; min-width:0; }
    .eh-hub-text strong, .eh-hub-text small { display:block; }
    .eh-hub-text small { margin-top:2px; color:var(--ad-muted, #667085); font-size:.76rem; line-height:1.35; }
    .eh-hub-arrow { color:var(--ad-muted, #667085); font-size:.75rem; }
    @media (max-width:1180px) { .eh-hub-grid { grid-template-columns:repeat(2, minmax(0, 1fr)); } }
    @media (max-width:640px) { .eh-hub-grid { grid-template-columns:1fr; } }
</style>
@endpush
@endsection
