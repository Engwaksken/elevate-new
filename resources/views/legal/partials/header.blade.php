{{-- Shared heading block for the public legal pages. Expects $heading and $intro. --}}
<header class="legal-header">
    <div class="eyebrow">Legal</div>
    <h1>{{ $heading }}</h1>
    <p class="legal-meta">
        <span><i class="fas fa-calendar-day" aria-hidden="true"></i> Last updated: {{ config('legal.last_updated', '30 September 2026') }}</span>
        <span><i class="fas fa-code-branch" aria-hidden="true"></i> Version {{ config('app.policy_version', '1.0') }}</span>
    </p>
    <p class="lead">{{ $intro }}</p>
    <nav class="legal-switch" aria-label="Legal documents">
        <a href="{{ route('legal.privacy') }}" class="btn btn-sm {{ request()->routeIs('legal.privacy') ? 'btn-primary' : 'btn-outline' }}" @if(request()->routeIs('legal.privacy')) aria-current="page" @endif>
            <i class="fas fa-user-shield" aria-hidden="true"></i> Privacy Policy
        </a>
        <a href="{{ route('legal.terms') }}" class="btn btn-sm {{ request()->routeIs('legal.terms') ? 'btn-primary' : 'btn-outline' }}" @if(request()->routeIs('legal.terms')) aria-current="page" @endif>
            <i class="fas fa-file-contract" aria-hidden="true"></i> Terms of Use
        </a>
    </nav>
</header>
