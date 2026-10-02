<!doctype html>
<html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#222}
h1,h2{margin-bottom:5px}.section{margin-top:18px}.muted{color:#666}
</style></head><body>
<h1>{{ $resume->user->name }}</h1>
<p>{{ $resume->professional_summary }}</p>

<div class="section"><h2>Experience</h2>
@foreach($resume->experiences as $item)
<p><strong>{{ $item->job_title }}</strong> — {{ $item->organisation }}<br><span class="muted">{{ $item->start_date?->format('M Y') }} - {{ $item->is_current ? 'Present' : $item->end_date?->format('M Y') }}</span><br>{{ $item->description }}</p>
@endforeach</div>

<div class="section"><h2>Education</h2>
@foreach($resume->education as $item)<p><strong>{{ $item->qualification }}</strong> — {{ $item->institution }}</p>@endforeach
</div>

<div class="section"><h2>Skills</h2>
<p>{{ $resume->skills->pluck('skill')->join(', ') }}</p>
</div>
@if($resume->portfolio_url)<div class="section"><h2>Portfolio</h2><a href="{{ $resume->portfolio_url }}">{{ $resume->portfolio_url }}</a></div>@endif
@if($resume->projects->isNotEmpty())<div class="section"><h2>Projects</h2>@foreach($resume->projects as $project)<p><strong>{{ $project->name }}</strong><br>{{ $project->description }}@if($project->url)<br><a href="{{ $project->url }}">{{ $project->url }}</a>@endif</p>@endforeach</div>@endif
@if($resume->portfolioFiles->isNotEmpty())<div class="section"><h2>Portfolio Files</h2>@foreach($resume->portfolioFiles as $file)<p><a href="{{ $file->shareUrl() }}">{{ $file->label }}</a></p>@endforeach<p class="muted">File links expire seven days after this PDF is generated.</p></div>@endif
@if($resume->referees->isNotEmpty())<div class="section"><h2>Referees</h2>@foreach($resume->referees as $referee)<p><strong>{{ $referee->name }}</strong><br>{{ $referee->job_title }} {{ $referee->organisation }}<br>{{ $referee->email }} {{ $referee->phone }}<br>{{ $referee->relationship }}</p>@endforeach</div>@endif
</body></html>
