@extends('layouts.app')
{{-- Professional layout for the Privacy Policy and Terms of Use (published CMS text or the built-in draft). --}}
@php
    $cms = app(\App\Services\CmsContentService::class);
    $siteName = $cms->siteChrome()['site_name'];
    $other = collect(\App\Services\CmsContentService::LEGAL)->except($doc['slug']);
    $meta = \App\Services\CmsContentService::LEGAL[$doc['slug']];
    $minutes = max(1, (int) ceil(str_word_count(strip_tags($doc['html'])) / 200));
@endphp
@section('title', $doc['title'].' | '.$siteName)
@section('meta_description', $doc['intro'])
@section('full_bleed', '1')
@section('content')
<div class="legal-doc">
    @if($preview ?? false)<div class="container"><div class="warning-box" style="margin:16px 0">Draft preview - this content is not necessarily published.</div></div>@endif
    <header class="legal-doc__hero">
        <div class="container">
            <nav class="legal-doc__crumbs" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><i class="fas fa-chevron-right" aria-hidden="true"></i><span>Legal</span><i class="fas fa-chevron-right" aria-hidden="true"></i><span aria-current="page">{{ $doc['title'] }}</span></nav>
            <div class="legal-doc__hero-row">
                <span class="legal-doc__icon" aria-hidden="true"><i class="fas {{ $meta['icon'] }}"></i></span>
                <div>
                    <h1>{{ $doc['title'] }}</h1>
                    <p class="legal-doc__intro">{{ $doc['intro'] }}</p>
                </div>
            </div>
            <ul class="legal-doc__meta">
                <li><i class="fas fa-calendar-check" aria-hidden="true"></i> Last updated <strong>{{ $doc['updated'] }}</strong></li>
                <li><i class="fas fa-code-branch" aria-hidden="true"></i> Version {{ $doc['version'] }}</li>
                <li><i class="fas fa-clock" aria-hidden="true"></i> {{ $minutes }} min read</li>
            </ul>
            <div class="legal-doc__actions">
                @foreach(\App\Services\CmsContentService::LEGAL as $slug => $item)
                    @if(Route::has($item['route']))<a href="{{ route($item['route']) }}" class="legal-doc__tab {{ $slug === $doc['slug'] ? 'is-active' : '' }}" @if($slug === $doc['slug']) aria-current="page" @endif><i class="fas {{ $item['icon'] }}" aria-hidden="true"></i> {{ $item['title'] }}</a>@endif
                @endforeach
                <button type="button" class="legal-doc__print" onclick="window.print()"><i class="fas fa-print" aria-hidden="true"></i> Print / save as PDF</button>
            </div>
        </div>
    </header>

    <div class="container legal-doc__layout">
        @if(count($doc['toc']) > 1)
            <aside class="legal-doc__toc" aria-label="On this page">
                <details open data-legal-toc>
                    <summary>On this page</summary>
                    <ol>
                        @foreach($doc['toc'] as [$id, $label])
                            <li><a href="#{{ $id }}" data-toc-link="{{ $id }}">{{ preg_replace('/^\d+[.)]\s*/', '', $label) }}</a></li>
                        @endforeach
                    </ol>
                </details>
            </aside>
        @endif

        <article class="legal-doc__body" data-legal-body>
            {!! $doc['html'] !!}

            <section class="legal-doc__contact" aria-label="Contact">
                <div>
                    <h2 class="legal-doc__contact-title">Questions about this {{ $doc['slug'] === 'terms' ? 'agreement' : 'policy' }}?</h2>
                    <p>Our team will help you understand how it applies to you.</p>
                </div>
                <div class="legal-doc__contact-actions">
                    <a class="btn btn-primary" href="mailto:{{ $doc['email'] }}"><i class="fas fa-envelope" aria-hidden="true"></i> {{ $doc['email'] }}</a>
                    @if(Route::has('public.faqs'))<a class="btn btn-outline" href="{{ route('public.faqs') }}"><i class="fas fa-circle-question" aria-hidden="true"></i> Read the FAQs</a>@endif
                </div>
            </section>

            @foreach($other as $slug => $item)
                @if(Route::has($item['route']))
                    <a class="legal-doc__related" href="{{ route($item['route']) }}">
                        <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span><small>Related document</small><strong>{{ $item['title'] }}</strong></span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
            @endforeach
        </article>
    </div>
    <a href="#main-content" class="legal-doc__top" aria-label="Back to top" data-legal-top><i class="fas fa-arrow-up" aria-hidden="true"></i></a>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const links = [...document.querySelectorAll('[data-toc-link]')];
    const top = document.querySelector('[data-legal-top]');
    const toc = document.querySelector('[data-legal-toc]');
    // Small screens: start the contents list collapsed.
    if (toc && window.matchMedia('(max-width: 960px)').matches) toc.removeAttribute('open');
    if (links.length && 'IntersectionObserver' in window) {
        const targets = links.map((a) => document.getElementById(a.dataset.tocLink)).filter(Boolean);
        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                links.forEach((a) => a.classList.toggle('is-active', a.dataset.tocLink === entry.target.id));
            });
        }, { rootMargin: '-20% 0px -70% 0px' });
        targets.forEach((t) => observer.observe(t));
    }
    if (top) {
        const toggle = () => top.classList.toggle('is-visible', window.scrollY > 600);
        window.addEventListener('scroll', toggle, { passive: true });
        toggle();
    }
});
</script>
@endpush
