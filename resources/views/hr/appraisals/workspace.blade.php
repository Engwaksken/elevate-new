@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title', 'My Performance | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Performance</span>
        <h1>Appraisals &amp; Performance</h1>
        <p>Self assessment → supervisor review → appraisal meeting → agreed score → confirmations.</p>
    </div>
</div>

<div class="admin-stats-grid compact">
    @foreach([
        ['mine', 'My Appraisals', 'fa-user-pen'],
        ['team', 'My Team', 'fa-people-group'],
        ['action', 'Awaiting My Action', 'fa-hourglass-half'],
        ['completed', 'Completed', 'fa-circle-check'],
    ] as [$key, $label, $icon])
        <div class="admin-stat">
            <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
            <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div>
        </div>
    @endforeach
</div>

@foreach([
    ['My Appraisals', $mine, $myActionStatuses, 'No appraisal has been assigned to you yet.', false],
    ['My Team', $team, $teamActionStatuses, 'No team member appraisals are assigned to you.', true],
] as [$heading, $appraisals, $actionStatuses, $emptyText, $isTeam])
    <div class="admin-panel" style="margin-bottom:16px">
        <h2 style="margin:0 0 12px;font-size:1rem">{{ $heading }} <small style="color:var(--ad-muted,#667085);font-weight:500">({{ $appraisals->count() }})</small></h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>{{ $isTeam ? 'Employee' : 'Cycle' }}</th>
                        <th>{{ $isTeam ? 'Cycle' : 'Supervisor' }}</th>
                        <th>Status</th>
                        <th>Completion</th>
                        <th>Agreed Performance</th>
                        <th class="table-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($appraisals as $appraisal)
                        <tr>
                            <td><strong>{{ $isTeam ? ($appraisal->employee?->user?->name ?: '—') : ($appraisal->cycle?->name ?: 'Appraisal') }}</strong></td>
                            <td>{{ $isTeam ? ($appraisal->cycle?->name ?: '—') : ($appraisal->manager?->name ?: 'Not assigned') }}</td>
                            <td>
                                <span class="status-chip {{ $appraisal->status }}">{{ ucwords(str_replace('_', ' ', $appraisal->status)) }}</span>
                                @if(in_array($appraisal->status, $actionStatuses, true) && ! $appraisal->locked_at)
                                    <small class="admin-cell-hint">Needs your action</small>
                                @endif
                                @if($appraisal->locked_at)
                                    <small class="admin-cell-hint"><i class="fas fa-lock"></i> Locked</small>
                                @endif
                            </td>
                            <td>
                                <div class="table-progress">
                                    <span>{{ number_format((float) $appraisal->completion_percent, 0) }}%</span>
                                    <div class="progress-track"><i style="width:{{ min(100, (float) $appraisal->completion_percent) }}%"></i></div>
                                </div>
                            </td>
                            <td><strong>{{ $appraisal->performance_percent !== null ? number_format((float) $appraisal->performance_percent, 1).'%' : '—' }}</strong></td>
                            <td class="table-actions">
                                <a class="btn {{ $isTeam ? 'btn-outline' : 'btn-primary' }} btn-sm" href="{{ route('staff.performance.show', $appraisal) }}">{{ $isTeam ? 'Review' : 'Open' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="admin-empty">{{ $emptyText }}</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endforeach
@endsection
