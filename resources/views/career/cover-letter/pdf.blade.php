<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>{{ $letter->title }}</title>
<style>body{font-family:DejaVu Sans,sans-serif;font-size:11pt;line-height:1.6;color:#222;margin:30px}h1{font-size:18pt;margin-bottom:0}.contact{color:#555}.body{white-space:pre-wrap}</style></head>
<body>
<h1>{{ $letter->user->name }}</h1>
<p class="contact">{{ $letter->user->email }} @if($letter->user->phone) · {{ $letter->user->phone }} @endif</p>
@if($letter->recipient_name || $letter->employer_name)<p>{{ $letter->recipient_name }}<br>{{ $letter->employer_name }}</p>@endif
@if($letter->job_title)<p><strong>Re: {{ $letter->job_title }}</strong></p>@endif
<div class="body">{{ $letter->body }}</div>
</body></html>
