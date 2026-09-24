<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query = Programme::query();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $this->applyPeriod($query, $request);

        $stats = [
            'total' => Programme::count(),
            'active' => Programme::where('status', 'active')->count(),
            'draft' => Programme::where('status', 'draft')->count(),
            'completed' => Programme::where('status', 'completed')->count(),
        ];

        $perPage = in_array((int) $request->get('per_page'), [10,15,25,50,100], true)
            ? (int) $request->get('per_page')
            : 15;

        return view('admin.programmes.index', [
            'programmes' => $query->latest()->paginate($perPage)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.programmes.index')->with('info', 'Use the New Programme button.');
    }

    public function store(Request $request, AuditService $audit)
    {
        $programme = Programme::create($this->validated($request));
        $audit->log('programmes', 'created', $programme, [], $programme->toArray());

        return back()->with('success', 'Programme created successfully.');
    }

    public function edit(Programme $programme)
    {
        return redirect()->route('admin.programmes.index')->with('info', 'Use the Edit action on the programme row.');
    }

    public function update(Request $request, Programme $programme, AuditService $audit)
    {
        $old = $programme->toArray();
        $programme->update($this->validated($request, $programme->id));
        $audit->log('programmes', 'updated', $programme, $old, $programme->fresh()->toArray());

        return back()->with('success', 'Programme updated successfully.');
    }

    public function destroy(Programme $programme, AuditService $audit)
    {
        $old = $programme->toArray();
        $programme->delete();
        $audit->log('programmes', 'deleted', null, $old, []);

        return back()->with('success', 'Programme deleted successfully.');
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer','exists:programmes,id'],
        ]);

        $items = Programme::whereKey($data['ids'])->get();

        foreach ($items as $programme) {
            $old = $programme->toArray();
            $programme->delete();
            $audit->log('programmes', 'deleted', null, $old, []);
        }

        return back()->with('success', $items->count().' programme(s) deleted.');
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

    private function applyPeriod($query, Request $request): void
    {
        if ($period = $request->get('period')) {
            $now = now();
            match ($period) {
                'today' => $query->whereDate('created_at', $now->toDateString()),
                'week' => $query->whereBetween('created_at', [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()]),
                'month' => $query->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]),
                'quarter' => $query->whereBetween('created_at', [$now->copy()->firstOfQuarter(), $now->copy()->lastOfQuarter()]),
                'year' => $query->whereYear('created_at', $now->year),
                default => null,
            };
        }

        if ($from = $request->get('from_date')) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to = $request->get('to_date')) {
            $query->whereDate('created_at', '<=', $to);
        }
    }
}
