@props(['role' => 'participant'])
@php
    $rsUser = auth()->user();
    $rsMeta = \App\Support\RoleShell::meta($role);
    $rsHome = \App\Support\RoleShell::url($rsMeta['home']) ?? route('home');
    $rsNav = \App\Support\RoleShell::navigation($role);
    $rsLogoPath = app(\App\Services\SettingsService::class)->get('branding.logo_path');
    $rsLogoUrl = $rsLogoPath && Route::has('branding.asset')
        ? route('branding.asset', ['type' => 'logo', 'v' => md5((string) $rsLogoPath)])
        : null;
@endphp
<aside id="participantSidebar" class="participant-sidebar role-sidebar role-sidebar--{{ $role }}" aria-label="{{ $rsMeta['label'] }} navigation">
    <div class="ps-brand">
        <a href="{{ $rsHome }}" class="ps-brand-link">
            @if($rsLogoUrl)
                <img src="{{ $rsLogoUrl }}" alt="ElevateHer360" class="ps-mark-logo">
            @else
                <span class="ps-mark">E360</span>
            @endif
            <span class="ps-brand-copy">
                <strong>ElevateHer360</strong>
                <small>Women in Technology Uganda</small>
            </span>
        </a>
        <button type="button" class="ps-close" data-sidebar-close aria-label="Close navigation">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <div class="ps-user">
        <span class="ps-avatar" aria-hidden="true">{{ strtoupper(substr($rsUser->name ?? $rsUser->email ?? 'U', 0, 1)) }}</span>
        <span class="ps-user-copy">
            <strong>{{ $rsUser->name ?? $rsMeta['label'] }}</strong>
            <small>{{ $rsUser->email }}</small>
            <x-role-badge :role="$role" tone="dark" class="ps-user-role" />
        </span>
    </div>

    <nav class="ps-nav" aria-label="{{ $rsMeta['label'] }} menu">
        @foreach($rsNav as $group => $items)
            <span class="ps-label">{{ $group }}</span>
            @foreach($items as $item)
                <a href="{{ $item['url'] }}" class="ps-link {{ $item['is_active'] ? 'active' : '' }}" @if($item['is_active']) aria-current="page" @endif>
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="ps-footer">
        <form method="POST" action="{{ route($role !== 'participant' && Route::has('partners.logout') ? 'partners.logout' : 'logout') }}" class="ps-logout-form">
            @csrf
            <button type="submit" class="ps-logout">
                <i class="fas fa-right-from-bracket" aria-hidden="true"></i>
                <span>Sign Out</span>
            </button>
        </form>
    </div>
</aside>
