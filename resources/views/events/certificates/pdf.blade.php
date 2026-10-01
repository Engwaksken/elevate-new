<!doctype html><html><head><meta charset="utf-8"><style>
@php $background = isset($template) ? $template?->backgroundFilePath() : null; @endphp
@if($background)
@page{margin:0}body{margin:0;font-family:DejaVu Sans,sans-serif;text-align:center;color:#222}.page{position:relative;width:100%;height:100%;overflow:hidden}.bg{position:absolute;inset:0;width:100%;height:100%}.content{position:relative;z-index:2;padding:150px 80px 60px}
@else
@page{margin:18mm}body{font-family:DejaVu Sans,sans-serif;text-align:center;color:#222}.content{border:8px solid #800000;padding:28px;height:420px;position:relative}
@endif
.name{font-size:30px;font-weight:bold;margin:20px 0}.title{font-size:28px;color:#800000}.event{font-size:20px;font-weight:bold}.small{font-size:10px;color:#666}.signature{margin-top:34px}
</style></head><body><div class="page">
@if($background)<img class="bg" src="{{ $background }}">@endif
<div class="content">
<div class="title">{{ $event->certificate_title ?: 'Certificate of Attendance' }}</div>
<p>This certifies that</p>
<div class="name">{{ $user->fullName() }}</div>
<p>attended</p>
<div class="event">{{ $event->title }}</div>
<p>held {{ $event->ends_at && ! $event->ends_at->isSameDay($event->starts_at) ? 'from '.$event->starts_at->format('d F').' to '.$event->ends_at->format('d F Y') : 'on '.$event->starts_at->format('d F Y') }}@if($event->venue), at {{ $event->venue }}@endif.</p>
@if($event->cohort)<p>Cohort: <strong>{{ $event->cohort->name }}</strong></p>@endif
<div class="signature"><strong>{{ $event->certificate_signatory_name ?: 'Women in Technology Uganda' }}</strong><br>{{ $event->certificate_signatory_title ?: 'Authorised Signatory' }}</div>
<p class="small">@if($user->participant_code)Participant ID: {{ $user->participant_code }} · @endif Certificate Code: {{ $certificate->certificate_code }} · Verify: {{ route('events.certificates.verify',$certificate->certificate_code) }}</p>
</div></div></body></html>
