@php
    $ehSettings = app(\App\Services\SettingsService::class);
    $ehPrimary = $ehSettings->get('branding.primary_color', '#800000');
    $ehSecondary = $ehSettings->get('branding.secondary_color', '#ffffff');
    $ehAccent = $ehSettings->get('branding.accent_color', '#D4AF37');
    $ehFontFamily = $ehSettings->get('branding.font_family', 'DM Sans');
    $ehFontSize = (int) $ehSettings->get('branding.font_size', 16);
    $ehLogo = $ehSettings->get('branding.logo_path');
    $ehFavicon = $ehSettings->get('branding.favicon_path');
    $ehLogoUrl = $ehLogo ? \Illuminate\Support\Facades\Storage::disk('public')->url($ehLogo) : null;
    $ehFaviconUrl = $ehFavicon ? \Illuminate\Support\Facades\Storage::disk('public')->url($ehFavicon) : null;
@endphp

@if($ehFaviconUrl)
<link rel="icon" href="{{ $ehFaviconUrl }}?v={{ md5((string)$ehFavicon) }}">
<link rel="shortcut icon" href="{{ $ehFaviconUrl }}?v={{ md5((string)$ehFavicon) }}">
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
.btn-primary,.admin-sidebar,.eh-admin-sidebar{--brand-primary:var(--eh-primary)}
.btn-primary{background:var(--eh-primary)!important;border-color:var(--eh-primary)!important}
.admin-eyebrow,.text-accent{color:var(--eh-primary)!important}
.eh-dynamic-brand-logo{display:block;width:100%;height:100%;max-width:100%;max-height:100%;object-fit:contain}
</style>

@if($ehLogoUrl)
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const logoUrl=@json($ehLogoUrl.'?v='.md5((string)$ehLogo));
    ['.eh-admin-sidebar__logo','.brand-mark','.ps-mark','.ps-mobile-mark'].forEach((selector)=>{
        document.querySelectorAll(selector).forEach((mark)=>{
            mark.innerHTML='';
            const image=document.createElement('img');
            image.src=logoUrl;
            image.alt='Platform logo';
            image.className='eh-dynamic-brand-logo';
            mark.appendChild(image);
        });
    });
});
</script>
@endif
