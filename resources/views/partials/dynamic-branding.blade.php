@php
    $ehSettings = app(\App\Services\SettingsService::class);
    $ehPrimary = $ehSettings->get('branding.primary_color', '#800000');
    $ehSecondary = $ehSettings->get('branding.secondary_color', '#ffffff');
    $ehAccent = $ehSettings->get('branding.accent_color', '#D4AF37');
    $ehFontFamily = $ehSettings->get('branding.font_family', 'DM Sans');
    $ehFontSize = (int) $ehSettings->get('branding.font_size', 16);
    $ehFavicon = $ehSettings->get('branding.favicon_path');
@endphp
@if($ehFavicon)
<link rel="icon" href="{{ asset('storage/'.$ehFavicon) }}">
@endif
<style>
:root{
    --eh-primary: {{ $ehPrimary }};
    --eh-secondary: {{ $ehSecondary }};
    --eh-accent: {{ $ehAccent }};
    --eh-base-font-size: {{ $ehFontSize }}px;
    --eh-font-family: "{{ $ehFontFamily }}", Arial, sans-serif;
}
html{font-size:var(--eh-base-font-size)}
body{font-family:var(--eh-font-family)}
.btn-primary,.admin-sidebar,.eh-admin-sidebar{--brand-primary:var(--eh-primary)}
.btn-primary{background:var(--eh-primary)!important;border-color:var(--eh-primary)!important}
.admin-eyebrow,.text-accent{color:var(--eh-primary)!important}
</style>
