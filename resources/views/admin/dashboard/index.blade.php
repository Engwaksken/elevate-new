@extends('layouts.admin')
@section('title','Dashboard | ElevateHer360 Administration')

@section('content')
@php
    $dashboardUser = auth()->user();

    $dashboardCan = static function (string|array $permissions) use ($dashboardUser): bool {
        if (! $dashboardUser) {
            return false;
        }

        return $dashboardUser->isSuperAdmin()
            || $dashboardUser->hasAnyPermission((array) $permissions);
    };

    $cards = [
        ['participants', 'Participants', 'fa-users'],
        ['staff', 'Staff', 'fa-user-tie'],
        ['programmes', 'Active Programmes', 'fa-diagram-project'],
        ['courses', 'Published Courses', 'fa-graduation-cap'],
        ['enrolments', 'Enrolments', 'fa-user-graduate'],
        ['completed', 'Completed Learners', 'fa-circle-check'],
        ['open_tasks', 'Open Tasks', 'fa-list-check'],
        ['overdue_tasks', 'Overdue Tasks', 'fa-triangle-exclamation'],
        ['open_deliverables', 'Open Deliverables', 'fa-box'],
        ['approved_workplans', 'Approved Workplans', 'fa-calendar-check'],
        ['pending_indicator_results', 'Pending MEAL Results', 'fa-chart-line'],
        ['open_purchase_requests', 'Open Purchase Requests', 'fa-cart-shopping'],
        ['active_assets', 'Active Assets', 'fa-boxes-stacked'],
    ];

    $visibleCards = collect($cards)->filter(
        fn (array $card): bool => array_key_exists($card[0], $stats)
    );

    $quickLinks = [
        ['admin.programmes.index', 'Programmes', 'fa-diagram-project', ['programmes.manage']],
        ['admin.elearning.courses.index', 'Courses', 'fa-graduation-cap', ['courses.edit']],
        ['admin.elearning.enrolments.index', 'Enrolments', 'fa-user-graduate', ['students.view', 'students.edit']],
        ['admin.workplans.index', 'Workplans', 'fa-calendar-check', ['workplans.view', 'workplans.edit', 'workplans.approve']],
        ['admin.tasks.index', 'Tasks', 'fa-list-check', ['tasks.manage']],
        ['admin.deliverables.index', 'Deliverables', 'fa-box', ['tasks.manage']],
        ['admin.indicators.index', 'Indicators', 'fa-chart-line', ['indicators.view', 'indicators.manage', 'indicators.verify']],
        ['admin.purchase-requests.index', 'Procurement', 'fa-cart-shopping', ['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive']],
        ['admin.assets.index', 'Assets', 'fa-boxes-stacked', ['assets.view', 'assets.manage', 'assets.dispose']],
        ['admin.hr.employees.index', 'Employees', 'fa-users-gear', ['hr.view', 'hr.manage']],
        ['admin.settings.index', 'Settings', 'fa-gears', ['settings.manage']],
    ];

    $visibleQuickLinks = collect($quickLinks)->filter(
        fn (array $item): bool => Route::has($item[0]) && $dashboardCan($item[3])
    );
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Administration</span>
        <h1>ElevateHer360 Dashboard</h1>
        <p>Your dashboard only shows information and actions available to your assigned role and permissions.</p>
    </div>

    <div class="admin-page-actions">
        @if(Route::has('admin.search'))
            <form method="GET" action="{{ route('admin.search') }}" class="search-box" style="min-width:320px">
                <i class="fas fa-magnifying-glass"></i>
                <input name="q" placeholder="Search across ElevateHer360...">
            </form>
        @endif
    </div>
</div>

@if($visibleCards->isNotEmpty())
    <div class="admin-stats-grid compact">
        @foreach($visibleCards as [$key, $label, $icon])
            <div class="admin-stat">
                <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
                <div>
                    <small>{{ $label }}</small>
                    <strong>{{ number_format((float) $stats[$key]) }}</strong>
                </div>
            </div>
        @endforeach
    </div>
@endif

@if(($dashboardAccess['tasks'] ?? false) || ($dashboardAccess['procurement'] ?? false))
    <div class="admin-dashboard-grid">
        @if($dashboardAccess['tasks'] ?? false)
            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Recent Tasks</h2>
                        <p>Latest tasks available to your role.</p>
                    </div>
                    @if(Route::has('admin.tasks.index') && $dashboardCan('tasks.manage'))
                        <a href="{{ route('admin.tasks.index') }}" class="btn btn-outline btn-sm">View All</a>
                    @endif
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Task</th><th>Due</th><th>Status</th><th>Progress</th></tr></thead>
                        <tbody>
                        @forelse($recentTasks as $task)
                            <tr>
                                <td><strong>{{ $task->title }}</strong></td>
                                <td>{{ optional($task->due_date)->format('d M Y') ?: '—' }}</td>
                                <td><span class="status-chip {{ $task->status }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span></td>
                                <td>{{ number_format((float) $task->progress_percent, 0) }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No tasks available.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        @if($dashboardAccess['procurement'] ?? false)
            <section class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Recent Purchase Requests</h2>
                        <p>Latest procurement activity available to your role.</p>
                    </div>
                    @if(Route::has('admin.purchase-requests.index') && $dashboardCan(['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive']))
                        <a href="{{ route('admin.purchase-requests.index') }}" class="btn btn-outline btn-sm">View All</a>
                    @endif
                </div>

                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Request</th><th>Total</th><th>Status</th></tr></thead>
                        <tbody>
                        @forelse($recentRequests as $request)
                            <tr>
                                <td><strong>{{ $request->request_number }}</strong></td>
                                <td>{{ $request->currency ?: 'UGX' }} {{ number_format((float) $request->estimated_total, 0) }}</td>
                                <td><span class="status-chip {{ $request->status }}">{{ ucfirst(str_replace('_', ' ', $request->status)) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No purchase requests available.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
@endif

@if($visibleQuickLinks->isNotEmpty())
    <div class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Quick Access</h2>
                <p>Only administration areas permitted for your role are shown.</p>
            </div>
        </div>

        <div class="admin-quick-grid">
            @foreach($visibleQuickLinks as [$route, $label, $icon, $permissions])
                <a href="{{ route($route) }}" class="admin-quick-card">
                    <i class="fas {{ $icon }}"></i>
                    <span>{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif

@if($visibleCards->isEmpty() && $visibleQuickLinks->isEmpty() && !($dashboardAccess['tasks'] ?? false) && !($dashboardAccess['procurement'] ?? false))
    <div class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>No dashboard modules assigned</h2>
                <p>Your staff account is active, but your current role does not yet have permissions for administration modules. Contact an administrator if you need additional access.</p>
            </div>
        </div>
    </div>
@endif
@endsection
