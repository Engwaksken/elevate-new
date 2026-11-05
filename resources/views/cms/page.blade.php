@extends('layouts.app')
@php($cms = app(\App\Services\CmsContentService::class))
@php($siteName = $cms->siteChrome()['site_name'])
@section('title', $content['title'].' | '.$siteName)
@section('meta_description', $content['summary'] ?? '')
@php($sections = array_values(array_filter($cms->sections($content), fn ($s) => empty($s['hidden']))))
{{-- Pages built from sections render edge-to-edge so section backgrounds can span the screen. --}}
@if(!empty($sections))@section('full_bleed', '1')@endif
@section('content')
@if($preview ?? false)<div class="container"><div class="warning-box" style="margin:16px 0">Draft preview - this content is not necessarily published.</div></div>@endif
@php($showRoleChooser = request()->routeIs('home') && !($preview ?? false))
@php($leadHero = !empty($sections) && (($sections[0]['type'] ?? null) === 'hero'))
@if(!empty($sections))
    <div class="cms-page">
    @if($leadHero)
        @include('cms.sections', ['sections' => array_slice($sections, 0, 1)])
        @if($showRoleChooser)<x-role-chooser />@endif
        @include('cms.sections', ['sections' => array_slice($sections, 1)])
    @else
        @if($showRoleChooser)<x-role-chooser />@endif
        @include('cms.sections', ['sections' => $sections])
    @endif
    </div>
@endif
<div class="container" style="padding:0 0 48px">
@if(empty($sections))
    @if($showRoleChooser)<x-role-chooser />@endif
    <div style="padding:32px 0 0">
        <div class="page-header"><div><h1>{{ $content['title'] }}</h1>@if($content['summary'] ?? null)<p>{{ $content['summary'] }}</p>@endif</div></div>
        @if($imageUrl = $cms->mediaUrl($content['image_path'] ?? null))<img src="{{ $imageUrl }}" alt="{{ $content['title'] }}" style="max-width:100%;max-height:480px;object-fit:cover;border-radius:12px">@endif
    </div>
@endif
@if(!empty($content['body'] ?? null))<div style="padding:24px 0"><article class="cms-content">{!! $cms->render($content['body']) !!}</article></div>@endif
@if(!empty(data_get($content, 'settings.links', [])))<div style="padding:8px 0 0;display:flex;gap:10px;flex-wrap:wrap">@foreach(data_get($content, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}" @if(!empty($link['new_tab'])) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>@endforeach</div>@endif
</div>
@endsection
