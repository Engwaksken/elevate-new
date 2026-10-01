@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')

@section('content')
@php
    $isEmployee = (int) $appraisal->employee?->user_id === (int) auth()->id();
    $isSupervisor = (int) $appraisal->manager_user_id === (int) auth()->id();

    $employeeEditableStatuses = [
        'draft',
        'in_progress',
        'returned_for_revision',
        'goal_setting',
        'self_assessment',
    ];

    $canEmployeeEdit = $isEmployee
        && ! $appraisal->locked_at
        && in_array($appraisal->status, $employeeEditableStatuses, true);

    $canSupervisorReview = $isSupervisor
        && ! $appraisal->locked_at
        && in_array($appraisal->status, ['submitted', 'supervisor_review'], true);

    $canRecordMeeting = $isSupervisor
        && ! $appraisal->locked_at
        && in_array($appraisal->status, ['meeting_pending', 'meeting_completed'], true);
@endphp

<div class="container-fluid py-4">
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">{{ $appraisal->cycle?->name ?? 'Staff Appraisal' }}</h1>
            <div class="text-muted">
                {{ $appraisal->employee?->user?->name ?? 'Employee' }}
                · Supervisor: {{ $appraisal->manager?->name ?? 'Not assigned' }}
            </div>
        </div>

        <div class="text-end">
            <span class="badge bg-primary fs-6">
                {{ ucwords(str_replace('_', ' ', $appraisal->status)) }}
            </span>

            @if($appraisal->locked_at)
                <div class="text-danger small mt-1">
                    <i class="fas fa-lock me-1"></i>Locked
                </div>
            @endif
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100 border-start border-primary border-4">
                <div class="card-body">
                    <div class="text-muted small">Completion</div>
                    <div class="fs-4 fw-bold">{{ number_format((float) $appraisal->completion_percent, 1) }}%</div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-start border-success border-4">
                <div class="card-body">
                    <div class="text-muted small">Agreed Performance</div>
                    <div class="fs-4 fw-bold">
                        {{ $appraisal->performance_percent !== null ? number_format((float) $appraisal->performance_percent, 1).'%' : 'Pending' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100 border-start border-secondary border-4">
                <div class="card-body">
                    <div class="text-muted small">Appraisal ID</div>
                    <div class="fs-4 fw-bold">#{{ $appraisal->id }}</div>
                </div>
            </div>
        </div>
    </div>

    @if($canEmployeeEdit)
        <form method="POST" action="{{ route('staff.performance.employee.save', $appraisal) }}" id="employee-appraisal-form">
            @csrf
            @method('PUT')

            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h2 class="h5 mb-0">Employee Reflection</h2>
                </div>

                <div class="card-body">
                    <div class="row g-3">
                        @foreach([
                            'achievements' => 'Achievements',
                            'challenges' => 'Challenges',
                            'support_required' => 'Support Required',
                            'learning_completed' => 'Learning Completed',
                            'development_needs' => 'Development Needs',
                            'employee_comments' => 'Overall Employee Comments',
                        ] as $name => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <textarea
                                    class="form-control"
                                    rows="4"
                                    name="{{ $name }}"
                                >{{ old($name, $appraisal->$name) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h5 mb-1">KRAs & KPIs</h2>
                    <div class="text-muted small">
                        KRA weights must total 100%. KPI weights inside each KRA must also total 100%.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-primary" id="add-kra">
                    <i class="fas fa-plus me-1"></i>Add KRA
                </button>
            </div>

            <div id="kra-list">
                @foreach($appraisal->kras as $kraIndex => $kra)
                    <div class="card mb-3 kra-card" data-kra-index="{{ $kraIndex }}">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <strong>KRA <span class="kra-number">{{ $kraIndex + 1 }}</span></strong>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-kra">
                                <i class="fas fa-trash me-1"></i>Remove KRA
                            </button>
                        </div>

                        <div class="card-body">
                            <input type="hidden" name="kras[{{ $kraIndex }}][id]" value="{{ $kra->id }}">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">KRA Title</label>
                                    <input
                                        class="form-control"
                                        name="kras[{{ $kraIndex }}][title]"
                                        value="{{ old("kras.$kraIndex.title", $kra->title) }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Weight %</label>
                                    <input
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="form-control"
                                        name="kras[{{ $kraIndex }}][weight]"
                                        value="{{ old("kras.$kraIndex.weight", $kra->weight) }}"
                                        required
                                    >
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Self Rating</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="5"
                                        step="0.1"
                                        class="form-control"
                                        name="kras[{{ $kraIndex }}][employee_rating]"
                                        value="{{ old("kras.$kraIndex.employee_rating", $kra->employee_rating) }}"
                                    >
                                </div>

                                <div class="col-md-2">
                                    <label class="form-label">Agreed</label>
                                    <input class="form-control" value="{{ $kra->agreed_rating ?? 'Pending' }}" disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" rows="2" name="kras[{{ $kraIndex }}][description]">{{ $kra->description }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Employee Comment</label>
                                    <textarea class="form-control" rows="2" name="kras[{{ $kraIndex }}][employee_comment]">{{ $kra->employee_comment }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Expected Result</label>
                                    <textarea class="form-control" rows="3" name="kras[{{ $kraIndex }}][expected_result]">{{ $kra->expected_result }}</textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Actual Result</label>
                                    <textarea class="form-control" rows="3" name="kras[{{ $kraIndex }}][actual_result]">{{ $kra->actual_result }}</textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                                <strong>KPIs</strong>
                                <button type="button" class="btn btn-sm btn-outline-secondary add-kpi">
                                    <i class="fas fa-plus me-1"></i>Add KPI
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table align-middle kpi-table">
                                    <thead>
                                        <tr>
                                            <th style="min-width:180px">KPI</th>
                                            <th style="min-width:120px">Target</th>
                                            <th style="min-width:120px">Actual</th>
                                            <th style="min-width:100px">Weight %</th>
                                            <th style="min-width:90px">Self</th>
                                            <th style="min-width:100px">Supervisor</th>
                                            <th style="min-width:90px">Agreed</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody class="kpi-list">
                                        @foreach($kra->kpis as $kpiIndex => $kpi)
                                            <tr class="kpi-row">
                                                <td>
                                                    <input type="hidden" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][id]" value="{{ $kpi->id }}">
                                                    <input class="form-control" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][title]" value="{{ $kpi->title }}" required>
                                                </td>
                                                <td><input class="form-control" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][target]" value="{{ $kpi->target }}"></td>
                                                <td><input class="form-control" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][actual_achievement]" value="{{ $kpi->actual_achievement }}"></td>
                                                <td><input type="number" min="0" max="100" step="0.01" class="form-control" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][weight]" value="{{ $kpi->weight }}" required></td>
                                                <td><input type="number" min="1" max="5" step="0.1" class="form-control" name="kras[{{ $kraIndex }}][kpis][{{ $kpiIndex }}][employee_score]" value="{{ $kpi->employee_score }}"></td>
                                                <td>{{ $kpi->supervisor_score ?? '—' }}</td>
                                                <td><strong>{{ $kpi->agreed_score ?? '—' }}</strong></td>
                                                <td><button type="button" class="btn btn-sm btn-outline-danger remove-kpi"><i class="fas fa-times"></i></button></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button class="btn btn-primary">
                <i class="fas fa-save me-1"></i>Save Draft
            </button>
        </form>

        <form method="POST" class="d-inline-block mt-3" action="{{ route('staff.performance.employee.submit', $appraisal) }}">
            @csrf
            <button class="btn btn-success">
                <i class="fas fa-paper-plane me-1"></i>Submit to Supervisor
            </button>
        </form>
    @endif

    @if($isEmployee && ! $canEmployeeEdit)
        <div class="alert alert-info">
            Your KRA/KPI section is read-only while the appraisal is with the supervisor or in the confirmation process.
        </div>
    @endif

    @if($canSupervisorReview)
        <div class="card mt-4 mb-3">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Supervisor Review</h2>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('staff.performance.supervisor.save', $appraisal) }}">
                    @csrf
                    @method('PUT')

                    @foreach($appraisal->kras as $kra)
                        <div class="border rounded p-3 mb-3">
                            <h3 class="h6">{{ $kra->title }}</h3>

                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-3">
                                    <label class="form-label">Employee Rating</label>
                                    <input class="form-control" value="{{ $kra->employee_rating ?? '—' }}" disabled>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Supervisor Rating</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="5"
                                        step="0.1"
                                        class="form-control"
                                        name="kras[{{ $kra->id }}][supervisor_rating]"
                                        value="{{ $kra->supervisor_rating }}"
                                    >
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Supervisor Comment</label>
                                    <input
                                        class="form-control"
                                        name="kras[{{ $kra->id }}][supervisor_comment]"
                                        value="{{ $kra->supervisor_comment }}"
                                    >
                                </div>
                            </div>

                            @foreach($kra->kpis as $kpi)
                                <div class="row g-2 border-top py-2 align-items-end">
                                    <div class="col-md-5">
                                        <strong>{{ $kpi->title }}</strong>
                                        <div class="small text-muted">Self score: {{ $kpi->employee_score ?? '—' }}</div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">Supervisor Score</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="5"
                                            step="0.1"
                                            class="form-control"
                                            name="kras[{{ $kra->id }}][kpis][{{ $kpi->id }}][supervisor_score]"
                                            value="{{ $kpi->supervisor_score }}"
                                        >
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Comment</label>
                                        <input
                                            class="form-control"
                                            name="kras[{{ $kra->id }}][kpis][{{ $kpi->id }}][supervisor_comment]"
                                            value="{{ $kpi->supervisor_comment }}"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <label class="form-label">Overall Supervisor Comments</label>
                    <textarea class="form-control mb-3" rows="4" name="manager_comments">{{ $appraisal->manager_comments }}</textarea>

                    <label class="form-label">Development Plan</label>
                    <textarea class="form-control mb-3" rows="4" name="development_plan">{{ $appraisal->development_plan }}</textarea>

                    <button class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Supervisor Review
                    </button>
                </form>

                <div class="row g-3 mt-2">
                    <div class="col-lg-6">
                        <form method="POST" action="{{ route('staff.performance.return', $appraisal) }}">
                            @csrf
                            <label class="form-label">Return for Revision</label>
                            <textarea class="form-control mb-2" name="return_comment" rows="2" required placeholder="Explain what the employee should revise"></textarea>
                            <button class="btn btn-outline-danger">
                                <i class="fas fa-rotate-left me-1"></i>Return to Employee
                            </button>
                        </form>
                    </div>

                    <div class="col-lg-6 d-flex align-items-end">
                        <form method="POST" action="{{ route('staff.performance.review.complete', $appraisal) }}">
                            @csrf
                            <button class="btn btn-success">
                                <i class="fas fa-check me-1"></i>Complete Review & Proceed to Meeting
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card mt-4">
        <div class="card-header bg-white">
            <h2 class="h5 mb-0">Appraisal Meeting & Agreed Scores</h2>
        </div>

        <div class="card-body">
            @if($canRecordMeeting)
                <form method="POST" action="{{ route('staff.performance.meeting.save', $appraisal) }}">
                    @csrf
                    @method('PUT')

                    @foreach($appraisal->kras as $kra)
                        <div class="border rounded p-3 mb-3">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-4">
                                    <strong>{{ $kra->title }}</strong>
                                </div>
                                <div class="col-md-2">Self: {{ $kra->employee_rating ?? '—' }}</div>
                                <div class="col-md-2">Supervisor: {{ $kra->supervisor_rating ?? '—' }}</div>
                                <div class="col-md-4">
                                    <label class="form-label">Agreed Rating</label>
                                    <input
                                        type="number"
                                        min="1"
                                        max="5"
                                        step="0.1"
                                        class="form-control"
                                        name="kras[{{ $kra->id }}][agreed_rating]"
                                        value="{{ $kra->agreed_rating }}"
                                    >
                                </div>
                            </div>

                            @foreach($kra->kpis as $kpi)
                                <div class="row g-2 border-top mt-2 pt-2 align-items-end">
                                    <div class="col-md-5">
                                        <strong>{{ $kpi->title }}</strong>
                                    </div>
                                    <div class="col-md-2">Self: {{ $kpi->employee_score ?? '—' }}</div>
                                    <div class="col-md-2">Supervisor: {{ $kpi->supervisor_score ?? '—' }}</div>
                                    <div class="col-md-3">
                                        <label class="form-label">Agreed Score</label>
                                        <input
                                            type="number"
                                            min="1"
                                            max="5"
                                            step="0.1"
                                            class="form-control"
                                            name="kras[{{ $kra->id }}][kpis][{{ $kpi->id }}][agreed_score]"
                                            value="{{ $kpi->agreed_score }}"
                                        >
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endforeach

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Meeting Date & Time</label>
                            <input
                                type="datetime-local"
                                class="form-control"
                                name="meeting_at"
                                value="{{ optional($appraisal->meeting?->meeting_at)->format('Y-m-d\TH:i') }}"
                                required
                            >
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Venue / Online Link</label>
                            <input class="form-control" name="venue" value="{{ $appraisal->meeting?->venue }}">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Participants</label>
                            <textarea class="form-control" rows="2" name="participants">{{ $appraisal->meeting?->participants }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Discussion Notes</label>
                            <textarea class="form-control" rows="4" name="discussion_notes">{{ $appraisal->meeting?->discussion_notes }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Disagreements / Outstanding Issues</label>
                            <textarea class="form-control" rows="4" name="disagreements">{{ $appraisal->meeting?->disagreements }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Agreed Actions</label>
                            <textarea class="form-control" rows="4" name="agreed_actions">{{ $appraisal->meeting?->agreed_actions }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Development Commitments</label>
                            <textarea class="form-control" rows="4" name="development_commitments">{{ $appraisal->meeting?->development_commitments }}</textarea>
                        </div>
                    </div>

                    <button class="btn btn-primary mt-3">
                        <i class="fas fa-handshake me-1"></i>Save Meeting & Agreed Scores
                    </button>
                </form>
            @elseif($appraisal->meeting)
                <div class="row g-3">
                    <div class="col-md-6"><strong>Meeting:</strong> {{ optional($appraisal->meeting->meeting_at)->format('d M Y H:i') }}</div>
                    <div class="col-md-6"><strong>Venue:</strong> {{ $appraisal->meeting->venue ?: '—' }}</div>
                    <div class="col-12"><strong>Discussion:</strong><br>{{ $appraisal->meeting->discussion_notes ?: '—' }}</div>
                    <div class="col-12"><strong>Agreed actions:</strong><br>{{ $appraisal->meeting->agreed_actions ?: '—' }}</div>
                </div>
            @else
                <div class="text-muted">The meeting becomes available after the supervisor completes the review.</div>
            @endif
        </div>
    </div>

    @if($isEmployee && $appraisal->status === 'meeting_completed')
        <form method="POST" class="card mt-4" action="{{ route('staff.performance.employee.confirm', $appraisal) }}">
            @csrf
            <div class="card-body">
                <h2 class="h5">Employee Final Confirmation</h2>
                <p class="text-muted">Confirm that the meeting took place and that the agreed scores reflect the discussion.</p>
                <textarea class="form-control mb-3" name="comment" rows="3" placeholder="Optional final employee comment">{{ $appraisal->employee_final_comment }}</textarea>
                <button class="btn btn-success">Confirm Agreed Appraisal</button>
            </div>
        </form>
    @endif

    @if($isSupervisor && $appraisal->status === 'employee_confirmation')
        <form method="POST" class="card mt-4" action="{{ route('staff.performance.supervisor.confirm', $appraisal) }}">
            @csrf
            <div class="card-body">
                <h2 class="h5">Supervisor Final Confirmation</h2>
                <p class="text-muted">The employee has confirmed the appraisal. Complete the supervisor confirmation.</p>
                <textarea class="form-control mb-3" name="comment" rows="3" placeholder="Optional final supervisor comment">{{ $appraisal->supervisor_final_comment }}</textarea>
                <button class="btn btn-success">Confirm & Complete Appraisal</button>
            </div>
        </form>
    @endif

    @if($appraisal->statusHistory->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header bg-white">
                <h2 class="h5 mb-0">Workflow History</h2>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>From</th>
                                <th>To</th>
                                <th>Comment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($appraisal->statusHistory->sortByDesc('created_at') as $history)
                                <tr>
                                    <td>{{ optional($history->created_at)->format('d M Y H:i') }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $history->from_status ?? '')) }}</td>
                                    <td>{{ ucwords(str_replace('_', ' ', $history->to_status ?? '')) }}</td>
                                    <td>{{ $history->comment }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

@if($canEmployeeEdit)
    <template id="kra-template">
        <div class="card mb-3 kra-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <strong>KRA <span class="kra-number"></span></strong>
                <button type="button" class="btn btn-sm btn-outline-danger remove-kra">
                    <i class="fas fa-trash me-1"></i>Remove KRA
                </button>
            </div>

            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">KRA Title</label>
                        <input class="form-control kra-title" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Weight %</label>
                        <input type="number" min="0" max="100" step="0.01" class="form-control kra-weight" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Self Rating</label>
                        <input type="number" min="1" max="5" step="0.1" class="form-control kra-rating">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Description</label>
                        <textarea class="form-control kra-description" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Employee Comment</label>
                        <textarea class="form-control kra-comment" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected Result</label>
                        <textarea class="form-control kra-expected" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Actual Result</label>
                        <textarea class="form-control kra-actual" rows="3"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
                    <strong>KPIs</strong>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-kpi">
                        <i class="fas fa-plus me-1"></i>Add KPI
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>KPI</th>
                                <th>Target</th>
                                <th>Actual</th>
                                <th>Weight %</th>
                                <th>Self</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody class="kpi-list"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </template>

    <template id="kpi-template">
        <tr class="kpi-row">
            <td><input class="form-control kpi-title" required></td>
            <td><input class="form-control kpi-target"></td>
            <td><input class="form-control kpi-actual"></td>
            <td><input type="number" min="0" max="100" step="0.01" class="form-control kpi-weight" required></td>
            <td><input type="number" min="1" max="5" step="0.1" class="form-control kpi-score"></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger remove-kpi"><i class="fas fa-times"></i></button></td>
        </tr>
    </template>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const kraList = document.getElementById('kra-list');
                const addKraButton = document.getElementById('add-kra');
                const kraTemplate = document.getElementById('kra-template');
                const kpiTemplate = document.getElementById('kpi-template');

                if (!kraList || !addKraButton || !kraTemplate || !kpiTemplate) {
                    return;
                }

                function reindex() {
                    const kraCards = kraList.querySelectorAll('.kra-card');

                    kraCards.forEach((card, kraIndex) => {
                        card.dataset.kraIndex = kraIndex;

                        const number = card.querySelector('.kra-number');
                        if (number) number.textContent = kraIndex + 1;

                        const existingId = card.querySelector('input[type="hidden"][name*="[id]"]');
                        if (existingId) existingId.name = `kras[${kraIndex}][id]`;

                        const selectors = {
                            '.kra-title': 'title',
                            '.kra-weight': 'weight',
                            '.kra-rating': 'employee_rating',
                            '.kra-description': 'description',
                            '.kra-comment': 'employee_comment',
                            '.kra-expected': 'expected_result',
                            '.kra-actual': 'actual_result'
                        };

                        Object.entries(selectors).forEach(([selector, field]) => {
                            const input = card.querySelector(selector);
                            if (input) input.name = `kras[${kraIndex}][${field}]`;
                        });

                        card.querySelectorAll('.kpi-row').forEach((row, kpiIndex) => {
                            const id = row.querySelector('input[type="hidden"][name*="[id]"]');
                            if (id) id.name = `kras[${kraIndex}][kpis][${kpiIndex}][id]`;

                            const kpiSelectors = {
                                '.kpi-title': 'title',
                                '.kpi-target': 'target',
                                '.kpi-actual': 'actual_achievement',
                                '.kpi-weight': 'weight',
                                '.kpi-score': 'employee_score'
                            };

                            Object.entries(kpiSelectors).forEach(([selector, field]) => {
                                const input = row.querySelector(selector);
                                if (input) input.name = `kras[${kraIndex}][kpis][${kpiIndex}][${field}]`;
                            });
                        });
                    });
                }

                function prepareExistingRows() {
                    kraList.querySelectorAll('.kra-card').forEach(card => {
                        const title = card.querySelector('input[name$="[title]"]');
                        const weight = card.querySelector('input[name$="[weight]"]');
                        const rating = card.querySelector('input[name$="[employee_rating]"]');
                        const description = card.querySelector('textarea[name$="[description]"]');
                        const comment = card.querySelector('textarea[name$="[employee_comment]"]');
                        const expected = card.querySelector('textarea[name$="[expected_result]"]');
                        const actual = card.querySelector('textarea[name$="[actual_result]"]');

                        if (title) title.classList.add('kra-title');
                        if (weight) weight.classList.add('kra-weight');
                        if (rating) rating.classList.add('kra-rating');
                        if (description) description.classList.add('kra-description');
                        if (comment) comment.classList.add('kra-comment');
                        if (expected) expected.classList.add('kra-expected');
                        if (actual) actual.classList.add('kra-actual');

                        card.querySelectorAll('.kpi-row').forEach(row => {
                            const inputs = row.querySelectorAll('input:not([type="hidden"])');
                            if (inputs[0]) inputs[0].classList.add('kpi-title');
                            if (inputs[1]) inputs[1].classList.add('kpi-target');
                            if (inputs[2]) inputs[2].classList.add('kpi-actual');
                            if (inputs[3]) inputs[3].classList.add('kpi-weight');
                            if (inputs[4]) inputs[4].classList.add('kpi-score');
                        });
                    });
                }

                function addKpi(card) {
                    const row = kpiTemplate.content.firstElementChild.cloneNode(true);
                    card.querySelector('.kpi-list').appendChild(row);
                    reindex();
                }

                addKraButton.addEventListener('click', function () {
                    const card = kraTemplate.content.firstElementChild.cloneNode(true);
                    kraList.appendChild(card);
                    addKpi(card);
                    reindex();
                });

                kraList.addEventListener('click', function (event) {
                    const removeKra = event.target.closest('.remove-kra');
                    if (removeKra) {
                        removeKra.closest('.kra-card').remove();
                        reindex();
                        return;
                    }

                    const addKpiButton = event.target.closest('.add-kpi');
                    if (addKpiButton) {
                        addKpi(addKpiButton.closest('.kra-card'));
                        return;
                    }

                    const removeKpi = event.target.closest('.remove-kpi');
                    if (removeKpi) {
                        removeKpi.closest('.kpi-row').remove();
                        reindex();
                    }
                });

                prepareExistingRows();
                reindex();
            });
        </script>
    @endpush
@endif
@endsection
