<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin:0; }
body { margin:0; font-family:DejaVu Sans,sans-serif; color:#2b2b2b; }
.page { position:relative; width:100%; height:100%; text-align:center; overflow:hidden; }
.bg { position:absolute; inset:0; width:100%; height:100%; }
.content { position:relative; z-index:2; padding:150px 80px 70px; }
h1 { font-size:34px; margin:0 0 18px; color:#800000; }
.name { font-size:32px; font-weight:bold; margin:18px 0; }
.title { font-size:24px; margin:10px 0; }
.meta { margin-top:30px; font-size:15px; }
</style>
</head>
<body>
<div class="page">
@if($template?->backgroundFilePath())
<img class="bg" src="{{ $template->backgroundFilePath() }}">
@endif
<div class="content">
<h1>Certificate of Completion</h1>
<p>This certificate is proudly presented to</p>
<div class="name">{{ $certificate->user?->name }}</div>
<p>for successfully completing</p>
<div class="title">{{ $certificate->course?->title ?? $certificate->event?->title ?? 'Programme Activity' }}</div>
<div class="meta">
Issued: {{ $certificate->issued_on?->format('d M Y') ?? now()->format('d M Y') }}<br>
Year: {{ $certificate->issued_on?->format('Y') ?? now()->format('Y') }}<br>
Certificate No: {{ $certificate->certificate_number }}
@if($participantId ?? $certificate->user?->participant_code)<br>Participant ID: {{ $participantId ?? $certificate->user->participant_code }}@endif
</div>
</div>
</div>
</body>
</html>
