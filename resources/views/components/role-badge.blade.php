@props(['role' => 'participant', 'tone' => 'light'])
@php($roleMeta = \App\Support\RoleShell::meta($role))
<span {{ $attributes->class(['role-badge', 'role-badge--'.$role, 'role-badge--on-dark' => $tone === 'dark']) }}>
    <i class="fas {{ $roleMeta['icon'] }}" aria-hidden="true"></i>
    <span>{{ $roleMeta['label'] }}</span>
</span>
