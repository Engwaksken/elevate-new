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
</body></html>
