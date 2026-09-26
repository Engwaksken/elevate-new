{{-- Hidden route-aware source. Nothing visible is rendered outside the main sidebar. --}}
<template id="eh-overview-sidebar-template" data-eh-overview-sidebar-template>
    @if(Route::has('admin.executive-dashboard'))
        <a href="{{ route('admin.executive-dashboard') }}"
           data-eh-sidebar-group="overview"
           data-eh-sidebar-generated="executive-dashboard"
           class="eh-sidebar-injected-link {{ request()->routeIs('admin.executive-dashboard') ? 'active' : '' }}">
            <i class="fas fa-chart-pie"></i>
            <span>Executive Dashboard</span>
        </a>
    @endif
</template>
