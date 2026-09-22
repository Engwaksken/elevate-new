<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CohortController extends Controller
{
    public function index(Request $request)
    {
        $query = Cohort::with(['programme','project','branch']);

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('admin.cohorts.index', [
            'cohorts' => $query->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return $this->form(new Cohort());
    }

    public function store(Request $request, AuditService $audit)
    {
        $cohort = Cohort::create($this->validated($request));
        $audit->log('cohorts', 'created', $cohort, [], $cohort->toArray());

        return redirect()->route('admin.cohorts.index')->with('success', 'Cohort created.');
    }

    public function edit(Cohort $cohort)
    {
        return $this->form($cohort);
    }

    public function update(Request $request, Cohort $cohort, AuditService $audit)
    {
        $old = $cohort->toArray();
        $cohort->update($this->validated($request, $cohort->id));
        $audit->log('cohorts', 'updated', $cohort, $old, $cohort->fresh()->toArray());

        return redirect()->route('admin.cohorts.index')->with('success', 'Cohort updated.');
    }

    public function destroy(Cohort $cohort, AuditService $audit)
    {
        $old = $cohort->toArray();
        $cohort->delete();
        $audit->log('cohorts', 'deleted', null, $old, []);

        return back()->with('success', 'Cohort deleted.');
    }

    private function form(Cohort $cohort)
    {
        return view('admin.cohorts.form', [
            'cohort' => $cohort,
            'programmes' => Programme::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'programme_id' => ['nullable','exists:programmes,id'],
            'project_id' => ['nullable','exists:projects,id'],
            'branch_id' => ['nullable','exists:branches,id'],
            'name' => ['required','string','max:190'],
            'code' => ['nullable','string','max:50','unique:cohorts,code,'.($id ?? 'NULL')],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'status' => ['required','in:planned,open,active,completed,cancelled'],
        ]);
    }
}
