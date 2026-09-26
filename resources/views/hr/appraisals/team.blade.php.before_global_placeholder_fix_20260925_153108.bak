@extends('layouts.admin')

@section('title', 'Team Appraisals | ElevateHer360')

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Human Resources</span>
        <h1>Team Appraisals</h1>
        <p>Review staff appraisals assigned to you as manager.</p>
    </div>

    <div class="admin-page-actions">
        @if(Route::has('staff.appraisals.index'))
            <a href="{{ route('staff.appraisals.index') }}" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i>
                My Appraisals
            </a>
        @endif
    </div>
</div>

<div class="admin-panel">
    <form method="GET" class="admin-filter-row">
        <div class="form-group">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">All statuses</option>
                @foreach([
                    'self_assessment'=>'Self Assessment',
                    'manager_review'=>'Manager Review',
                    'calibration'=>'Calibration',
                    'acknowledgement'=>'Acknowledgement',
                    'completed'=>'Completed',
                ] as $value=>$label)
                    <option value="{{ $value }}" @selected(request('status')===$value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-filter"></i>
            Apply
        </button>
    </form>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Cycle</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appraisals as $appraisal)
                    <tr>
                        <td>
                            <strong>{{ data_get($appraisal,'employee.user.name') ?? 'Staff Member' }}</strong>
                        </td>
                        <td>{{ data_get($appraisal,'cycle.name') ?? '—' }}</td>
                        <td>{{ ucwords(str_replace('_',' ',(string)$appraisal->status)) }}</td>
                        <td>{{ number_format((float)($appraisal->completion_percent ?? 0),1) }}%</td>
                        <td class="text-end">
                            <a href="{{ route('staff.appraisals.show',$appraisal) }}"
                               class="btn btn-sm btn-outline">
                                <i class="fas fa-eye"></i>
                                Review
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="admin-empty compact">
                                <p>No team appraisals found.</p>
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
