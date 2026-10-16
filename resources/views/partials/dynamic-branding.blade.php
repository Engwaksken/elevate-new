@php
    $ehSettings = app(\App\Services\SettingsService::class);
    $ehPrimary = $ehSettings->get('branding.primary_color', '#800000');
    $ehSecondary = $ehSettings->get('branding.secondary_color', '#ffffff');
    $ehAccent = $ehSettings->get('branding.accent_color', '#D4AF37');
    $ehFontFamily = $ehSettings->get('branding.font_family', 'DM Sans');
    $ehFontSize = (int) $ehSettings->get('branding.font_size', 16);
    $ehLogo = $ehSettings->get('branding.logo_path');
    $ehFavicon = $ehSettings->get('branding.favicon_path');

    $ehLogoUrl = \Illuminate\Support\Facades\Route::has('branding.asset')
        ? route('branding.asset', is_string($ehLogo) && $ehLogo !== ''
            ? ['type'=>'logo','v'=>md5($ehLogo)]
            : ['type'=>'logo'])
        : asset('icons/apple-touch-icon.png');

    $ehFaviconUrl = \Illuminate\Support\Facades\Route::has('branding.asset')
        ? route('branding.asset', is_string($ehFavicon) && $ehFavicon !== ''
            ? ['type'=>'favicon','v'=>md5($ehFavicon)]
            : ['type'=>'favicon'])
        : asset('icons/pwa-192.png');
@endphp

@if($ehFaviconUrl)
<link rel="icon" href="{{ $ehFaviconUrl }}" data-fallback="{{ asset('icons/pwa-192.png') }}" onerror="this.onerror=null;this.href=this.dataset.fallback;">
<link rel="shortcut icon" href="{{ $ehFaviconUrl }}" data-fallback="{{ asset('icons/pwa-192.png') }}" onerror="this.onerror=null;this.href=this.dataset.fallback;">
@endif
<link rel="apple-touch-icon" href="{{ $ehLogoUrl ?: asset('icons/apple-touch-icon.png') }}" data-fallback="{{ asset('icons/apple-touch-icon.png') }}" onerror="this.onerror=null;this.href=this.dataset.fallback;">

<style>
:root{
    --eh-primary:{{ $ehPrimary }};
    --eh-secondary:{{ $ehSecondary }};
    --eh-accent:{{ $ehAccent }};
    --eh-base-font-size:{{ $ehFontSize }}px;
    --eh-font-family:"{{ $ehFontFamily }}",Arial,sans-serif;
}
html{font-size:calc((var(--eh-base-font-size) + clamp(0px, (100vw - 1600px) * .005, 16px)) * var(--eh-user-text-scale, 1))}
body{font-family:var(--eh-font-family)}
.btn-primary{background:var(--eh-primary)!important;border-color:var(--eh-primary)!important}
.admin-eyebrow,.text-accent{color:var(--eh-primary)!important}
.eh-dynamic-brand-logo{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain}
</style>
