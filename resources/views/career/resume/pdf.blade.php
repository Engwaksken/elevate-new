@php
    /** Rendered by DomPDF for downloads and in the browser for template previews ($screen = true). Keep CSS DomPDF-safe: no flex/grid. */
    $t = array_key_exists($resume->template, \App\Support\ResumeTemplates::TEMPLATES) ? $resume->template : 'classic';
    $theme = [
        'classic'      => ['font' => 'DejaVu Serif', 'accent' => '#111111', 'head' => 'center', 'layout' => 'single', 'order' => ['summary', 'experience', 'education', 'skills', 'certifications', 'languages', 'projects', 'referees']],
        'modern'       => ['font' => 'DejaVu Sans', 'accent' => '#800000', 'head' => 'band', 'layout' => 'single', 'order' => ['summary', 'experience', 'skills', 'education', 'projects', 'certifications', 'languages', 'referees']],
        'minimal'      => ['font' => 'DejaVu Sans', 'accent' => '#555555', 'head' => 'left', 'layout' => 'single', 'order' => ['summary', 'experience', 'education', 'skills', 'projects', 'certifications', 'languages', 'referees']],
        'professional' => ['font' => 'DejaVu Sans', 'accent' => '#1f3a5f', 'head' => 'left', 'layout' => 'sidebar', 'order' => ['summary', 'experience', 'education', 'projects', 'referees'], 'side' => ['contact', 'skills', 'languages', 'certifications']],
        'graduate'     => ['font' => 'DejaVu Sans', 'accent' => '#0f766e', 'head' => 'center', 'layout' => 'single', 'order' => ['summary', 'education', 'projects', 'experience', 'skills', 'certifications', 'languages', 'referees']],
        'technology'   => ['font' => 'DejaVu Sans', 'accent' => '#2563eb', 'head' => 'dark', 'layout' => 'sidebar', 'order' => ['summary', 'experience', 'projects', 'education', 'referees'], 'side' => ['contact', 'skills', 'certifications', 'languages']],
        'executive'    => ['font' => 'DejaVu Serif', 'accent' => '#8a6d1f', 'head' => 'rule', 'layout' => 'single', 'order' => ['summary', 'experience', 'education', 'certifications', 'skills', 'languages', 'projects', 'referees']],
    ][$t];
    $user = $resume->user;
    $location = trim(collect([$user?->profile?->district, $user?->profile?->country])->filter()->implode(', '));
    $contact = collect([$user?->email, $user?->phone, $location, $resume->portfolio_url])->filter()->values();
    $period = fn ($start, $end, $current = false) => collect([$start?->format('M Y'), $current ? 'Present' : $end?->format('M Y')])->filter()->implode(' to ');
    $has = [
        'summary' => filled($resume->professional_summary),
        'experience' => $resume->experiences->isNotEmpty(),
        'education' => $resume->education->isNotEmpty(),
        'skills' => $resume->skills->isNotEmpty(),
        'certifications' => $resume->certifications->isNotEmpty(),
        'languages' => $resume->languages->isNotEmpty(),
        'projects' => $resume->projects->isNotEmpty() || $resume->portfolioFiles->isNotEmpty(),
        'referees' => $resume->referees->isNotEmpty(),
        'contact' => $contact->isNotEmpty(),
    ];
    $titles = ['summary' => $t === 'graduate' ? 'Profile' : 'Professional Summary', 'experience' => $t === 'executive' ? 'Leadership and Experience' : 'Work Experience', 'education' => 'Education',
        'skills' => $t === 'technology' ? 'Technical Skills' : 'Skills', 'certifications' => 'Certifications', 'languages' => 'Languages', 'projects' => 'Projects', 'referees' => 'Referees', 'contact' => 'Contact'];
@endphp
<!doctype html>
<html><head><meta charset="utf-8"><title>{{ $user?->name }} - Resume</title><style>
@page{margin:28px 34px}
body{font-family:'{{ $theme['font'] }}',sans-serif;font-size:11px;line-height:1.45;color:#222;margin:0}
a{color:{{ $theme['accent'] }};text-decoration:none}
.name{font-size:24px;font-weight:bold;margin:0;letter-spacing:.5px}
.contact{color:#555;font-size:10px;margin-top:4px}
.section{margin-top:14px}
.section h2{font-size:12px;margin:0 0 6px;color:{{ $theme['accent'] }};text-transform:uppercase;letter-spacing:1px}
.item{margin-bottom:8px}
.item-title{font-weight:bold}
.muted{color:#666;font-size:10px}
.desc{margin-top:2px}
.tag{display:inline-block;margin:0 4px 4px 0;padding:2px 7px;border-radius:3px;font-size:10px}
/* Header styles */
.head-center{text-align:center;padding-bottom:8px;border-bottom:1.5px solid {{ $theme['accent'] }}}
.head-left{padding-bottom:8px;border-bottom:1px solid #ddd}
.head-band{background:{{ $theme['accent'] }};color:#fff;padding:16px 18px;margin:0 0 4px}
.head-band .contact{color:#f3dede}
.head-dark{background:#0f172a;color:#fff;padding:16px 18px}
.head-dark .contact{color:#cbd5e1}
.head-dark .name span{color:#60a5fa}
.head-rule{text-align:center;padding-bottom:8px;border-bottom:3px double {{ $theme['accent'] }}}
.head-rule .name{font-size:26px;letter-spacing:2px;text-transform:uppercase}
/* Template-specific section styling */
.t-classic .section h2{text-transform:none;font-size:13px;letter-spacing:0;border-bottom:1px solid #111;padding-bottom:2px}
.t-modern .section h2{border-left:4px solid {{ $theme['accent'] }};padding-left:7px}
.t-minimal{color:#333}.t-minimal .section h2{color:#888;font-weight:normal;font-size:10px;letter-spacing:2px}
.t-graduate .section h2{background:#e6f4f1;padding:3px 7px}
.t-executive .section h2{text-align:center;font-weight:normal;letter-spacing:3px}
.t-executive .summary{font-style:italic;text-align:center}
.sidebar{background:#f1f5f9;padding:12px 12px 4px;vertical-align:top}
.t-technology .sidebar{background:#eef2ff}
.t-technology .tag{background:#dbeafe;color:#1e3a8a;font-family:'DejaVu Sans Mono',monospace}
.t-professional .tag,.t-modern .tag{background:#f3e8e8;color:#5b0000}
.t-professional .tag{background:#e2e8f0;color:#1f3a5f}
.main{padding:0 0 0 16px;vertical-align:top}
@if($screen ?? false)
html{background:#e9e9ee}
body{max-width:780px;margin:18px auto;padding:30px 36px;background:#fff;box-shadow:0 2px 14px rgba(0,0,0,.15)}
@endif
</style></head><body class="t-{{ $t }}">

@php($headClass = ['center' => 'head-center', 'left' => 'head-left', 'band' => 'head-band', 'dark' => 'head-dark', 'rule' => 'head-rule'][$theme['head']])
<div class="{{ $headClass }}">
    <p class="name">@if($t === 'technology')<span>&lt;</span>{{ $user?->name }}<span>/&gt;</span>@else{{ $user?->name }}@endif</p>
    @if($theme['layout'] !== 'sidebar' && $contact->isNotEmpty())<div class="contact">{{ $contact->implode('  |  ') }}</div>@endif
</div>

@if($theme['layout'] === 'sidebar')
    <table style="width:100%;border-collapse:collapse;margin-top:10px"><tr>
        <td class="sidebar" style="width:30%">
            @foreach($theme['side'] as $section)
                @if($has[$section] ?? false) @include('career.resume.pdf-section') @endif
            @endforeach
        </td>
        <td class="main">
            @foreach($theme['order'] as $section)
                @if($has[$section] ?? false) @include('career.resume.pdf-section') @endif
            @endforeach
        </td>
    </tr></table>
@else
    @foreach($theme['order'] as $section)
        @if($has[$section] ?? false) @include('career.resume.pdf-section') @endif
    @endforeach
@endif
</body></html>
