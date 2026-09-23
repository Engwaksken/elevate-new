<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="@yield('meta_description','ElevateHer360 - Learning, mentorship, career development, jobs and digital resources.')">
<title>@yield('title','ElevateHer360')</title>
@vite(['resources/css/app.css','resources/js/app.js'])
@stack('head')
</head>
@php
$participantShell=auth()->check() && method_exists(auth()->user(),'isStaff') && !auth()->user()->isStaff();
$inlineAuthFeedback=request()->routeIs('login') || request()->routeIs('admin.login') || request()->routeIs('register');
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
<a href="{{ route('dashboard') }}" class="participant-mobile-brand"><span class="ps-mobile-mark">E360</span><strong>ElevateHer360</strong></a>
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
<div class="nav">
<a href="{{ route('home') }}" class="brand" aria-label="ElevateHer360 home"><span class="brand-mark">E360</span><span>ElevateHer360<small>Women in Technology Uganda</small></span></a>
<nav class="nav-links" aria-label="Main navigation">
<a href="{{ route('home') }}" class="{{ request()->routeIs('home')?'active':'' }}">Home</a>
@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}" class="{{ request()->routeIs('learning.*')?'active':'' }}">Learning</a>@endif
@if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}" class="{{ request()->routeIs('jobs.*')?'active':'' }}">Jobs</a>@endif
@if(Route::has('library.index'))<a href="{{ route('library.index') }}" class="{{ request()->routeIs('library.*')?'active':'' }}">Library</a>@endif
</nav>
<div class="nav-actions">
@guest
<a href="{{ route('login') }}" class="btn btn-outline btn-sm"><i class="fas fa-right-to-bracket"></i><span>Sign In</span></a>
<a href="{{ route('register') }}" class="btn btn-primary btn-sm"><i class="fas fa-user-plus"></i><span>Register</span></a>
@else
@if(method_exists(auth()->user(),'isStaff') && auth()->user()->isStaff() && Route::has('admin.dashboard'))
<a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
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
<div><strong class="footer-brand">ElevateHer360</strong><div class="footer-copy"><i class="fas fa-copyright"></i> {{ date('Y') }} Women in Technology Uganda</div></div>
<nav class="footer-links" aria-label="Footer navigation"><a href="{{ route('home') }}">Home</a>@if(Route::has('learning.index'))<a href="{{ route('learning.index') }}">Learning</a>@endif @if(Route::has('jobs.index'))<a href="{{ route('jobs.index') }}">Jobs</a>@endif @if(Route::has('library.index'))<a href="{{ route('library.index') }}">Library</a>@endif @guest<a href="{{ route('login') }}">Sign In</a><a href="{{ route('register') }}">Register</a>@endguest</nav>
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
@stack('scripts')
</body>
</html>