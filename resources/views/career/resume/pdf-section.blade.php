{{-- One resume section for career.resume.pdf. Expects $section, $resume, $titles, $period, $contact, $t. --}}
<div class="section section-{{ $section }}">
    <h2>{{ $titles[$section] }}</h2>
    @switch($section)
        @case('contact')
            @foreach($contact as $line)<div class="item" style="margin-bottom:4px;word-wrap:break-word">{{ $line }}</div>@endforeach
            @break
        @case('summary')
            <div class="summary">{{ $resume->professional_summary }}</div>
            @break
        @case('experience')
            @foreach($resume->experiences as $item)
                <div class="item">
                    <div class="item-title">{{ $item->job_title }}@if($item->organisation), {{ $item->organisation }}@endif</div>
                    <div class="muted">{{ $period($item->start_date, $item->end_date, $item->is_current) }}@if($item->location) · {{ $item->location }}@endif</div>
                    @if($item->description)<div class="desc">{{ $item->description }}</div>@endif
                </div>
            @endforeach
            @break
        @case('education')
            @foreach($resume->education as $item)
                <div class="item">
                    <div class="item-title">{{ $item->qualification }}</div>
                    <div>{{ $item->institution }}@if($item->field_of_study) · {{ $item->field_of_study }}@endif</div>
                    @if($item->start_date || $item->end_date)<div class="muted">{{ $period($item->start_date, $item->end_date) }}</div>@endif
                    @if($item->description)<div class="desc">{{ $item->description }}</div>@endif
                </div>
            @endforeach
            @break
        @case('skills')
            @if(in_array($t, ['technology', 'professional', 'modern'], true))
                <div>@foreach($resume->skills as $skill)<span class="tag">{{ $skill->skill }}</span>@endforeach</div>
            @else
                <div>{{ $resume->skills->map(fn ($s) => $s->skill.($s->level ? ' ('.$s->level.')' : ''))->join(', ') }}</div>
            @endif
            @break
        @case('certifications')
            @foreach($resume->certifications as $item)
                <div class="item"><span class="item-title">{{ $item->name }}</span>@if($item->issuer), {{ $item->issuer }}@endif @if($item->issued_on)<span class="muted">({{ $item->issued_on->format('Y') }})</span>@endif</div>
            @endforeach
            @break
        @case('languages')
            <div>{{ $resume->languages->map(fn ($l) => $l->language.($l->proficiency ? ' ('.$l->proficiency.')' : ''))->join(', ') }}</div>
            @break
        @case('projects')
            @foreach($resume->projects as $project)
                <div class="item"><div class="item-title">{{ $project->name }}</div>@if($project->description)<div class="desc">{{ $project->description }}</div>@endif @if($project->url)<div><a href="{{ $project->url }}">{{ $project->url }}</a></div>@endif</div>
            @endforeach
            @if($resume->portfolioFiles->isNotEmpty())
                @foreach($resume->portfolioFiles as $file)<div class="item"><a href="{{ $file->shareUrl() }}">{{ $file->label }}</a></div>@endforeach
                <div class="muted">File links expire seven days after this PDF is generated.</div>
            @endif
            @break
        @case('referees')
            @foreach($resume->referees as $referee)
                <div class="item">
                    <div class="item-title">{{ $referee->name }}</div>
                    <div>{{ collect([$referee->job_title, $referee->organisation])->filter()->implode(', ') }}</div>
                    <div class="muted">{{ collect([$referee->email, $referee->phone, $referee->relationship])->filter()->implode(' · ') }}</div>
                </div>
            @endforeach
            @break
    @endswitch
</div>
