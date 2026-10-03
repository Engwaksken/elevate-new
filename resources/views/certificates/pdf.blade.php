<!doctype html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin:0; }
body { margin:0; font-family:DejaVu Sans,sans-serif; color:#2b2b2b; }
.page { position:relative; width:100%; height:100%; overflow:hidden; }
.bg { position:absolute; top:0; left:0; width:100%; height:100%; }
.field { position:absolute; }
</style>
</head>
<body>
<div class="page">
@if($template?->backgroundFilePath())
<img class="bg" src="{{ $template->backgroundFilePath() }}">
@endif
@foreach($layout as $key => $f)
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
</div>
</body>
</html>
