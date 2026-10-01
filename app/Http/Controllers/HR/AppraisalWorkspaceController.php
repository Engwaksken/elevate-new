<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\Task;
use App\Models\AppraisalKpi;
use App\Models\AppraisalKra;
use App\Models\AppraisalMeeting;
use App\Models\Employee;
use App\Services\HR\AppraisalScoreService;
use App\Services\HR\AppraisalWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppraisalWorkspaceController extends Controller
{
    public function index()
    {
        $employee = Employee::where('user_id', auth()->id())->first();

        $mine = $employee
            ? Appraisal::with(['cycle', 'manager'])
                ->where('employee_id', $employee->id)
                ->latest()
                ->get()
            : collect();

        $team = Appraisal::with(['cycle', 'employee.user'])
            ->where('manager_user_id', auth()->id())
            ->latest()
            ->get();

        $myActionStatuses = ['draft', 'in_progress', 'returned_for_revision', 'goal_setting', 'self_assessment', 'meeting_completed'];
        $teamActionStatuses = ['submitted', 'supervisor_review', 'meeting_pending', 'employee_confirmation'];

        $stats = [
            'mine' => $mine->count(),
            'team' => $team->count(),
            'action' => $mine->whereIn('status', $myActionStatuses)->count()
                + $team->whereIn('status', $teamActionStatuses)->count(),
            'completed' => $mine->where('status', 'completed')->count() + $team->where('status', 'completed')->count(),
        ];

        return view('hr.appraisals.workspace', compact('mine', 'team', 'stats', 'myActionStatuses', 'teamActionStatuses'));
    }

    public function show(Appraisal $appraisal, AppraisalScoreService $scores)
    {
        $this->authorise($appraisal);

        $appraisal = $scores->refresh($appraisal);
        $appraisal->load([
            'employee.user',
            'manager',
            'cycle',
            'kras.kpis',
            'competencies',
            'meeting',
            'statusHistory',
        ]);

        $competencyPercent = $scores->competencyPercent($appraisal);

        // Tasks the employee linked to these KPIs in My Tasks, shown as evidence when scoring.
        $kpiTasks = Task::whereIn('appraisal_kpi_id', $appraisal->kras->flatMap->kpis->pluck('id'))
            ->where('assigned_to', $appraisal->employee?->user_id)
            ->orderBy('due_date')
            ->get()
            ->groupBy('appraisal_kpi_id');

        return view('hr.appraisals.workflow', compact('appraisal', 'competencyPercent', 'kpiTasks'));
    }

    public function saveEmployee(
        Request $request,
        Appraisal $appraisal,
        AppraisalScoreService $scores,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->employee($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, [
                'draft',
                'in_progress',
                'returned_for_revision',
                'goal_setting',
                'self_assessment',
            ], true),
            422,
            'This appraisal can no longer be edited by the employee.'
        );

        $data = $request->validate([
            'achievements' => 'nullable|string|max:30000',
            'challenges' => 'nullable|string|max:30000',
            'support_required' => 'nullable|string|max:30000',
            'learning_completed' => 'nullable|string|max:30000',
            'development_needs' => 'nullable|string|max:30000',
            'employee_comments' => 'nullable|string|max:30000',

            'kras' => 'nullable|array',
            'kras.*.id' => 'nullable|integer',
            'kras.*.title' => 'required|string|max:255',
            'kras.*.description' => 'nullable|string|max:5000',
            'kras.*.weight' => 'required|numeric|min:0|max:100',
            'kras.*.expected_result' => 'nullable|string|max:10000',
            'kras.*.actual_result' => 'nullable|string|max:10000',
            'kras.*.employee_rating' => 'nullable|numeric|min:1|max:5',
            'kras.*.employee_comment' => 'nullable|string|max:5000',

            'kras.*.kpis' => 'nullable|array',
            'kras.*.kpis.*.id' => 'nullable|integer',
            'kras.*.kpis.*.title' => 'required|string|max:255',
            'kras.*.kpis.*.description' => 'nullable|string|max:5000',
            'kras.*.kpis.*.measurement_method' => 'nullable|string|max:500',
            'kras.*.kpis.*.target' => 'nullable|string|max:500',
            'kras.*.kpis.*.actual_achievement' => 'nullable|string|max:500',
            'kras.*.kpis.*.unit' => 'nullable|string|max:100',
            'kras.*.kpis.*.kpi_type' => 'nullable|string|max:50',
            'kras.*.kpis.*.weight' => 'required|numeric|min:0|max:100',
            'kras.*.kpis.*.employee_score' => 'nullable|numeric|min:1|max:5',
            'kras.*.kpis.*.evidence' => 'nullable|string|max:5000',
            'kras.*.kpis.*.employee_comment' => 'nullable|string|max:5000',

            'competencies_submitted' => 'nullable|boolean',
            'competencies' => 'nullable|array',
            'competencies.*.id' => 'nullable|integer',
            'competencies.*.name' => 'required|string|max:255',
            'competencies.*.weight' => 'required|numeric|min:0|max:100',
            'competencies.*.employee_rating' => 'nullable|numeric|min:1|max:5',
            'competencies.*.employee_comment' => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($appraisal, $data) {
            $appraisal->update(collect($data)->only([
                'achievements',
                'challenges',
                'support_required',
                'learning_completed',
                'development_needs',
                'employee_comments',
            ])->all());

            $keptKraIds = [];

            foreach (($data['kras'] ?? []) as $position => $kraData) {
                $kpiRows = $kraData['kpis'] ?? [];
                $kraId = $kraData['id'] ?? null;

                unset($kraData['id'], $kraData['kpis']);

                $employeeKraFields = collect($kraData)->only([
                    'title',
                    'description',
                    'expected_result',
                    'actual_result',
                    'weight',
                    'employee_rating',
                    'employee_comment',
                ])->all();

                $employeeKraFields['position'] = $position;

                if ($kraId) {
                    $kra = $appraisal->kras()->whereKey($kraId)->firstOrFail();
                    $kra->update($employeeKraFields);
                } else {
                    $kra = $appraisal->kras()->create($employeeKraFields);
                }

                $keptKraIds[] = $kra->id;
                $keptKpiIds = [];

                foreach ($kpiRows as $kpiPosition => $kpiData) {
                    $kpiId = $kpiData['id'] ?? null;
                    unset($kpiData['id']);

                    $employeeKpiFields = collect($kpiData)->only([
                        'title',
                        'description',
                        'measurement_method',
                        'target',
                        'actual_achievement',
                        'unit',
                        'kpi_type',
                        'weight',
                        'employee_score',
                        'evidence',
                        'employee_comment',
                    ])->all();

                    $employeeKpiFields['position'] = $kpiPosition;

                    if ($kpiId) {
                        $kpi = $kra->kpis()->whereKey($kpiId)->firstOrFail();
                        $kpi->update($employeeKpiFields);
                    } else {
                        $kpi = $kra->kpis()->create($employeeKpiFields);
                    }

                    $keptKpiIds[] = $kpi->id;
                }

                $deleteKpis = $kra->kpis();
                if ($keptKpiIds !== []) {
                    $deleteKpis->whereNotIn('id', $keptKpiIds);
                }
                $deleteKpis->delete();
            }

            $deleteKras = $appraisal->kras();
            if ($keptKraIds !== []) {
                $deleteKras->whereNotIn('id', $keptKraIds);
            }
            $deleteKras->delete();

            // Only sync competencies when the form included the section, so a
            // request without it cannot wipe existing rows.
            if (! empty($data['competencies_submitted'])) {
                $keptCompetencyIds = [];

                foreach (($data['competencies'] ?? []) as $competencyData) {
                    $fields = collect($competencyData)->only([
                        'name',
                        'weight',
                        'employee_rating',
                        'employee_comment',
                    ])->all();

                    if (! empty($competencyData['id'])) {
                        $competency = $appraisal->competencies()->whereKey($competencyData['id'])->firstOrFail();
                        $competency->update($fields);
                    } else {
                        $competency = $appraisal->competencies()->create($fields);
                    }

                    $keptCompetencyIds[] = $competency->id;
                }

                $deleteCompetencies = $appraisal->competencies();
                if ($keptCompetencyIds !== []) {
                    $deleteCompetencies->whereNotIn('id', $keptCompetencyIds);
                }
                $deleteCompetencies->delete();
            }
        });

        if ($appraisal->status !== 'in_progress') {
            $workflow->transition($appraisal->fresh(), 'in_progress', 'Employee saved appraisal draft.');
        }

        $scores->refresh($appraisal->fresh());

        return back()->with('success', 'Appraisal draft saved.');
    }

    public function submitEmployee(
        Appraisal $appraisal,
        AppraisalScoreService $scores,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->employee($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, [
                'draft',
                'in_progress',
                'returned_for_revision',
                'goal_setting',
                'self_assessment',
            ], true),
            422,
            'This appraisal cannot be submitted from its current status.'
        );

        $errors = $scores->weightErrors($appraisal);

        if ($errors !== []) {
            return back()->with('error', implode(' ', $errors));
        }

        $appraisal->update(['employee_submitted_at' => now()]);
        $workflow->transition($appraisal, 'submitted', 'Submitted to assigned supervisor.');

        return back()->with('success', 'Submitted to your supervisor.');
    }

    public function saveSupervisor(
        Request $request,
        Appraisal $appraisal,
        AppraisalScoreService $scores,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->supervisor($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, ['submitted', 'supervisor_review'], true),
            422,
            'Supervisor review is not available at this stage.'
        );

        $data = $request->validate([
            'manager_comments' => 'nullable|string|max:30000',
            'development_plan' => 'nullable|string|max:30000',
            'kras' => 'nullable|array',
            'kras.*.supervisor_rating' => 'nullable|numeric|min:1|max:5',
            'kras.*.supervisor_comment' => 'nullable|string|max:5000',
            'kras.*.kpis' => 'nullable|array',
            'kras.*.kpis.*.supervisor_score' => 'nullable|numeric|min:1|max:5',
            'kras.*.kpis.*.supervisor_comment' => 'nullable|string|max:5000',
            'competencies' => 'nullable|array',
            'competencies.*.supervisor_rating' => 'nullable|numeric|min:1|max:5',
            'competencies.*.supervisor_comment' => 'nullable|string|max:5000',
        ]);

        DB::transaction(function () use ($appraisal, $data) {
            $appraisal->update(collect($data)->only([
                'manager_comments',
                'development_plan',
            ])->all());

            foreach (($data['kras'] ?? []) as $kraId => $kraData) {
                $kra = $appraisal->kras()->whereKey($kraId)->firstOrFail();

                $kra->update(collect($kraData)->only([
                    'supervisor_rating',
                    'supervisor_comment',
                ])->all());

                foreach (($kraData['kpis'] ?? []) as $kpiId => $kpiData) {
                    $kra->kpis()
                        ->whereKey($kpiId)
                        ->firstOrFail()
                        ->update(collect($kpiData)->only([
                            'supervisor_score',
                            'supervisor_comment',
                        ])->all());
                }
            }

            foreach (($data['competencies'] ?? []) as $competencyId => $competencyData) {
                $appraisal->competencies()
                    ->whereKey($competencyId)
                    ->firstOrFail()
                    ->update(collect($competencyData)->only([
                        'supervisor_rating',
                        'supervisor_comment',
                    ])->all());
            }
        });

        if ($appraisal->status === 'submitted') {
            $workflow->transition($appraisal->fresh(), 'supervisor_review', 'Supervisor started review.');
        }

        $scores->refresh($appraisal->fresh());

        return back()->with('success', 'Supervisor review saved.');
    }

    public function returnForRevision(
        Request $request,
        Appraisal $appraisal,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->supervisor($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, ['submitted', 'supervisor_review'], true),
            422,
            'The appraisal cannot be returned from its current status.'
        );

        $data = $request->validate([
            'return_comment' => 'required|string|max:5000',
        ]);

        $appraisal->update([
            'employee_submitted_at' => null,
            'manager_submitted_at' => null,
        ]);

        $workflow->transition(
            $appraisal,
            'returned_for_revision',
            'Returned for revision: '.$data['return_comment']
        );

        return back()->with('success', 'Appraisal returned to the employee for revision.');
    }

    public function reviewComplete(
        Appraisal $appraisal,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->supervisor($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, ['submitted', 'supervisor_review'], true),
            422,
            'The review cannot be completed from its current status.'
        );

        $appraisal->update(['manager_submitted_at' => now()]);

        $workflow->transition(
            $appraisal,
            'meeting_pending',
            'Supervisor review completed; appraisal meeting pending.'
        );

        return back()->with('success', 'Supervisor review completed. Record the appraisal meeting next.');
    }

    public function saveMeeting(
        Request $request,
        Appraisal $appraisal,
        AppraisalScoreService $scores,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->supervisor($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');

        abort_unless(
            in_array($appraisal->status, ['meeting_pending', 'meeting_completed'], true),
            422,
            'The appraisal meeting cannot be recorded at this stage.'
        );

        $data = $request->validate([
            'meeting_at' => 'required|date',
            'venue' => 'nullable|string|max:255',
            'participants' => 'nullable|string|max:5000',
            'discussion_notes' => 'nullable|string|max:30000',
            'disagreements' => 'nullable|string|max:30000',
            'agreed_actions' => 'nullable|string|max:30000',
            'development_commitments' => 'nullable|string|max:30000',

            'kras' => 'nullable|array',
            'kras.*.agreed_rating' => 'nullable|numeric|min:1|max:5',
            'kras.*.kpis' => 'nullable|array',
            'kras.*.kpis.*.agreed_score' => 'nullable|numeric|min:1|max:5',
            'competencies' => 'nullable|array',
            'competencies.*.agreed_rating' => 'nullable|numeric|min:1|max:5',
        ]);

        DB::transaction(function () use ($appraisal, $data) {
            $meetingData = collect($data)->only([
                'meeting_at',
                'venue',
                'participants',
                'discussion_notes',
                'disagreements',
                'agreed_actions',
                'development_commitments',
            ])->all();

            $meetingData['recorded_by'] = auth()->id();

            AppraisalMeeting::updateOrCreate(
                ['appraisal_id' => $appraisal->id],
                $meetingData
            );

            foreach (($data['kras'] ?? []) as $kraId => $kraData) {
                $kra = $appraisal->kras()->whereKey($kraId)->firstOrFail();

                $kra->update(collect($kraData)->only(['agreed_rating'])->all());

                foreach (($kraData['kpis'] ?? []) as $kpiId => $kpiData) {
                    $kra->kpis()
                        ->whereKey($kpiId)
                        ->firstOrFail()
                        ->update(collect($kpiData)->only(['agreed_score'])->all());
                }
            }

            foreach (($data['competencies'] ?? []) as $competencyId => $competencyData) {
                $appraisal->competencies()
                    ->whereKey($competencyId)
                    ->firstOrFail()
                    ->update(collect($competencyData)->only(['agreed_rating'])->all());
            }

            $appraisal->update(['meeting_completed_at' => now()]);
        });

        if ($appraisal->status !== 'meeting_completed') {
            $workflow->transition($appraisal->fresh(), 'meeting_completed', 'Appraisal meeting and agreed scores recorded.');
        }

        $scores->refresh($appraisal->fresh());

        return back()->with('success', 'Meeting and agreed scores saved.');
    }

    public function employeeConfirm(
        Request $request,
        Appraisal $appraisal,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->employee($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');
        abort_unless($appraisal->status === 'meeting_completed', 422, 'The appraisal is not ready for employee confirmation.');

        $data = $request->validate([
            'comment' => 'nullable|string|max:5000',
        ]);

        $appraisal->update([
            'employee_final_comment' => $data['comment'] ?? null,
            'employee_confirmed_at' => now(),
        ]);

        $workflow->transition(
            $appraisal,
            'employee_confirmation',
            'Employee confirmed the agreed appraisal.'
        );

        return back()->with('success', 'Your confirmation has been recorded.');
    }

    public function supervisorConfirm(
        Request $request,
        Appraisal $appraisal,
        AppraisalWorkflowService $workflow
    ) {
        abort_unless($this->supervisor($appraisal), 403);
        abort_if($appraisal->locked_at, 422, 'Appraisal is locked.');
        abort_unless($appraisal->status === 'employee_confirmation', 422, 'Employee confirmation is required first.');

        $data = $request->validate([
            'comment' => 'nullable|string|max:5000',
        ]);

        $workflow->transition(
            $appraisal,
            'supervisor_confirmation',
            'Supervisor confirmation started.'
        );

        $appraisal->refresh()->update([
            'supervisor_final_comment' => $data['comment'] ?? null,
            'supervisor_confirmed_at' => now(),
        ]);

        $workflow->transition(
            $appraisal->fresh(),
            'completed',
            'Supervisor confirmed and completed the appraisal.'
        );

        return back()->with('success', 'Appraisal completed.');
    }

    private function authorise(Appraisal $appraisal): void
    {
        $user = auth()->user();

        $allowed = $this->employee($appraisal)
            || $this->supervisor($appraisal)
            || ($user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin())
            || ($user && method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['hr', 'HR', 'administrator']))
            || ($user && method_exists($user, 'hasPermission') && $user->hasPermission('appraisals.view'));

        abort_unless($allowed, 403);
    }

    private function employee(Appraisal $appraisal): bool
    {
        return (int) $appraisal->employee?->user_id === (int) auth()->id();
    }

    private function supervisor(Appraisal $appraisal): bool
    {
        return (int) $appraisal->manager_user_id === (int) auth()->id();
    }
}
