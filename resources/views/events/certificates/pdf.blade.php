<!doctype html><html><head><meta charset="utf-8"><style>
@page{margin:18mm}body{font-family:DejaVu Sans,sans-serif;text-align:center;color:#222}.frame{border:8px solid #800000;padding:28px;height:420px;position:relative}.gold{color:#a68400}.name{font-size:30px;font-weight:bold;margin:20px 0}.title{font-size:28px;color:#800000}.event{font-size:20px;font-weight:bold}.small{font-size:10px;color:#666}.signature{margin-top:34px}
</style></head><body><div class="frame">
<div class="title">{{ $event->certificate_title ?: 'Certificate of Attendance' }}</div>
<p>This certifies that</p>
<div class="name">{{ $user->name }}</div>
<p>attended</p>
<div class="event">{{ $event->title }}</div>
<p>held on {{ $event->starts_at->format('d F Y') }}@if($event->venue), at {{ $event->venue }}@endif.</p>
<div class="signature"><strong>{{ $event->certificate_signatory_name ?: 'Women in Technology Uganda' }}</strong><br>{{ $event->certificate_signatory_title ?: 'Authorised Signatory' }}</div>
<p class="small">Certificate Code: {{ $certificate->certificate_code }} · Verify: {{ route('events.certificates.verify',$certificate->certificate_code) }}</p>
</div></body></html>