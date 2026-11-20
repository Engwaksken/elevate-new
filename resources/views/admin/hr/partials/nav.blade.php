{{-- HR operations sub-navigation shared by all HR admin pages. --}}
@php
    $hrNav = [
        ['admin.hr.dashboard', 'Overview', 'fa-gauge', ['admin.hr.dashboard']],
        ['admin.hr.employees.index', 'Employee Directory', 'fa-id-badge', ['admin.hr.employees.*']],
        ['admin.hr.recruitment.index', 'Recruitment', 'fa-user-plus', ['admin.hr.recruitment.*']],
        ['admin.hr.policies.index', 'Policies', 'fa-scale-balanced', ['admin.hr.policies.*']],
        ['admin.hr.compliance.index', 'Documents & Compliance', 'fa-folder-open', ['admin.hr.compliance.*']],
        ['admin.hr.leave.index', 'Leave', 'fa-calendar-minus', ['admin.hr.leave.*']],
        ['admin.hr.appraisals.index', 'Appraisals', 'fa-clipboard-check', ['admin.hr.appraisals.*']],
        ['admin.hr.exits.index', 'Exits', 'fa-door-open', ['admin.hr.exits.*']],
    ];
@endphp
<nav class="admin-tab-nav hr-subnav" aria-label="HR operations" style="margin-bottom:18px">
    @foreach($hrNav as [$route, $label, $icon, $active])
        @if(Route::has($route))
            <a href="{{ route($route) }}" class="admin-tab-button {{ request()->routeIs(...$active) ? 'active' : '' }}" @if(request()->routeIs(...$active)) aria-current="page" @endif style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;white-space:nowrap">
                <i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $label }}
            </a>
        @endif
    @endforeach
</nav>
