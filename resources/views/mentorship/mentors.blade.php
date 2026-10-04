@extends('layouts.app')
@section('title','Find a Mentor | ElevateHer360')
@section('content')
<style>
.eh-mentor-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:18px}
.eh-mentor-card{background:#fff;border:1px solid #eadede;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:10px}
.eh-mentor-card h3{margin:0;font-size:1.05rem;color:#101828}
.eh-mentor-card .eh-mentor-meta{color:#667085;font-size:.85rem}
.eh-mentor-tags{display:flex;flex-wrap:wrap;gap:6px}
.eh-mentor-tag{background:#f4f1ee;border:1px solid #eadede;border-radius:20px;padding:2px 10px;font-size:.74rem;color:#475467}
.eh-mentor-score{font-size:.75rem;color:#800000;font-weight:700}
.eh-mentor-reason{font-size:.8rem;color:#667085;font-style:italic}
@media(max-width:980px){.eh-mentor-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:620px){.eh-mentor-grid{grid-template-columns:1fr}}
</style>

<div class="page-header">
    <div>
        <span class="eh-kicker">Mentorship</span>
        <h1>Find a Mentor</h1>
        <p>Select up to {{ $maxMentors }} mentors for a {{ $windowMonths }}-month mentorship period. Mentors are ranked for you.</p>
    </div>
    <a href="{{ route('mentorship.dashboard') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back to My Mentorship</a>
</div>

@if($matches->isNotEmpty())
    <div class="card">
        <h2 style="margin-top:0">My current mentors ({{ $matches->count() }}/{{ $maxMentors }})</h2>
        <div class="eh-data-list">
            @foreach($matches as $match)
                <div class="eh-data-row">
                    <div class="eh-data-row-main">
                        <span class="eh-data-row-icon"><i class="fas fa-user-tie"></i></span>
                        <div class="eh-data-row-copy">
                            <strong>{{ $match->mentor?->name }}</strong>
                            <span>Until {{ optional($match->end_date)->format('d M Y') }}</span>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('mentorship.matches.destroy', $match) }}" onsubmit="return confirm('End this mentorship?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i> End</button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endif

@if($mentors->isEmpty())
    <div class="card"><p class="form-hint">No approved mentors are available yet. Please check back soon.</p></div>
@else
    <div class="eh-mentor-grid">
        @foreach($mentors as $mentor)
            <div class="eh-mentor-card">
                <h3>{{ $mentor->user?->name ?? 'Mentor' }}</h3>
                <div class="eh-mentor-meta">
                    {{ $mentor->job_title ?: 'Mentor' }}@if($mentor->organisation) · {{ $mentor->organisation }}@endif
                </div>
                @if(($mentor->recommendation_score ?? null) !== null)
                    <div class="eh-mentor-score"><i class="fas fa-star"></i> Match score {{ number_format((float) $mentor->recommendation_score, 0) }}%</div>
                @endif
                @if(!empty($mentor->recommendation_reason))
                    <div class="eh-mentor-reason">{{ $mentor->recommendation_reason }}</div>
                @endif
                <div class="eh-mentor-tags">
                    @foreach(array_slice($mentor->mentoring_areas ?? [], 0, 4) as $area)
                        <span class="eh-mentor-tag">{{ $area }}</span>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('mentorship.mentors.store') }}" style="margin-top:auto">
                    @csrf
                    <input type="hidden" name="mentor_user_id" value="{{ $mentor->user_id }}">
                    <button class="btn btn-primary" @disabled($matches->count() >= $maxMentors)>
                        <i class="fas fa-user-plus"></i> Select mentor
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@endif
@endsection
