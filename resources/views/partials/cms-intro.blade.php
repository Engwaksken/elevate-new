@php($cmsIntro = app(\App\Services\CmsContentService::class)->published($slug))
{{-- Served through /cms-media: the shared host has no public/storage symlink. --}}
@php($cmsIntroImage = app(\App\Services\CmsContentService::class)->mediaUrl($cmsIntro['image_path'] ?? null))
@if($cmsIntroImage)<img src="{{ $cmsIntroImage }}" alt="{{ $cmsIntro['title'] }}" style="max-width:100%;max-height:400px;object-fit:cover">@endif
@if($cmsIntro && ($cmsIntro['body'] ?? null))<div class="cms-content">{!! app(\App\Services\CmsContentService::class)->render($cmsIntro['body']) !!}</div>@endif
@foreach(data_get($cmsIntro, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
