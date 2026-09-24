<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::with('programme');

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($programmeId = $request->get('programme_id')) {
            $query->where('programme_id', $programmeId);
        }

        $this->applyPeriod($query, $request);

        $stats = [
            'total' => Project::count(),
            'active' => Project::where('status', 'active')->count(),
            'draft' => Project::where('status', 'draft')->count(),
            'completed' => Project::where('status', 'completed')->count(),
        ];

        $perPage = in_array((int) $request->get('per_page'), [10,15,25,50,100], true)
            ? (int) $request->get('per_page') : 15;

        return view('admin.projects.index', [
            'projects' => $query->latest()->paginate($perPage)->withQueryString(),
            'programmes' => Programme::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.projects.index')->with('info', 'Use the New Project button.');
    }

    public function store(Request $request, AuditService $audit)
    {
        $project = Project::create($this->validated($request));
        $audit->log('projects', 'created', $project, [], $project->toArray());

        return back()->with('success', 'Project created successfully.');
    }

    public function edit(Project $project)
    {
        return redirect()->route('admin.projects.index')->with('info', 'Use the Edit action on the project row.');
    }

    public function update(Request $request, Project $project, AuditService $audit)
    {
        $old = $project->toArray();
        $project->update($this->validated($request, $project->id));
        $audit->log('projects', 'updated', $project, $old, $project->fresh()->toArray());

        return back()->with('success', 'Project updated successfully.');
    }

    public function destroy(Project $project, AuditService $audit)
    {
        $old = $project->toArray();
        $project->delete();
        $audit->log('projects', 'deleted', null, $old, []);

        return back()->with('success', 'Project deleted successfully.');
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:projects,id'],
        ]);

        $items = Project::whereKey($data['ids'])->get();

        foreach ($items as $project) {
            $old = $project->toArray();
            $project->delete();
            $audit->log('projects', 'deleted', null, $old, []);
        }

        return back()->with('success', $items->count().' project(s) deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'programme_id' => ['nullable','exists:programmes,id'],
            'name' => ['required','string','max:190'],
            'code' => ['nullable','string','max:50','unique:projects,code,'.($id ?? 'NULL')],
            'description' => ['nullable','string'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'status' => ['required','in:draft,active,completed,on_hold,cancelled'],
        ]);
    }

    private function applyPeriod($query, Request $request): void
    {
        $now = now();
        match ($request->get('period')) {
            'today' => $query->whereDate('created_at', $now->toDateString()),
            'week' => $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
            'month' => $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
            'quarter' => $query->whereBetween('created_at', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]),
            'year' => $query->whereYear('created_at', $now->year),
            default => null,
        };

        if ($from = $request->get('from_date')) $query->whereDate('created_at','>=',$from);
        if ($to = $request->get('to_date')) $query->whereDate('created_at','<=',$to);
    }
}
