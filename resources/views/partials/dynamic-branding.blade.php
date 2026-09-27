@php
    $ehSettings = app(\App\Services\SettingsService::class);
    $ehPrimary = $ehSettings->get('branding.primary_color', '#800000');
    $ehSecondary = $ehSettings->get('branding.secondary_color', '#ffffff');
    $ehAccent = $ehSettings->get('branding.accent_color', '#D4AF37');
    $ehFontFamily = $ehSettings->get('branding.font_family', 'DM Sans');
    $ehFontSize = (int) $ehSettings->get('branding.font_size', 16);
    $ehLogo = $ehSettings->get('branding.logo_path');
    $ehFavicon = $ehSettings->get('branding.favicon_path');

    $ehLogoUrl = $ehLogo && \Illuminate\Support\Facades\Route::has('branding.asset')
        ? route('branding.asset', ['type'=>'logo','v'=>md5((string)$ehLogo)])
        : null;

    $ehFaviconUrl = $ehFavicon && \Illuminate\Support\Facades\Route::has('branding.asset')
        ? route('branding.asset', ['type'=>'favicon','v'=>md5((string)$ehFavicon)])
        : null;
@endphp

@if($ehFaviconUrl)
<link rel="icon" href="{{ $ehFaviconUrl }}">
<link rel="shortcut icon" href="{{ $ehFaviconUrl }}">
<link rel="apple-touch-icon" href="{{ $ehFaviconUrl }}">
@endif

<style>
:root{
    --eh-primary:{{ $ehPrimary }};
    --eh-secondary:{{ $ehSecondary }};
    --eh-accent:{{ $ehAccent }};
    --eh-base-font-size:{{ $ehFontSize }}px;
    --eh-font-family:"{{ $ehFontFamily }}",Arial,sans-serif;
}
html{font-size:var(--eh-base-font-size)}
body{font-family:var(--eh-font-family)}
.btn-primary{background:var(--eh-primary)!important;border-color:var(--eh-primary)!important}
.admin-eyebrow,.text-accent{color:var(--eh-primary)!important}
.eh-dynamic-brand-logo{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain}
</style>
