@php($cms = app(\App\Services\CmsContentService::class))
@foreach($sections as $section)
    @continue(!empty($section['hidden']))
    @php($type = $section['type'] ?? 'text')
    @php($ap = $cms->appearance($section))
    @php($wrap = $ap['full_width'] ? 'cms-container-fluid' : 'container')
    @php($baseClass = [
        'hero' => 'cms-hero', 'cta' => 'cms-cta', 'features' => 'cms-section cms-section--soft',
        'section' => 'cms-section cms-section--grid', 'gallery' => 'cms-section cms-section--gallery',
        'video' => 'cms-section cms-section--video',
    ][$type] ?? 'cms-section')
    <section class="{{ $baseClass }} cms-block-{{ $type }} {{ $ap['class'] }}" @if($ap['style']) style="{{ $ap['style'] }}" @endif @if($ap['anchor']) id="{{ $ap['anchor'] }}" @endif>
        @if($ap['video'])<video class="cms-bg-media" autoplay muted loop playsinline preload="metadata" aria-hidden="true"><source src="{{ $ap['video'] }}" type="video/{{ str_ends_with(strtolower(parse_url($ap['video'], PHP_URL_PATH) ?? ''), '.webm') ? 'webm' : 'mp4' }}"></video>@endif
        @if($ap['iframe'])<div class="cms-bg-media cms-bg-media--embed" aria-hidden="true"><iframe src="{{ $ap['iframe'] }}" title="" tabindex="-1" allow="autoplay; encrypted-media" loading="lazy"></iframe></div>@endif
        @if($ap['overlay'])<div class="cms-bg-overlay" style="{{ $ap['overlay'] }}" aria-hidden="true"></div>@endif

    @if($type === 'hero')
            <div class="{{ $wrap }} cms-hero__inner @if(!empty($section['image_url'])) cms-hero__inner--split @endif">
                <div class="cms-hero__copy">
                    @if(!empty($section['eyebrow']))<span class="cms-eyebrow">{{ $section['eyebrow'] }}</span>@endif
                    @if(!empty($section['title']))<h1>{{ $section['title'] }}</h1>@endif
                    @if(!empty($section['text']))<p>{{ $section['text'] }}</p>@endif
                    @if(!empty($section['primary_label']) || !empty($section['secondary_label']))
                        <div class="cms-hero__actions">
                            @if(!empty($section['primary_label']) && !empty($section['primary_url']))<a class="btn btn-primary" href="{{ $section['primary_url'] }}">{{ $section['primary_label'] }}</a>@endif
                            @if(!empty($section['secondary_label']) && !empty($section['secondary_url']))<a class="btn btn-outline" href="{{ $section['secondary_url'] }}">{{ $section['secondary_label'] }}</a>@endif
                        </div>
                    @endif
                </div>
                @if(!empty($section['image_url']))<div class="cms-hero__media"><img src="{{ $section['image_url'] }}" alt="" loading="lazy"></div>@endif
            </div>
    @elseif($type === 'text')
            <div class="{{ $wrap }}">
                @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                @if(!empty($section['content']))<div class="cms-content">{!! $cms->render($section['content']) !!}</div>@endif
            </div>
    @elseif($type === 'image_text')
            <div class="{{ $wrap }}">
                <div class="cms-image-text @if(!empty($section['reverse'])) cms-image-text--reverse @endif">
                    @if(!empty($section['image_url']))<div class="cms-image-text__media"><img src="{{ $section['image_url'] }}" alt="" loading="lazy"></div>@endif
                    <div class="cms-image-text__copy">
                        @if(!empty($section['title']))<h2>{{ $section['title'] }}</h2>@endif
                        @if(!empty($section['content']))<div class="cms-content">{!! $cms->render($section['content']) !!}</div>@endif
                    </div>
                </div>
            </div>
    @elseif($type === 'features')
            <div class="{{ $wrap }}">
                @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                @if(!empty($section['intro']))<p class="cms-section__intro">{{ $section['intro'] }}</p>@endif
                @if(!empty($section['items']))
                    <div class="cms-features">
                        @foreach($section['items'] as $item)
                            <div class="cms-feature">
                                @if(!empty($item['icon']))<span class="cms-feature__icon"><i class="fas {{ $item['icon'] }}"></i></span>@endif
                                @if(!empty($item['title']))<h3>{{ $item['title'] }}</h3>@endif
                                @if(!empty($item['text']))<p>{{ $item['text'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
    @elseif($type === 'stats')
            <div class="{{ $wrap }}">
                @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                @if(!empty($section['items']))
                    <div class="cms-stats">
                        @foreach($section['items'] as $item)
                            <div class="cms-stat">
                                @if(!empty($item['value']))<strong>{{ $item['value'] }}</strong>@endif
                                @if(!empty($item['label']))<span>{{ $item['label'] }}</span>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
    @elseif($type === 'cta')
            <div class="{{ $wrap }} cms-cta__inner">
                @if(!empty($section['title']))<h2>{{ $section['title'] }}</h2>@endif
                @if(!empty($section['content']))<p>{{ $section['content'] }}</p>@endif
                @if(!empty($section['button_label']) && !empty($section['button_url']))<a class="btn btn-gold" href="{{ $section['button_url'] }}">{{ $section['button_label'] }}</a>@endif
            </div>
    @elseif($type === 'gallery')
            <div class="{{ $wrap }}">
                @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                @if(!empty($section['intro']))<p class="cms-section__intro">{{ $section['intro'] }}</p>@endif
                <div class="cms-gallery cms-gallery--gap-{{ $section['gap'] ?? 'md' }} cms-gallery--{{ $section['ratio'] ?? 'square' }} cms-gallery--captions-{{ $section['captions'] ?? 'below' }}" style="--cms-gallery-cols: {{ max(2, min(6, (int) ($section['columns'] ?? 3))) }}">
                    @foreach($section['items'] ?? [] as $item)
                        @continue(!$cms->safeMediaUrl($item['image_url'] ?? ''))
                        @php($tag = !empty($item['link']) && $cms->safeUrl($item['link']) ? 'a' : 'div')
                        <figure class="cms-gallery__item">
                            <{{ $tag }} class="cms-gallery__frame" @if($tag === 'a') href="{{ $item['link'] }}" @endif>
                                <img src="{{ $item['image_url'] }}" alt="{{ $item['caption'] ?? '' }}" loading="lazy">
                                @if(!empty($item['caption']) && ($section['captions'] ?? 'below') === 'overlay')<span class="cms-gallery__overlay">{{ $item['caption'] }}</span>@endif
                            </{{ $tag }}>
                            @if(!empty($item['caption']) && ($section['captions'] ?? 'below') === 'below')<figcaption>{{ $item['caption'] }}</figcaption>@endif
                        </figure>
                    @endforeach
                </div>
            </div>
    @elseif($type === 'video')
            @php($vurl = $section['video_url'] ?? '')
            <div class="{{ $wrap }}">
                <div class="cms-video cms-video--{{ $section['width'] ?? 'normal' }}">
                    @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                    @if(!empty($section['text']))<p class="cms-section__intro">{{ $section['text'] }}</p>@endif
                    <div class="cms-widget-video">
                        <div class="cms-widget-video__frame">
                            @if($cms->isDirectVideo($vurl))
                                <video src="{{ $vurl }}" controls playsinline preload="metadata" @if(!empty($section['poster_url']) && $cms->safeMediaUrl($section['poster_url'])) poster="{{ $section['poster_url'] }}" @endif @if(!empty($section['autoplay'])) autoplay muted loop @endif></video>
                            @elseif($cms->isValidVideoEmbed($vurl))
                                <iframe src="{{ $vurl }}{{ !empty($section['autoplay']) ? (str_contains($vurl, 'vimeo') ? '?autoplay=1&muted=1' : '?autoplay=1&mute=1') : '' }}" title="{{ $section['title'] ?? 'Video' }}" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
    @elseif($type === 'section')
            <div class="{{ $wrap }}">
                @if(!empty($section['columns']))
                    <div class="cms-grid">
                        @foreach($section['columns'] as $column)
                            @php($cw = $column['width'] ?? '1/1')
                            <div class="cms-col cms-col--{{ str_replace('/', '-', $cw) }}">
                                @foreach($column['widgets'] ?? [] as $widget)
                                    @php($wt = $widget['type'] ?? 'text')
                                    @if($wt === 'heading')
                                        @php($level = in_array($widget['level'] ?? 'h2', ['h2', 'h3', 'h4'], true) ? $widget['level'] : 'h2')
                                        @if(!empty($widget['text']))<{{ $level }} class="cms-widget-heading">{{ $widget['text'] }}</{{ $level }}>@endif
                                    @elseif($wt === 'text')
                                        @if(!empty($widget['content']))<div class="cms-content">{!! $cms->render($widget['content']) !!}</div>@endif
                                    @elseif($wt === 'image')
                                        @if(!empty($widget['image_url']))<figure class="cms-widget-image"><img src="{{ $widget['image_url'] }}" alt="{{ $widget['alt'] ?? '' }}" loading="lazy"></figure>@endif
                                    @elseif($wt === 'button')
                                        @if(!empty($widget['label']) && !empty($widget['url']))
                                            @php($style = in_array($widget['style'] ?? 'primary', ['primary', 'outline', 'gold'], true) ? $widget['style'] : 'primary')
                                            <a class="btn btn-{{ $style }}" href="{{ $widget['url'] }}">{{ $widget['label'] }}</a>
                                        @endif
                                    @elseif($wt === 'spacer')
                                        <div class="cms-widget-spacer" style="height: {{ (int)($widget['height'] ?? 0) }}px"></div>
                                    @elseif($wt === 'divider')
                                        <hr class="cms-widget-divider">
                                    @elseif($wt === 'video')
                                        @php($embed = $widget['embed_url'] ?? '')
                                        @if($cms->isValidVideoEmbed($embed) || $cms->isDirectVideo($embed))
                                            <div class="cms-widget-video"><div class="cms-widget-video__frame">
                                                @if($cms->isDirectVideo($embed))<video src="{{ $embed }}" controls playsinline preload="metadata"></video>
                                                @else<iframe src="{{ $embed }}" title="Embedded video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>@endif
                                            </div></div>
                                        @endif
                                    @elseif($wt === 'html')
                                        @if(!empty($widget['content']))<div class="cms-widget-html">{!! $widget['content'] !!}</div>@endif
                                    @endif
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
    @endif
    </section>
@endforeach
