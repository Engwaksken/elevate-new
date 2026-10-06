@extends('layouts.admin')
@section('title', 'Mentorship Tracking | ElevateHer360 Administration')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Reports</span>
        <h1>Mentorship Tracking</h1>
        <p>Mentors, matches, sessions and goals across the programme.</p>
    </div>
    <div class="admin-page-actions"><x-export-buttons /></div>
</div>

@php
    $palette = ['#800000', '#D4AF37', '#0e7490', '#7c3aed', '#15803d', '#b45309'];
    $labels = ['mentors' => 'Mentors', 'matches' => 'Matches', 'sessions' => 'Sessions', 'goals' => 'Goals'];
    $total = array_sum($stats);
    $segments = [];
    if ($total > 0) {
        $start = 0;
        $i = 0;
        foreach ($stats as $key => $value) {
            if ($value <= 0) { $i++; continue; }
            $pct = $value / $total * 100;
            $segments[] = ['key' => $key, 'label' => $labels[$key], 'value' => $value, 'pct' => round($pct, 1), 'color' => $palette[$i % count($palette)], 'start' => $start, 'end' => $start + $pct];
            $start += $pct;
            $i++;
        }
    }
    $donut = implode(', ', array_map(fn ($s) => $s['color'].' '.$s['start'].'% '.$s['end'].'%', $segments));
@endphp

<div class="admin-stats-grid compact">
    @foreach([['Mentors', $stats['mentors'], 'fa-user-tie'], ['Matches', $stats['matches'], 'fa-people-arrows'], ['Sessions', $stats['sessions'], 'fa-calendar-check'], ['Goals', $stats['goals'], 'fa-bullseye']] as [$label, $value, $icon])
        <div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($value) }}</strong></div></div>
    @endforeach
</div>

<style>
    .mt-charts{display:grid;grid-template-columns:minmax(280px,380px) 1fr;gap:18px;margin-bottom:20px}
    .mt-donut{display:flex;align-items:center;gap:22px}
    .mt-donut-ring{width:180px;height:180px;border-radius:50%;background:conic-gradient({{ $donut ?: '#e5e7eb' }});position:relative;flex:none}
    .mt-donut-hole{position:absolute;inset:0;margin:44px;border-radius:50%;background:#fff;display:grid;place-items:center;text-align:center}
    .mt-donut-hole strong{font-size:1.5rem;color:#101828}
    .mt-donut-hole small{display:block;color:#667085;font-size:.72rem}
    .mt-legend{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:10px}
    .mt-legend li{display:flex;align-items:center;gap:9px;font-size:.82rem;color:#344054}
    .mt-legend .dot{width:12px;height:12px;border-radius:3px;flex:none}
    .mt-legend .val{margin-left:auto;font-weight:700;color:#101828}
    .mt-bars{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
    .mt-bar-card{background:#fff;border:1px solid #e4e7ec;border-radius:12px;padding:16px}
    .mt-bar-card h3{margin:0 0 12px;font-size:.95rem;color:#101828}
    .mt-bar-row{margin-bottom:12px}
    .mt-bar-row:last-child{margin-bottom:0}
    .mt-bar-row .lbl{display:flex;justify-content:space-between;font-size:.78rem;color:#344054;margin-bottom:5px}
    .mt-bar-track{height:9px;background:#eef0f3;border-radius:99px;overflow:hidden}
    .mt-bar-fill{height:100%;border-radius:99px}
    .mt-empty{color:#98a2b3;font-size:.8rem}
    @media(max-width:900px){.mt-charts{grid-template-columns:1fr}.mt-bars{grid-template-columns:1fr}}
</style>

<div class="admin-panel">
    <h2 style="margin:0 0 16px;font-size:1rem">Programme overview</h2>
    <div class="mt-charts">
        <div class="mt-donut">
            <div class="mt-donut-ring" role="img" aria-label="Distribution of mentors, matches, sessions and goals">
                <div class="mt-donut-hole"><div><strong>{{ number_format($total) }}</strong><small>Total records</small></div></div>
            </div>
            <ul class="mt-legend">
                @forelse($segments as $s)
                    <li><span class="dot" style="background:{{ $s['color'] }}"></span>{{ $s['label'] }}<span class="val">{{ number_format($s['value']) }} · {{ $s['pct'] }}%</span></li>
                @empty
                    <li class="mt-empty">No mentorship data yet.</li>
                @endforelse
            </ul>
        </div>

        <div class="mt-bars">
            @foreach(['sessions' => 'Sessions by status', 'matches' => 'Matches by status', 'mentors' => 'Mentors by status', 'goals' => 'Goals by status'] as $key => $title)
                @php($rows = $breakdowns[$key] ?? collect())
                <div class="mt-bar-card">
                    <h3>{{ $title }}</h3>
                    @php($max = $rows->max() ?: 1)
                    @forelse($rows as $status => $count)
                        @php($color = $palette[$loop->index % count($palette)])
                        <div class="mt-bar-row">
                            <div class="lbl"><span>{{ ucwords(str_replace('_', ' ', (string) $status)) ?: '—' }}</span><strong>{{ number_format($count) }}</strong></div>
                            <div class="mt-bar-track"><div class="mt-bar-fill" style="width:{{ max(3, round($count / $max * 100)) }}%;background:{{ $color }}"></div></div>
                        </div>
                    @empty
                        <div class="mt-empty">No data available.</div>
                    @endforelse
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
