@extends('layouts.admin')

@section('title', 'My Appraisals | ElevateHer360')

@section('content')
@php
    $appraisalCollection = collect($appraisals instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $appraisals->items()
        : $appraisals);

    $totalAppraisals = $appraisals instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $appraisals->total()
        : $appraisalCollection->count();

    $draftCount = $appraisalCollection->whereIn('status', ['goal_setting','self_assessment'])->count();
    $reviewCount = $appraisalCollection->whereIn('status', ['manager_review','calibration'])->count();
    $completedCount = $appraisalCollection->where('status', 'completed')->count();
@endphp

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Human Resources</span>
        <h1>My Appraisals</h1>
        <p>Complete self assessments, review progress and track your appraisal workflow.</p>
    </div>

    <div class="admin-page-actions">
        @if(Route::has('staff.appraisals.team'))
            <a href="{{ route('staff.appraisals.team') }}" class="btn btn-outline">
                <i class="fas fa-users"></i>
                Team Reviews
                @if(($teamCount ?? 0) > 0)
                    <span class="badge">{{ $teamCount }}</span>
                @endif
            </a>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
@endif

<div class="admin-stats-grid compact">
    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-clipboard-list"></i></span>
        <div><small>Total Appraisals</small><strong>{{ $totalAppraisals }}</strong></div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-pen-to-square"></i></span>
        <div><small>Self Assessment</small><strong>{{ $draftCount }}</strong></div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-user-check"></i></span>
        <div><small>In Review</small><strong>{{ $reviewCount }}</strong></div>
    </div>

    <div class="admin-stat">
        <span class="admin-stat-icon"><i class="fas fa-circle-check"></i></span>
        <div><small>Completed</small><strong>{{ $completedCount }}</strong></div>
    </div>
</div>

<div class="admin-panel">
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Cycle</th>
                    <th>Template</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Performance</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appraisalCollection as $appraisal)
                    <tr>
                        <td>
                            <strong>{{ data_get($appraisal, 'cycle.name') ?? 'Appraisal' }}</strong>
                        </td>

                        <td>{{ data_get($appraisal, 'kpiTemplate.name') ?? '—' }}</td>

                        <td>
                            <span class="status-badge">
                                {{ ucwords(str_replace('_',' ',(string) $appraisal->status)) }}
                            </span>
                        </td>

                        <td>
                            {{ number_format((float)($appraisal->completion_percent ?? 0), 1) }}%
                        </td>

                        <td>
                            {{ $appraisal->performance_percent !== null
                                ? number_format((float)$appraisal->performance_percent,1).'%'
                                : '—' }}
                        </td>

                        <td class="text-end">
                            @if(Route::has('staff.appraisals.show'))
                                <a href="{{ route('staff.appraisals.show',$appraisal) }}"
                                   class="btn btn-sm btn-outline">
                                    <i class="fas fa-eye"></i>
                                    Open
                                </a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="admin-empty compact">
                                <i class="fas fa-clipboard"></i>
                                <p>No appraisals have been assigned to you yet.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($appraisals instanceof \Illuminate\Contracts\Pagination\Paginator)
        <div class="admin-pagination">
            {{ $appraisals->links() }}
        </div>
    @endif
</div>
@endsection
