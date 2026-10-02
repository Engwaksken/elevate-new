@extends('layouts.app')
@section('title', $content['title'].' | ElevateHer360')
@section('meta_description', $content['summary'] ?? '')
@section('content')
@php($cms = app(\App\Services\CmsContentService::class))
@php($sections = $cms->sections($content))
<div class="container" style="padding:0 16px 48px">
@if($preview ?? false)<div class="warning-box">Draft preview — this content is not necessarily published.</div>@endif
@if(!empty($sections))
    @include('cms.sections', ['sections' => $sections])
@else
    <div style="padding:32px 0 0">
        <div class="page-header"><div><h1>{{ $content['title'] }}</h1>@if($content['summary'] ?? null)<p>{{ $content['summary'] }}</p>@endif</div></div>
        @if($content['image_path'] ?? null)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($content['image_path']) }}" alt="{{ $content['title'] }}" style="max-width:100%;max-height:480px;object-fit:cover;border-radius:12px">@endif
    </div>
@endif
@if(!empty($content['body'] ?? null))<div style="padding:24px 0"><article class="cms-content">{!! $cms->render($content['body']) !!}</article></div>@endif
@if(!empty(data_get($content, 'settings.links', [])))<div style="padding:8px 0 0;display:flex;gap:10px;flex-wrap:wrap">@foreach(data_get($content, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</div>@endif
</div>
@endsection
