@php
    $details ??= app(\App\Services\CertificatePdfService::class)->details($certificate);
    $background = $template?->backgroundFilePath();
    $portrait = ($template?->orientation ?? 'landscape') === 'portrait';
@endphp
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin:0; }
body { margin:0; font-family:DejaVu Sans,sans-serif; color:#2b2b2b; }
.page { position:relative; width:100%; height:100%; text-align:center; overflow:hidden; }
.bg { position:absolute; top:0; left:0; width:100%; height:100%; }
.content { position:relative; z-index:2; padding:{{ $portrait ? '220px 70px 80px' : '140px 90px 60px' }}; }
@unless($background)
.frame { position:absolute; top:22px; left:22px; right:22px; bottom:22px; border:8px solid #800000; }
@endunless
h1 { font-size:32px; margin:0 0 14px; color:#800000; letter-spacing:1px; }
.lead { margin:0; font-size:14px; }
.name { font-size:34px; font-weight:bold; margin:14px 0 12px; }
.title { font-size:22px; font-weight:bold; margin:8px 0 14px; color:#800000; }
.facts { margin:10px auto 0; border-collapse:collapse; }
.facts td { padding:3px 12px; font-size:14px; }
.facts td.label { text-align:right; color:#666; }
.facts td.value { text-align:left; font-weight:bold; }
.meta { margin-top:22px; font-size:10px; color:#666; }
</style>
</head>
<body>
<div class="page">
@if($background)
<img class="bg" src="{{ $background }}">
@else
<div class="frame"></div>
@endif
<div class="content">
<h1>Certificate of Completion</h1>
<p class="lead">This certificate is proudly presented to</p>
<div class="name">{{ $details['full_name'] }}</div>
<p class="lead">for successfully completing</p>
<div class="title">{{ $details['title'] }}</div>
<table class="facts">
@if($details['cohort'])<tr><td class="label">Cohort</td><td class="value">{{ $details['cohort'] }}</td></tr>@endif
@if($details['period'])<tr><td class="label">Period</td><td class="value">{{ $details['period'] }}</td></tr>@endif
<tr><td class="label">Issued</td><td class="value">{{ $details['issued_on'] }}</td></tr>
</table>
<div class="meta">
Certificate No: {{ $details['number'] }}
@if($details['participant_code']) · Participant ID: {{ $details['participant_code'] }}@endif
@if($details['verify_url'])<br>Verify: {{ $details['verify_url'] }}@endif
</div>
</div>
</div>
</body>
</html>
