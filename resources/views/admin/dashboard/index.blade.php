@extends('layouts.admin')
@section('title','Dashboard | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Administration</span>
        <h1>ElevateHer360 Dashboard</h1>
        <p>Operational overview across participants, programmes, learning, planning, MEAL, procurement and assets.</p>
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

<div class="admin-stats-grid compact">
@php
$cards=[
['participants','Participants','fa-users'],
['staff','Staff','fa-user-tie'],
['programmes','Active Programmes','fa-diagram-project'],
['courses','Published Courses','fa-graduation-cap'],
['enrolments','Enrolments','fa-user-graduate'],
['completed','Completed Learners','fa-circle-check'],
['open_tasks','Open Tasks','fa-list-check'],
['overdue_tasks','Overdue Tasks','fa-triangle-exclamation'],
['open_deliverables','Open Deliverables','fa-box'],
['approved_workplans','Approved Workplans','fa-calendar-check'],
['pending_indicator_results','Pending MEAL Results','fa-chart-line'],
['open_purchase_requests','Open Purchase Requests','fa-cart-shopping'],
['active_assets','Active Assets','fa-boxes-stacked'],
];
@endphp

@foreach($cards as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-dashboard-grid">
    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Recent Tasks</h2>
                <p>Latest tasks requiring operational attention.</p>
            </div>
            @if(Route::has('admin.tasks.index'))
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
                        <td><span class="status-chip {{ $task->status }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                        <td>{{ number_format((float)$task->progress_percent,0) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No tasks available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel">
        <div class="admin-panel-head">
            <div>
                <h2>Recent Purchase Requests</h2>
                <p>Latest procurement activity.</p>
            </div>
            @if(Route::has('admin.procurement.requests.index'))
            <a href="{{ route('admin.procurement.requests.index') }}" class="btn btn-outline btn-sm">View All</a>
            @endif
        </div>

        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Request</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($recentRequests as $request)
                    <tr>
                        <td><strong>{{ $request->request_number }}</strong></td>
                        <td>{{ $request->currency ?: 'UGX' }} {{ number_format((float)$request->estimated_total,0) }}</td>
                        <td><span class="status-chip {{ $request->status }}">{{ ucfirst(str_replace('_',' ',$request->status)) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3">No purchase requests available.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Quick Access</h2>
            <p>Open frequently used administration areas.</p>
        </div>
    </div>

    <div class="admin-quick-grid">
        @foreach([
            ['admin.workplans.index','Workplans','fa-calendar-check'],
            ['admin.tasks.index','Tasks','fa-list-check'],
            ['admin.deliverables.index','Deliverables','fa-box'],
            ['admin.indicators.index','Indicators','fa-chart-line'],
            ['admin.procurement.requests.index','Procurement','fa-cart-shopping'],
            ['admin.assets.index','Assets','fa-boxes-stacked'],
            ['admin.hr.employees.index','Employees','fa-users-gear'],
            ['admin.settings.index','Settings','fa-gears'],
        ] as [$route,$label,$icon])
            @if(Route::has($route))
            <a href="{{ route($route) }}" class="admin-quick-card">
                <i class="fas {{ $icon }}"></i>
                <span>{{ $label }}</span>
            </a>
            @endif
        @endforeach
    </div>
</div>
@endsection
