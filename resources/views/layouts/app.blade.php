<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="@yield('meta_description','ElevateHer360 - Learning, mentorship, career development, jobs and digital resources.')">
<title>@yield('title','ElevateHer360')</title>
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('head')
@include('partials.dynamic-branding')
<meta name="theme-color" content="#800000">
<link rel="manifest" href="{{ route('manifest') }}">
</head>
@php
// Participants, mentors and employers share one role-aware app shell
// (sidebar + role badge); staff keep layouts/admin and guests the public site.
$roleShell=\App\Support\RoleShell::roleFor(auth()->user());
// Public-facing pages stay in the public site shell for signed-in users too.
// Role-specific workspaces (including dashboards) retain the role shell.
$publicSiteShell = request()->routeIs(
    'home', 'legal.*', 'public.*', 'learning.index', 'learning.course.show',
    'jobs.index', 'jobs.show', 'events.index', 'events.show',
    'certificates.verify', 'career.documents.shared', 'career.portfolio.shared',
    'certificates.shared', 'events.certificates.verify',
    // Unverified users cannot use the workspace, so don't show its sidebar yet.
    'verification.notice'
);
$participantShell=$roleShell !== null && ! $publicSiteShell;
$inlineAuthFeedback=request()->routeIs('login') || request()->routeIs('admin.login') || request()->routeIs('register') || request()->routeIs('partners.*') || request()->routeIs('public.partners.*') || request()->routeIs('verification.notice');
$brandLogoPath = app(\App\Services\SettingsService::class)->get('branding.logo_path');
$brandLogoUrl = $brandLogoPath && \Illuminate\Support\Facades\Route::has('branding.asset')
    ? route('branding.asset', ['type' => 'logo', 'v' => md5((string) $brandLogoPath)])
    : null;
@endphp
<body class="{{ $participantShell?'participant-app-body role-shell-body role-shell-body--'.$roleShell:'' }}">
<a href="#main-content" class="skip-link">Skip to main content</a>

@if($participantShell)
<div class="participant-app-shell">
<x-role-sidebar :role="$roleShell" />
<div class="ps-overlay" data-sidebar-overlay aria-hidden="true"></div>
<div class="participant-app-main">
<header class="participant-mobile-header">
<button type="button" data-sidebar-toggle aria-controls="participantSidebar" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
<a href="{{ \App\Support\RoleShell::homeUrl(auth()->user()) ?? route('home') }}" class="participant-mobile-brand">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="ElevateHer360" style="max-width:36px;max-height:36px;object-fit:contain">@else<span class="ps-mobile-mark">E360</span>@endif<span class="participant-mobile-brand-copy"><strong>ElevateHer360</strong><x-role-badge :role="$roleShell" tone="dark" class="role-badge--compact" /></span></a>
@if(Route::has('notifications.index'))<a href="{{ route('notifications.index') }}" class="participant-mobile-action" aria-label="Notifications"><i class="fas fa-bell"></i></a>@else<span></span>@endif
</header>
<x-role-topbar :role="$roleShell" />
<main id="main-content" class="site-main participant-site-main">
@if(session('success'))<div class="flash-message success-box" data-auto-dismiss><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div>@endif
@if(session('error'))<div class="flash-message error-box" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div>@endif
@if(session('warning'))<div class="flash-message warning-box" data-auto-dismiss><i class="fas fa-triangle-exclamation"></i><span>{{ session('warning') }}</span></div>@endif
@if(session('info'))<div class="flash-message info-box" data-auto-dismiss><i class="fas fa-circle-info"></i><span>{{ session('info') }}</span></div>@endif
@if($errors->any())<div class="flash-message error-box" data-auto-dismiss><strong><i class="fas fa-circle-exclamation"></i> Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')
</main>
</div></div>
@else
@php($chrome = app(\App\Services\CmsContentService::class)->siteChrome())
@php($cmsHeader = $chrome['header'])
@php($hdr = $chrome['h'])
@php($announce = $chrome['announcement'])
@if(!empty($announce['enabled']) && !empty($announce['text']))
<div class="site-announcement" style="{{ !empty($announce['background']) ? '--announce-bg:'.$announce['background'].';' : '' }}{{ !empty($announce['text_color']) ? '--announce-fg:'.$announce['text_color'].';' : '' }}"><div class="container"><span>{{ $announce['text'] }}</span>@if(!empty($announce['link_label']) && !empty($announce['link_url']))<a href="{{ $announce['link_url'] }}">{{ $announce['link_label'] }} <i class="fas fa-arrow-right" aria-hidden="true"></i></a>@endif</div></div>
@endif
@php($headerStyle = (!empty($hdr['background']) ? '--site-header-bg:'.$hdr['background'].';' : '').(!empty($hdr['text_color']) ? '--site-header-fg:'.$hdr['text_color'].';' : ''))
<header @class(['site-header', 'site-header--static' => empty($hdr['sticky']), 'site-header--custom' => !empty($hdr['background'])]) @if($headerStyle) style="{{ $headerStyle }}" @endif>
@if($cmsHeader['body'] ?? null)<div class="container cms-content">{!! app(\App\Services\CmsContentService::class)->render($cmsHeader['body']) !!}</div>@endif
<div class="nav">
@php($headerLogo = $chrome['header_logo'] ?: $brandLogoUrl)
@php($logoH = (int) ($hdr['logo_height'] ?? 48))
<a href="{{ route('home') }}" class="brand" aria-label="{{ $chrome['site_name'] }} home">@if($headerLogo)<img src="{{ $headerLogo }}" alt="" class="brand-logo" style="height:{{ $logoH }}px;max-height:{{ $logoH }}px;width:auto;max-width:{{ $logoH * 4 }}px;object-fit:contain">@else<span class="brand-mark">E360</span>@endif @if(!empty($hdr['show_name']) || (!empty($hdr['show_tagline']) && $chrome['tagline'] !== ''))<span>@if(!empty($hdr['show_name'])){{ $chrome['site_name'] }}@endif @if(!empty($hdr['show_tagline']) && $chrome['tagline'] !== '')<small>{{ $chrome['tagline'] }}</small>@endif</span>@endif</a>
<nav class="nav-links" id="siteNav" aria-label="Main navigation">
@if($cmsHeader)
@foreach(data_get($cmsHeader, 'settings.links', []) as $link)<a href="{{ $link['url'] }}" class="{{ ($link['url'] === '/' ? request()->is('/') : (str_starts_with($link['url'], '/') && request()->is(ltrim(strtok($link['url'], '?#'), '/').'*'))) ? 'active' : '' }}" @if(!empty($link['new_tab'])) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>@endforeach
@else
<a href="{{ route('home') }}" class="{{ request()->routeIs('home')?'active':'' }}">Home</a>
@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}" class="{{ request()->routeIs('learning.*')?'active':'' }}">Learning</a>@endif
@if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*')?'active':'' }}">Jobs</a>@endif
@if(Route::has('library.index'))<a href="{{ route('library.index') }}" class="{{ request()->routeIs('library.*')?'active':'' }}">Library</a>@endif
<a href="{{ route('events.index') }}">Events</a><a href="{{ route('public.faqs') }}">FAQs</a>
@endif
@guest
{{-- On small phones these move here from the header buttons. --}}
<div class="nav-mobile-only nav-mobile-divider">Account</div>
<a class="nav-mobile-only" href="{{ route('login') }}"><i class="fas fa-right-to-bracket"></i> Sign in</a>
@foreach(['mentor' => 'Mentors', 'employer' => 'Employers'] as $ptype => $plabel)
@if(Route::has('partners.'.$ptype.'.login'))<a class="nav-mobile-only" href="{{ route('partners.'.$ptype.'.login') }}"><i class="fas fa-handshake-angle"></i> {{ $plabel }}: sign in</a>@endif
@if(Route::has('public.partners.'.$ptype))<a class="nav-mobile-only" href="{{ route('public.partners.'.$ptype) }}"><i class="fas fa-user-plus"></i> {{ $plabel }}: sign up</a>@endif
@endforeach
@endguest
</nav>
<div class="nav-actions">
@if(!empty($hdr['cta_label']) && !empty($hdr['cta_url']))<a href="{{ $hdr['cta_url'] }}" class="btn btn-gold btn-sm site-header-cta"><span>{{ $hdr['cta_label'] }}</span></a>@endif
@guest
@if(!empty($hdr['show_partners']) && (Route::has('partners.mentor.login') || Route::has('partners.employer.login')))
<details class="partner-menu" data-partner-menu>
<summary class="btn btn-outline btn-sm" aria-label="Partner sign in and sign up"><i class="fas fa-handshake-angle" aria-hidden="true"></i><span>Partners</span><i class="fas fa-chevron-down partner-menu__caret" aria-hidden="true"></i></summary>
<div class="partner-menu__panel">
@foreach(['mentor' => ['Mentors', 'fa-user-tie', 'Guide women in tech'], 'employer' => ['Employers', 'fa-building', 'Hire skilled talent']] as $ptype => [$plabel, $picon, $pline])
@if(Route::has('partners.'.$ptype.'.login'))
<div class="partner-menu__group">
<div class="partner-menu__head"><i class="fas {{ $picon }}" aria-hidden="true"></i><div><strong>{{ $plabel }}</strong><small>{{ $pline }}</small></div></div>
<div class="partner-menu__links">
<a href="{{ route('partners.'.$ptype.'.login') }}" aria-label="Sign in as {{ \Illuminate\Support\Str::singular($plabel) }}"><i class="fas fa-right-to-bracket" aria-hidden="true"></i> Sign in</a>
@if(Route::has('public.partners.'.$ptype))<a href="{{ route('public.partners.'.$ptype) }}" class="is-primary" aria-label="Sign up as {{ \Illuminate\Support\Str::singular($plabel) }}"><i class="fas fa-user-plus" aria-hidden="true"></i> Sign up</a>@endif
</div>
</div>
@endif
@endforeach
</div>
</details>
@endif
@if(!empty($hdr['show_auth']))
<a href="{{ route('login') }}" class="btn btn-outline btn-sm nav-signin"><i class="fas fa-right-to-bracket"></i><span>Sign In</span></a>
<a href="{{ route('register') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i><span>Register</span></a>
@endif
@else
{{-- Signed-in users browsing the public site need a way back to their workspace. --}}
@php($workspaceUrl = \App\Support\RoleShell::homeUrl(auth()->user()))
@if($workspaceUrl)
<a href="{{ $workspaceUrl }}" class="btn btn-primary btn-sm"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
@endif
@endguest
<button type="button" class="nav-menu-toggle" data-nav-toggle aria-controls="siteNav" aria-expanded="false" aria-label="Open menu"><i class="fas fa-bars"></i></button>
</div></div>
</header>

<main id="main-content" class="{{ (request()->routeIs('home') || View::hasSection('full_bleed'))?'site-main site-main-home':'site-main page-shell' }}">
@if(request()->routeIs('login', 'register', 'partners.*', 'public.partners.*', 'password.*', 'verification.*') && ! View::hasSection('auth_back_inline'))
<div class="eh-auth-back"><x-back-to-website /></div>
@endif
@unless($inlineAuthFeedback)
@if(session('success'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message success-box" data-auto-dismiss><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div></div>@endif
@if(session('error'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message error-box" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div></div>@endif
@if(session('warning'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message warning-box" data-auto-dismiss><i class="fas fa-triangle-exclamation"></i><span>{{ session('warning') }}</span></div></div>@endif
@if(session('info'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message info-box" data-auto-dismiss><i class="fas fa-circle-info"></i><span>{{ session('info') }}</span></div></div>@endif
@if($errors->any())<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message error-box" data-auto-dismiss><strong><i class="fas fa-circle-exclamation"></i> Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
@endunless
@yield('content')
</main>

@php($cmsFooter = $chrome['footer'])
@php($ftr = $chrome['f'])
<footer class="site-footer" style="{{ !empty($ftr['background']) ? '--site-footer-bg:'.$ftr['background'].';' : '' }}{{ !empty($ftr['text_color']) ? '--site-footer-fg:'.$ftr['text_color'].';' : '' }}"><div class="container footer">
@php($footerLogo = !empty($ftr['show_logo']) ? ($chrome['footer_logo'] ?: $brandLogoUrl) : null)
@php($footerLogoH = (int) ($ftr['logo_height'] ?? 44))
<div class="footer-column footer-branding"><strong class="footer-brand" style="display:flex;align-items:center;gap:10px">@if($footerLogo)<img src="{{ $footerLogo }}" alt="" style="height:{{ $footerLogoH }}px;width:auto;max-width:{{ $footerLogoH * 4 }}px;object-fit:contain">@endif<span>{{ $cmsFooter['title'] ?? $chrome['site_name'] }}</span></strong>
@if($cmsFooter['body'] ?? null)<div class="footer-about">{!! app(\App\Services\CmsContentService::class)->render($cmsFooter['body']) !!}</div>@endif
@if(!empty($ftr['email']) || !empty($ftr['phone']) || !empty($ftr['address']))
<ul class="footer-contact">
@if(!empty($ftr['address']))<li><i class="fas fa-location-dot" aria-hidden="true"></i><span>{{ $ftr['address'] }}</span></li>@endif
@if(!empty($ftr['email']))<li><i class="fas fa-envelope" aria-hidden="true"></i><a href="mailto:{{ $ftr['email'] }}">{{ $ftr['email'] }}</a></li>@endif
@if(!empty($ftr['phone']))<li><i class="fas fa-phone" aria-hidden="true"></i><a href="tel:{{ preg_replace('/[^0-9+]/', '', $ftr['phone']) }}">{{ $ftr['phone'] }}</a></li>@endif
</ul>
@endif
@if(!empty($ftr['social']))
<div class="footer-social">@foreach(\App\Services\CmsContentService::SOCIAL_NETWORKS as $net => [$netLabel, $netIcon])@if(!empty($ftr['social'][$net]))<a href="{{ $ftr['social'][$net] }}" target="_blank" rel="noopener" aria-label="{{ $netLabel }}"><i class="fab {{ $netIcon }}" aria-hidden="true"></i></a>@endif @endforeach</div>
@endif
<div class="footer-copy"><i class="fas fa-copyright"></i> {{ date('Y') }} {{ !empty($ftr['copyright']) ? $ftr['copyright'] : ($cmsFooter['summary'] ?? 'Women in Technology Uganda') }}</div></div>
@if($cmsFooter)
@php($cmsFooterLinks = is_array(data_get($cmsFooter, 'settings.links')) ? data_get($cmsFooter, 'settings.links') : [])
@php($cmsFooterLinks = array_values(array_filter($cmsFooterLinks, static function ($link) {
    if (!is_array($link) || !isset($link['url'], $link['label']) || !is_string($link['url']) || !is_string($link['label'])) {
        return false;
    }

    $url = trim($link['url']);
    if ($url === '' || trim($link['label']) === '' || preg_match('/[\x00-\x20\\\\]/', $url)) {
        return false;
    }

    if (str_starts_with($url, '//')) {
        return false;
    }

    $parts = parse_url($url);
    if ($parts === false) {
        return false;
    }

    if (preg_match('/^[a-z][a-z0-9+.-]*:/i', $url)) {
        return isset($parts['scheme'], $parts['host']) && in_array(strtolower($parts['scheme']), ['http', 'https'], true);
    }

    return true;
})) )
@if(collect($cmsFooterLinks)->contains(fn ($l) => isset($l['column'])))
@php($cmsFooterLinkColumns = [1 => [], 2 => [], 3 => []])
@foreach($cmsFooterLinks as $l)@php($cmsFooterLinkColumns[(int) ($l['column'] ?? 1)][] = $l)@endforeach
@php($cmsFooterLinkColumns = array_values($cmsFooterLinkColumns))
@else
@php($cmsFooterLinkChunks = array_chunk($cmsFooterLinks, max(1, (int) ceil(count($cmsFooterLinks) / 3))))
@php($cmsFooterLinkColumns = array_pad($cmsFooterLinkChunks, 3, []))
@endif
@php($footerColumnTitles = ['Explore', 'Resources', 'Partners'])
@foreach($cmsFooterLinkColumns as $index => $links)
@php($colTitle = trim((string) data_get($ftr, 'column_titles.'.$index, '')) ?: ($footerColumnTitles[$index] ?? 'Links'))
@if(count($links) || ! isset($ftr['column_titles']))
<nav class="footer-column footer-links" aria-label="{{ $colTitle }}"><h2>{{ $colTitle }}</h2>@foreach($links as $link)<a href="{{ $link['url'] }}" @if(!empty($link['new_tab'])) target="_blank" rel="noopener" @endif>{{ $link['label'] }}</a>@endforeach</nav>
@endif
@endforeach
@else
<nav class="footer-column footer-links" aria-label="Footer navigation"><h2>Explore</h2><a href="{{ route('home') }}">Home</a>@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}">Learning</a>@endif @if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}">Jobs</a>@endif @if(Route::has('library.index'))<a href="{{ route('library.index') }}">Library</a>@endif</nav>
<nav class="footer-column footer-links" aria-label="Account and legal navigation"><h2>Account &amp; Legal</h2>@guest<a href="{{ route('login') }}">Sign In</a><a href="{{ route('register') }}">Register</a>@endguest @if(Route::has('legal.privacy'))<a href="{{ route('legal.privacy') }}">Privacy Policy</a>@endif @if(Route::has('legal.terms'))<a href="{{ route('legal.terms') }}">Terms of Use</a>@endif</nav>
<nav class="footer-column footer-links" aria-label="Partner navigation"><h2>Partners</h2><a href="{{ route('public.partners.mentor') }}">Become a Mentor</a><a href="{{ route('public.partners.employer') }}">Register an Employer</a><a href="{{ route('public.faqs') }}">FAQs</a></nav>
@endif
</div></footer>
@endif

<script>
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('[data-password-toggle]').forEach(button=>{
   button.addEventListener('click',()=>{
      const input=document.getElementById(button.getAttribute('data-password-toggle'));
      if(!input)return;
      const visible=input.type==='text';
      input.type=visible?'password':'text';
      button.innerHTML=visible?'<i class="fas fa-eye"></i>':'<i class="fas fa-eye-slash"></i>';
   });
 });
 document.querySelectorAll('[data-auto-dismiss]').forEach(message=>{
   setTimeout(()=>{message.classList.add('is-fading');setTimeout(()=>message.remove(),450);},5000);
 });
});
</script>
@include('partials.global-assistive-tools')
@include('partials.file-preview-modal')
@include('partials.form-tabs-assets')
@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const seen = new Set();

    document.querySelectorAll(
        '.flash-message, .form-alert, .success-box, .error-box, .warning-box, .info-box'
    ).forEach((message) => {
        const text = (message.textContent || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();

        if (!text) return;

        if (seen.has(text)) {
            message.classList.add('eh-duplicate-flash');
            message.remove();
            return;
        }

        seen.add(text);
    });
});
</script>
@include('partials.pwa-install')
<script src="{{ asset('pwa.js') }}" defer></script>
</body>
</html>
