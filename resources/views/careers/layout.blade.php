@php
    $careersSettings = app(\App\Services\SettingsService::class);
    $careersOrg = (string) $careersSettings->get('branding.system_name', 'ElevateHer360');
    $careersPrimary = (string) $careersSettings->get('branding.primary_color', '#800000');
    $careersLogo = \Illuminate\Support\Facades\Route::has('branding.asset') ? route('branding.asset', ['type' => 'logo']) : asset('icons/apple-touch-icon.png');
    $embed = $embed ?? false;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Careers') | {{ $careersOrg }}</title>
@if($embed)<meta name="robots" content="noindex">@endif
<style>
:root{--c-primary:{{ $careersPrimary }};--c-text:#1f2430;--c-muted:#5f6675;--c-line:#e3e5ea;--c-bg:#f6f7f9;--c-card:#fff;--c-danger:#b42318;--c-ok:#067647}
*{box-sizing:border-box}
html,body{margin:0}
body{font-family:"DM Sans",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif;color:var(--c-text);background:{{ $embed ? 'transparent' : 'var(--c-bg)' }};font-size:16px;line-height:1.55}
a{color:var(--c-primary)}
.c-wrap{max-width:960px;margin:0 auto;padding:{{ $embed ? '4px' : '24px 16px 48px' }}}
.c-top{background:#fff;border-bottom:1px solid var(--c-line)}
.c-top .c-wrap{display:flex;align-items:center;gap:12px;padding-top:14px;padding-bottom:14px}
.c-top img{height:40px;width:auto}
.c-top strong{font-size:1.1rem}
.c-hero h1{margin:8px 0 4px;font-size:1.8rem;line-height:1.2}
.c-hero p{margin:0 0 20px;color:var(--c-muted)}
.c-card{background:var(--c-card);border:1px solid var(--c-line);border-radius:12px;padding:20px;margin-bottom:14px}
.c-job{display:flex;justify-content:space-between;gap:16px;align-items:center;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
.c-job:hover,.c-job:focus-visible{border-color:var(--c-primary);box-shadow:0 2px 10px rgba(0,0,0,.06)}
.c-job h2{margin:0 0 4px;font-size:1.15rem;color:var(--c-primary)}
.c-meta{display:flex;flex-wrap:wrap;gap:6px 14px;color:var(--c-muted);font-size:.9rem}
.c-pill{display:inline-block;padding:2px 10px;border-radius:999px;background:#f1f2f4;font-size:.8rem;color:var(--c-muted)}
.c-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border-radius:8px;border:1px solid var(--c-primary);background:var(--c-primary);color:#fff;font-weight:600;font-size:.95rem;text-decoration:none;cursor:pointer;white-space:nowrap}
.c-btn.outline{background:#fff;color:var(--c-primary)}
.c-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.c-filters input,.c-filters select{flex:1 1 200px}
label{display:block;font-weight:600;font-size:.9rem;margin-bottom:4px}
input,select,textarea{width:100%;font:inherit;padding:10px 12px;border:1px solid #c9ccd3;border-radius:8px;background:#fff;color:inherit}
input:focus,select:focus,textarea:focus{outline:2px solid var(--c-primary);outline-offset:1px;border-color:var(--c-primary)}
textarea{min-height:120px;resize:vertical}
.c-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
.c-field{margin-bottom:14px}
.c-hint{display:block;color:var(--c-muted);font-size:.82rem;margin-top:3px;font-weight:400}
.c-error{color:var(--c-danger);font-size:.85rem;margin-top:4px}
.c-alert{border-radius:8px;padding:12px 14px;margin-bottom:16px;background:#fef3f2;border:1px solid #fecdca;color:var(--c-danger)}
.c-alert.ok{background:#ecfdf3;border-color:#abefc6;color:var(--c-ok)}
.c-section h3{font-size:1.05rem;margin:18px 0 6px}
.c-rich{white-space:pre-line}
.c-check{display:flex;gap:8px;align-items:flex-start;font-weight:400}
.c-check input{width:auto;margin-top:5px}
.c-hp{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden}
.c-foot{color:var(--c-muted);font-size:.8rem;text-align:center;margin-top:20px}
@media (max-width:600px){.c-job{flex-direction:column;align-items:flex-start}.c-hero h1{font-size:1.45rem}}
</style>
</head>
<body>
@unless($embed)
<header class="c-top"><div class="c-wrap"><img src="{{ $careersLogo }}" alt="" onerror="this.style.display='none'"><strong>{{ $careersOrg }} Careers</strong></div></header>
@endunless
<main class="c-wrap">
@yield('content')
@if($embed)<p class="c-foot">Powered by {{ $careersOrg }}</p>@endif
</main>
@if($embed)
<script>
(function(){
  // Tell the host page (careers/embed.js) how tall this frame should be.
  function send(){ try{ parent.postMessage({ehCareersHeight: document.documentElement.scrollHeight}, '*'); }catch(e){} }
  window.addEventListener('load', send); window.addEventListener('resize', send);
  if (window.ResizeObserver) new ResizeObserver(send).observe(document.body); else setInterval(send, 1000);
  send();
})();
</script>
@endif
</body>
</html>
