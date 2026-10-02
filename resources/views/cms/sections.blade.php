@php($cms = app(\App\Services\CmsContentService::class))
@foreach($sections as $section)
    @php($type = $section['type'] ?? 'text')
    @if($type === 'hero')
        <section class="cms-hero">
            <div class="container cms-hero__inner @if(!empty($section['image_url'])) cms-hero__inner--split @endif">
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
        </section>
    @elseif($type === 'text')
        <section class="cms-section">
            <div class="container">
                @if(!empty($section['title']))<h2 class="cms-section__title">{{ $section['title'] }}</h2>@endif
                @if(!empty($section['content']))<div class="cms-content">{!! $cms->render($section['content']) !!}</div>@endif
            </div>
        </section>
    @elseif($type === 'image_text')
        <section class="cms-section">
            <div class="container">
                <div class="cms-image-text @if(!empty($section['reverse'])) cms-image-text--reverse @endif">
                    @if(!empty($section['image_url']))<div class="cms-image-text__media"><img src="{{ $section['image_url'] }}" alt="" loading="lazy"></div>@endif
                    <div class="cms-image-text__copy">
                        @if(!empty($section['title']))<h2>{{ $section['title'] }}</h2>@endif
                        @if(!empty($section['content']))<div class="cms-content">{!! $cms->render($section['content']) !!}</div>@endif
                    </div>
                </div>
            </div>
        </section>
    @elseif($type === 'features')
        <section class="cms-section cms-section--soft">
            <div class="container">
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
        </section>
    @elseif($type === 'stats')
        <section class="cms-section">
            <div class="container">
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
        </section>
    @elseif($type === 'cta')
        <section class="cms-cta">
            <div class="container cms-cta__inner">
                @if(!empty($section['title']))<h2>{{ $section['title'] }}</h2>@endif
                @if(!empty($section['content']))<p>{{ $section['content'] }}</p>@endif
                @if(!empty($section['button_label']) && !empty($section['button_url']))<a class="btn btn-gold" href="{{ $section['button_url'] }}">{{ $section['button_label'] }}</a>@endif
            </div>
        </section>
    @endif
@endforeach
