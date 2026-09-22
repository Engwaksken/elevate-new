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

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        return view('admin.projects.index', [
            'projects' => $query->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.projects.form', [
            'project' => new Project(),
            'programmes' => Programme::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $project = Project::create($this->validated($request));
        $audit->log('projects', 'created', $project, [], $project->toArray());

        return redirect()->route('admin.projects.index')->with('success', 'Project created.');
    }

    public function edit(Project $project)
    {
        return view('admin.projects.form', [
            'project' => $project,
            'programmes' => Programme::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Project $project, AuditService $audit)
    {
        $old = $project->toArray();
        $project->update($this->validated($request, $project->id));
        $audit->log('projects', 'updated', $project, $old, $project->fresh()->toArray());

        return redirect()->route('admin.projects.index')->with('success', 'Project updated.');
    }

    public function destroy(Project $project, AuditService $audit)
    {
        $old = $project->toArray();
        $project->delete();
        $audit->log('projects', 'deleted', null, $old, []);

        return back()->with('success', 'Project deleted.');
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
}
