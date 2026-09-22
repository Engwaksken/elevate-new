<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Services\AuditService;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = Programme::query();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('admin.programmes.index', [
            'programmes' => $query->latest()->paginate(15)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.programmes.form', ['programme' => new Programme()]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);
        $programme = Programme::create($data);
        $audit->log('programmes', 'created', $programme, [], $programme->toArray());

        return redirect()->route('admin.programmes.index')->with('success', 'Programme created.');
    }

    public function edit(Programme $programme)
    {
        return view('admin.programmes.form', compact('programme'));
    }

    public function update(Request $request, Programme $programme, AuditService $audit)
    {
        $old = $programme->toArray();
        $programme->update($this->validated($request, $programme->id));
        $audit->log('programmes', 'updated', $programme, $old, $programme->fresh()->toArray());

        return redirect()->route('admin.programmes.index')->with('success', 'Programme updated.');
    }

    public function destroy(Programme $programme, AuditService $audit)
    {
        $old = $programme->toArray();
        $programme->delete();
        $audit->log('programmes', 'deleted', null, $old, []);

        return back()->with('success', 'Programme deleted.');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required','string','max:190'],
            'code' => ['nullable','string','max:50','unique:programmes,code,'.($id ?? 'NULL')],
            'description' => ['nullable','string'],
            'start_date' => ['nullable','date'],
            'end_date' => ['nullable','date','after_or_equal:start_date'],
            'status' => ['required','in:draft,active,completed,on_hold,cancelled'],
        ]);
    }
}
