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
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
</head>
@php
$participantShell=auth()->check() && method_exists(auth()->user(),'isParticipant') && auth()->user()->isParticipant();
$partnerType=auth()->check() && method_exists(auth()->user(),'isPartner') && auth()->user()->isPartner() ? auth()->user()->user_type : null;
$partnerHome=$partnerType === 'mentor' ? 'mentorship.dashboard' : 'employer.jobs.index';
$inlineAuthFeedback=request()->routeIs('login') || request()->routeIs('admin.login') || request()->routeIs('register') || request()->routeIs('partners.*') || request()->routeIs('public.partners.*');
$brandLogoPath = app(\App\Services\SettingsService::class)->get('branding.logo_path');
$brandLogoUrl = $brandLogoPath && \Illuminate\Support\Facades\Route::has('branding.asset')
    ? route('branding.asset', ['type' => 'logo', 'v' => md5((string) $brandLogoPath)])
    : null;
@endphp
<body class="{{ $participantShell?'participant-app-body':'' }}">
<a href="#main-content" class="skip-link">Skip to main content</a>

@if($participantShell)
<div class="participant-app-shell">
@include('partials.participant-sidebar')
<div class="ps-overlay" data-sidebar-overlay aria-hidden="true"></div>
<div class="participant-app-main">
<header class="participant-mobile-header">
<button type="button" data-sidebar-toggle aria-controls="participantSidebar" aria-expanded="false" aria-label="Open navigation"><i class="fas fa-bars"></i></button>
<a href="{{ route('dashboard') }}" class="participant-mobile-brand">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="ElevateHer360" style="max-width:36px;max-height:36px;object-fit:contain">@else<span class="ps-mobile-mark">E360</span>@endif<strong>ElevateHer360</strong></a>
@if(Route::has('notifications.index'))<a href="{{ route('notifications.index') }}" class="participant-mobile-action" aria-label="Notifications"><i class="fas fa-bell"></i></a>@else<span></span>@endif
</header>
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
<header class="site-header">
@php($cmsHeader = app(\App\Services\CmsContentService::class)->published('site-header'))
@if($cmsHeader['body'] ?? null)<div class="container cms-content">{!! app(\App\Services\CmsContentService::class)->render($cmsHeader['body']) !!}</div>@endif
<div class="nav">
<a href="{{ route('home') }}" class="brand" aria-label="ElevateHer360 home">@if($brandLogoUrl)<img src="{{ $brandLogoUrl }}" alt="" style="max-width:64px;max-height:48px;object-fit:contain">@elseif($cmsHeader['image_path'] ?? null)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($cmsHeader['image_path']) }}" alt="" style="max-width:64px;max-height:48px">@else<span class="brand-mark">E360</span>@endif<span>{{ $cmsHeader['title'] ?? 'ElevateHer360' }}<small>{{ $cmsHeader['summary'] ?? 'Women in Technology Uganda' }}</small></span></a>
<nav class="nav-links" aria-label="Main navigation">
@if($cmsHeader)
@foreach(data_get($cmsHeader, 'settings.links', []) as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach
@else
<a href="{{ route('home') }}" class="{{ request()->routeIs('home')?'active':'' }}">Home</a>
@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}" class="{{ request()->routeIs('learning.*')?'active':'' }}">Learning</a>@endif
@if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*')?'active':'' }}">Jobs</a>@endif
@if(Route::has('library.index'))<a href="{{ route('library.index') }}" class="{{ request()->routeIs('library.*')?'active':'' }}">Library</a>@endif
<a href="{{ route('events.index') }}">Events</a><a href="{{ route('public.faqs') }}">FAQs</a>
@endif
</nav>
<div class="nav-actions">
@guest
<a href="{{ route('login') }}" class="btn btn-outline btn-sm"><i class="fas fa-right-to-bracket"></i><span>Sign In</span></a>
<a href="{{ route('register') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i><span>Register</span></a>
@else
@if(method_exists(auth()->user(),'isStaff') && auth()->user()->isStaff() && Route::has('admin.dashboard'))
<a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
@elseif($partnerType && Route::has($partnerHome))
<a href="{{ route($partnerHome) }}" class="btn btn-primary btn-sm"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
@endif
@endguest
</div></div>
</header>

<main id="main-content" class="{{ request()->routeIs('home')?'site-main site-main-home':'site-main page-shell' }}">
@unless($inlineAuthFeedback)
@if(session('success'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message success-box" data-auto-dismiss><i class="fas fa-circle-check"></i><span>{{ session('success') }}</span></div></div>@endif
@if(session('error'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message error-box" data-auto-dismiss><i class="fas fa-circle-exclamation"></i><span>{{ session('error') }}</span></div></div>@endif
@if(session('warning'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message warning-box" data-auto-dismiss><i class="fas fa-triangle-exclamation"></i><span>{{ session('warning') }}</span></div></div>@endif
@if(session('info'))<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message info-box" data-auto-dismiss><i class="fas fa-circle-info"></i><span>{{ session('info') }}</span></div></div>@endif
@if($errors->any())<div class="{{ request()->routeIs('home')?'container global-messages':'global-messages' }}"><div class="flash-message error-box" data-auto-dismiss><strong><i class="fas fa-circle-exclamation"></i> Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
@endunless
@yield('content')
</main>

<footer class="site-footer"><div class="container footer">
@php($cmsFooter = app(\App\Services\CmsContentService::class)->published('site-footer'))
<div class="footer-column footer-branding"><strong class="footer-brand">{{ $cmsFooter['title'] ?? 'ElevateHer360' }}</strong><div class="footer-copy"><i class="fas fa-copyright"></i> {{ date('Y') }} {{ $cmsFooter['summary'] ?? 'Women in Technology Uganda' }}</div>@if($cmsFooter['body'] ?? null)<div>{!! app(\App\Services\CmsContentService::class)->render($cmsFooter['body']) !!}</div>@endif</div>
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
@php($cmsFooterLinkChunks = array_chunk($cmsFooterLinks, max(1, (int) ceil(count($cmsFooterLinks) / 3))))
@php($cmsFooterLinkColumns = array_pad($cmsFooterLinkChunks, 3, []))
@foreach($cmsFooterLinkColumns as $index => $links)
<nav class="footer-column footer-links" aria-label="Footer navigation {{ $index + 1 }}">@foreach($links as $link)<a href="{{ $link['url'] }}">{{ $link['label'] }}</a>@endforeach</nav>
@endforeach
@else
<nav class="footer-column footer-links" aria-label="Footer navigation"><a href="{{ route('home') }}">Home</a>@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}">Learning</a>@endif @if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}">Jobs</a>@endif @if(Route::has('library.index'))<a href="{{ route('library.index') }}">Library</a>@endif</nav>
<nav class="footer-column footer-links" aria-label="Account and legal navigation">@guest<a href="{{ route('login') }}">Sign In</a><a href="{{ route('register') }}">Register</a>@endguest @if(Route::has('legal.privacy'))<a href="{{ route('legal.privacy') }}">Privacy Policy</a>@endif @if(Route::has('legal.terms'))<a href="{{ route('legal.terms') }}">Terms of Use</a>@endif</nav>
<nav class="footer-column footer-links" aria-label="Partner navigation"><a href="{{ route('public.partners.mentor') }}">Become a Mentor</a><a href="{{ route('public.partners.employer') }}">Register an Employer</a><a href="{{ route('public.faqs') }}">FAQs</a></nav>
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
