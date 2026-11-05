@extends('layouts.app')
@php
    $cms = app(\App\Services\CmsContentService::class);
    $siteName = $cms->siteChrome()['site_name'];
    $items = collect($faqs ?? [])->filter(fn ($f) => is_array($f) && filled($f['question'] ?? null) && filled($f['answer'] ?? null))->values();
    $groups = $items->groupBy(fn ($f) => trim((string) ($f['category'] ?? '')) ?: 'General');
    $email = config('legal.support_email') ?: 'support@elevateher360.org';
    $title = trim((string) ($content['title'] ?? '')) ?: 'Frequently Asked Questions';
@endphp
@section('title', $title.' | '.$siteName)
@section('meta_description', ($content['summary'] ?? null) ?: 'Answers to common questions about '.$siteName.': accounts, courses, certificates, mentorship, jobs, the library and the mobile app.')
@section('full_bleed', '1')
@push('head')
@if($items->isNotEmpty())
<script type="application/ld+json">{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => $items->map(fn ($f) => [
        '@type' => 'Question',
        'name' => $f['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => trim(strip_tags($cms->render($f['answer'])))],
    ])->all(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif
@endpush
@section('content')
<div class="faq-page">
    @if($preview ?? false)<div class="container"><div class="warning-box" style="margin:16px 0">Draft preview - this content is not necessarily published.</div></div>@endif
    <header class="faq-hero">
        <div class="container">
            <span class="faq-hero__eyebrow">Help centre</span>
            <h1>{{ $title }}</h1>
            <p>{{ ($content['summary'] ?? null) ?: 'Quick answers about accounts, learning, mentorship, jobs and more.' }}</p>
            @if($items->count() > 3)
                <div class="faq-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <input type="search" placeholder="Search questions..." aria-label="Search frequently asked questions" data-faq-search>
                </div>
                @if($groups->count() > 1)
                    <div class="faq-cats" role="group" aria-label="Filter by topic">
                        <button type="button" class="faq-cat is-active" data-faq-cat="">All</button>
                        @foreach($groups->keys() as $cat)
                            <button type="button" class="faq-cat" data-faq-cat="{{ $cat }}">{{ $cat }}</button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </header>

    <div class="container faq-body">
        @if(filled($content['body'] ?? null))<div class="cms-content" style="margin-bottom:24px">{!! $cms->render($content['body']) !!}</div>@endif

        @foreach($groups as $cat => $list)
            <section class="faq-group" data-faq-group="{{ $cat }}">
                <h2>{{ $cat }} <span>{{ $list->count() }}</span></h2>
                @foreach($list as $faq)
                    <details class="faq-item" data-faq-item>
                        <summary>{{ $faq['question'] }}</summary>
                        <div class="faq-answer cms-content">{!! $cms->render($faq['answer']) !!}</div>
                    </details>
                @endforeach
            </section>
        @endforeach
        <p class="faq-empty" data-faq-empty hidden>No questions match your search. Try other words, or contact us below.</p>

        @if(!empty(data_get($content, 'settings.links', [])))<div class="action-group" style="margin-top:24px">@foreach(data_get($content, 'settings.links', []) as $link)<a class="btn btn-outline" href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</div>@endif

        <section class="faq-help">
            <div>
                <h2>Still have a question?</h2>
                <p>Our team is happy to help.</p>
            </div>
            <div class="action-group">
                <a class="btn btn-primary" href="mailto:{{ $email }}"><i class="fas fa-envelope" aria-hidden="true"></i> Email {{ $email }}</a>
                @if(Route::has('legal.privacy'))<a class="btn btn-outline" href="{{ route('legal.privacy') }}">Privacy Policy</a>@endif
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const search = document.querySelector('[data-faq-search]');
    const cats = [...document.querySelectorAll('[data-faq-cat]')];
    const groups = [...document.querySelectorAll('[data-faq-group]')];
    const empty = document.querySelector('[data-faq-empty]');
    let cat = '';
    const apply = () => {
        const q = (search?.value || '').trim().toLowerCase();
        let shown = 0;
        groups.forEach((group) => {
            let inGroup = 0;
            group.querySelectorAll('[data-faq-item]').forEach((item) => {
                const match = (!cat || group.dataset.faqGroup === cat) && (!q || item.textContent.toLowerCase().includes(q));
                item.hidden = !match;
                if (match) inGroup++;
                if (q && match) item.open = true;
            });
            group.hidden = inGroup === 0;
            shown += inGroup;
        });
        if (empty) empty.hidden = shown > 0;
    };
    search?.addEventListener('input', apply);
    cats.forEach((btn) => btn.addEventListener('click', () => {
        cat = btn.dataset.faqCat;
        cats.forEach((b) => b.classList.toggle('is-active', b === btn));
        apply();
    }));
    // Open the question linked in the URL (e.g. shared #faq links) and the first one by default.
    const first = document.querySelector('[data-faq-item]');
    if (first) first.open = true;
});
</script>
@endpush
