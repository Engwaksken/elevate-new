@php($cmsIntro = app(\App\Services\CmsContentService::class)->published($slug))
@if($cmsIntro['image_path'] ?? null)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cmsIntro['image_path']) }}" alt="{{ $cmsIntro['title'] }}" style="max-width:100%;max-height:400px;object-fit:cover">@endif
@if($cmsIntro && ($cmsIntro['body'] ?? null))<div class="cms-content">{!! app(\App\Services\CmsContentService::class)->render($cmsIntro['body']) !!}</div>@endif
@foreach(data_get($cmsIntro, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
