<!doctype html><html><head><meta charset="utf-8"><style>
@php $background = isset($template) ? $template?->backgroundFilePath() : null; @endphp
@if($background)
@page{margin:0}body{margin:0;font-family:DejaVu Sans,sans-serif;color:#222}.page{position:relative;width:100%;height:100%;overflow:hidden}.bg{position:absolute;top:0;left:0;width:100%;height:100%}.field{position:absolute}
@else
@page{margin:18mm}body{font-family:DejaVu Sans,sans-serif;text-align:center;color:#222}.content{border:8px solid #800000;padding:28px;height:420px;position:relative}
@endif
@unless($background)
.name{font-size:30px;font-weight:bold;margin:20px 0}.title{font-size:28px;color:#800000}.event{font-size:20px;font-weight:bold}.small{font-size:10px;color:#666}.signature{margin-top:34px}
@endunless
</style></head><body><div class="page">
@if($background)
<img class="bg" src="{{ $background }}">
@foreach(($layout ?? []) as $key => $f)
@if(($f['enabled'] ?? true) && trim((string)($fields[$key] ?? '')) !== '')
@php
    $left = max(0, (float)$f['x'] - ((float)$f['width'] / 2));
    $style = 'left:'.$left.'%;top:'.(float)$f['y'].'%;width:'.(float)$f['width'].'%;'
        .'text-align:'.($f['align'] ?? 'center').';'
        .'font-family:'.($f['font_family'] ?? 'DejaVu Sans').',sans-serif;'
        .'font-size:'.(float)($f['font_size'] ?? 16).'px;'
        .'color:'.($f['color'] ?? '#2b2b2b').';'
        .(($f['bold'] ?? false) ? 'font-weight:bold;' : '')
        .(($f['italic'] ?? false) ? 'font-style:italic;' : '')
        .(($f['underline'] ?? false) ? 'text-decoration:underline;' : '')
        .((float)($f['letter_spacing'] ?? 0) != 0 ? 'letter-spacing:'.(float)$f['letter_spacing'].'px;' : '');
    $text = ($f['prefix'] ?? '').($fields[$key] ?? '').($f['suffix'] ?? '');
    if ($f['uppercase'] ?? false) { $text = mb_strtoupper($text); }
@endphp
<div class="field" style="{{ $style }}">{{ $text }}</div>
@endif
@endforeach
@else
<div class="content">
<div class="title">{{ $event->certificate_title ?: 'Certificate of Attendance' }}</div>
<p>This certifies that</p>
<div class="name">{{ $user->name }}</div>
<p>attended</p>
<div class="event">{{ $event->title }}</div>
<p>held on {{ $event->starts_at->format('d F Y') }}@if($event->venue), at {{ $event->venue }}@endif.</p>
<div class="signature"><strong>{{ $event->certificate_signatory_name ?: 'Women in Technology Uganda' }}</strong><br>{{ $event->certificate_signatory_title ?: 'Authorised Signatory' }}</div>
<p class="small">@if($participantId ?? $user->participant_code)Participant ID: {{ $participantId ?? $user->participant_code }} · @endif Certificate Code: {{ $certificate->certificate_code }} · Verify: {{ route('events.certificates.verify',$certificate->certificate_code) }}</p>
</div>
@endif
</div></body></html>
