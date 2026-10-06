@props(['role' => 'participant'])
@php($rtMeta = \App\Support\RoleShell::meta($role))
<header class="role-topbar">
    <div class="role-topbar__context">
        <x-role-badge :role="$role" />
        <span class="role-topbar__hint">{{ $rtMeta['label'] }} workspace</span>
    </div>
    <div class="role-topbar__actions">
        <a href="{{ route('home') }}" class="role-topbar__link"><i class="fas fa-house" aria-hidden="true"></i><span>Public site</span></a>
        @if(Route::has('notifications.index'))
            <a href="{{ route('notifications.index') }}" class="role-topbar__link"><i class="fas fa-bell" aria-hidden="true"></i><span>Notifications</span></a>
        @endif
    </div>
</header>
