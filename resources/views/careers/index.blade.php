@extends('careers.layout')
@section('title', 'Open positions')
@section('content')
@php $host = request('host'); @endphp
<section class="c-hero">
    @unless($embed)<h1>Join our team</h1>@endunless
    <p>{{ $vacancies->count() ? 'Browse our open positions and apply online. No account needed.' : '' }}</p>
</section>

@if(! $embed || $vacancies->count() > 4)
<form method="GET" class="c-filters" role="search">
    @if($host)<input type="hidden" name="host" value="{{ $host }}">@endif
    <input name="q" value="{{ request('q') }}" placeholder="Search job title or location" aria-label="Search jobs">
    <button class="c-btn outline">Search</button>
</form>
@endif

@forelse($vacancies as $vacancy)
    <a class="c-card c-job" href="{{ $embed ? route('careers.embed.show', [$vacancy->slug, 'host' => $host]) : route('careers.show', $vacancy->slug) }}">
        <div>
            <h2>{{ $vacancy->title }}</h2>
            <div class="c-meta">
                @if($vacancy->department)<span>{{ $vacancy->department->name }}</span>@endif
                @if($vacancy->location)<span>{{ $vacancy->location }}</span>@endif
                @if($vacancy->employment_type)<span>{{ $vacancy->employment_type }}</span>@endif
                @if($vacancy->closing_date)<span>Closes {{ $vacancy->closing_date->format('d M Y') }}</span>@endif
            </div>
            @if($vacancy->summary)<p style="margin:8px 0 0">{{ \Illuminate\Support\Str::limit($vacancy->summary, 180) }}</p>@endif
        </div>
        <span class="c-btn">View &amp; apply</span>
    </a>
@empty
    <div class="c-card"><strong>There are no open positions right now.</strong><p style="margin:4px 0 0;color:var(--c-muted)">Please check back soon.</p></div>
@endforelse
@endsection
