@extends('layouts.app')
@section('title', $content['title'].' | ElevateHer360')
@section('meta_description', $content['summary'] ?? '')
@section('content')
<div class="container" style="padding:32px 16px">
@if($preview ?? false)<div class="warning-box">Draft preview — this content is not necessarily published.</div>@endif
<div class="page-header"><div><h1>{{ $content['title'] }}</h1>@if($content['summary'] ?? null)<p>{{ $content['summary'] }}</p>@endif</div></div>
@if($content['image_path'] ?? null)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content['image_path']) }}" alt="{{ $content['title'] }}" style="max-width:100%;max-height:480px;object-fit:cover">@endif
<article class="cms-content">{!! app(\App\Services\CmsContentService::class)->render($content['body'] ?? '') !!}</article>
@foreach(data_get($content, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
</div>
@endsection
