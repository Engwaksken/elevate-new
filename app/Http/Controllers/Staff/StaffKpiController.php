<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\StaffKpi;
use App\Services\StaffKpiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffKpiController extends Controller
{
    use ExportsTables;
    public function __construct(private StaffKpiService $kpis)
    {
    }

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $employee = Employee::with('contracts')->where('user_id', $user->id)->first();
        $contract = $employee ? $this->kpis->contractFor($employee, $request->integer('contract') ?: null) : null;
        $kpis = $employee ? $this->kpis->kpis($employee, $contract) : collect();

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'My KPIs', $kpis, [
                'KRA' => 'kra',
                'KPI' => 'title',
                'Description' => 'description',
                'Measurement' => 'measurement_method',
                'Target' => 'target',
                'Unit' => 'unit',
                'Weight %' => 'weight',
                'Status' => fn ($r) => str_replace('_', ' ', (string) $r->status),
                'Reviewed At' => 'reviewed_at',
            ], array_filter([
                'Contract' => $contract ? trim(($contract->start_date?->format('d M Y') ?? '').' - '.($contract->end_date?->format('d M Y') ?? 'open')) : null,
            ]));
        }
        $quarters = $this->kpis->quarters($contract);

        return view('staff.kpis.index', [
            'employee' => $employee,
            'contract' => $contract,
            'contracts' => $employee?->contracts->sortByDesc('start_date')->values() ?? collect(),
            'kpis' => $kpis,
            'quarters' => $employee ? $this->kpis->quarterProgress($employee, $kpis, $quarters) : collect(),
            'pendingReviews' => $this->kpis->pendingTeamReviews($user),
            'canSubmit' => $kpis->contains(fn (StaffKpi $kpi) => $kpi->isEditableByOwner()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);
        $contract = $this->contract($request, $employee);

        StaffKpi::create($this->validated($request) + [
            'employee_id' => $employee->id,
            'employment_contract_id' => $contract?->id,
            'status' => 'draft',
            'position' => $employee->kpis()->where('employment_contract_id', $contract?->id)->count(),
        ]);

        return $this->back($contract, 'KPI added. Submit your KPIs to your supervisor when the set is ready.');
    }

    public function update(Request $request, StaffKpi $kpi): RedirectResponse
    {
        $employee = $this->employee($request);
        abort_unless($kpi->employee_id === $employee->id, 403);

        // Changing a submitted or approved KPI sends it back to draft so the supervisor sees the change.
        $kpi->update($this->validated($request) + ($kpi->isEditableByOwner() ? [] : ['status' => 'draft']));

        return $this->back($kpi->contract, $kpi->wasChanged('status') ? 'KPI updated and moved back to draft — submit it again for approval.' : 'KPI updated.');
    }

    public function destroy(Request $request, StaffKpi $kpi): RedirectResponse
    {
        $employee = $this->employee($request);
        abort_unless($kpi->employee_id === $employee->id && $kpi->status !== 'approved', 403);

        $contract = $kpi->contract;
        $kpi->delete();

        return $this->back($contract, 'KPI removed.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $employee = $this->employee($request);
        $contract = $this->contract($request, $employee);

        $count = $this->kpis->submit($employee, $contract);

        return $this->back($contract, $count.' '.Str::plural('KPI', $count).' sent to your supervisor for approval.');
    }

    public function review(Request $request, Employee $employee): RedirectResponse
    {
        $data = $request->validate([
            'kpi_ids' => ['required', 'array', 'min:1'],
            'kpi_ids.*' => ['integer'],
            'decision' => ['required', 'in:approve,return'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $count = $this->kpis->review($request->user(), $employee, $data['kpi_ids'], $data['decision'], $data['review_comment'] ?? null);

        return back()->with('success', $count.' '.Str::plural('KPI', $count).($data['decision'] === 'approve' ? ' approved.' : ' returned for changes.'));
    }

    public function startQuarter(Request $request, AppraisalCycle $cycle): RedirectResponse
    {
        $employee = $this->employee($request);
        $contract = $this->contract($request, $employee);

        $appraisal = $this->kpis->startQuarter($employee, $cycle, $contract);

        return redirect()->route('staff.performance.show', $appraisal)
            ->with('success', $cycle->name.' appraisal ready with your approved contract KPIs.');
    }

    public function import(Request $request, Appraisal $appraisal): RedirectResponse
    {
        $employee = $this->employee($request);
        abort_unless($appraisal->employee_id === $employee->id, 403);

        $added = $this->kpis->importInto($appraisal, $this->kpis->contractFor($employee));

        return back()->with('success', $added
            ? $added.' contract '.Str::plural('KPI', $added).' added to this appraisal.'
            : 'All approved contract KPIs are already in this appraisal.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'kra' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'measurement_method' => ['nullable', 'string', 'max:255'],
            'target' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:100'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    private function employee(Request $request): Employee
    {
        return Employee::where('user_id', $request->user()->id)->firstOr(
            fn () => abort(403, 'HR has not set up your employee record yet.')
        );
    }

    private function contract(Request $request, Employee $employee): ?EmploymentContract
    {
        return $request->filled('contract_id')
            ? $employee->contracts()->whereKey($request->integer('contract_id'))->firstOrFail()
            : $this->kpis->contractFor($employee);
    }

    private function back(?EmploymentContract $contract, string $message): RedirectResponse
    {
        return redirect()->route('staff.kpis.index', array_filter(['contract' => $contract?->id]))->with('success', $message);
    }
}
