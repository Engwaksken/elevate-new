<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query = Branch::query();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q
                ->where('name','like',"%{$search}%")
                ->orWhere('district','like',"%{$search}%")
                ->orWhere('country','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->get('status') === 'active');
        }

        $this->applyPeriod($query,$request);

        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('is_active',true)->count(),
            'inactive' => Branch::where('is_active',false)->count(),
            'districts' => Branch::whereNotNull('district')->distinct('district')->count('district'),
        ];

        $perPage = in_array((int)$request->get('per_page'),[10,15,25,50,100],true)
            ? (int)$request->get('per_page') : 15;

        return view('admin.branches.index',[
            'branches'=>$query->latest()->paginate($perPage)->withQueryString(),
            'stats'=>$stats,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.branches.index')->with('info','Use the New Branch button.');
    }

    public function store(Request $request, AuditService $audit)
    {
        $branch=Branch::create($this->validated($request));
        $audit->log('branches','created',$branch,[],$branch->toArray());

        return back()->with('success','Branch created successfully.');
    }

    public function edit(Branch $branch)
    {
        return redirect()->route('admin.branches.index')->with('info','Use the Edit action on the branch row.');
    }

    public function update(Request $request, Branch $branch, AuditService $audit)
    {
        $old=$branch->toArray();
        $branch->update($this->validated($request,$branch->id));
        $audit->log('branches','updated',$branch,$old,$branch->fresh()->toArray());

        return back()->with('success','Branch updated successfully.');
    }

    public function destroy(Branch $branch, AuditService $audit)
    {
        $old=$branch->toArray();
        $branch->delete();
        $audit->log('branches','deleted',null,$old,[]);

        return back()->with('success','Branch deleted successfully.');
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data=$request->validate([
            'ids'=>['required','array','min:1'],
            'ids.*'=>['integer','exists:branches,id'],
        ]);

        $items=Branch::whereKey($data['ids'])->get();

        foreach($items as $branch){
            $old=$branch->toArray();
            $branch->delete();
            $audit->log('branches','deleted',null,$old,[]);
        }

        return back()->with('success',$items->count().' branch(es) deleted.');
    }

    private function validated(Request $request, ?int $id=null): array
    {
        return $request->validate([
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:branches,code,'.($id ?? 'NULL')],
            'district'=>['nullable','string','max:100'],
            'country'=>['required','string','max:100'],
            'is_active'=>['nullable','boolean'],
        ]) + ['is_active'=>$request->boolean('is_active')];
    }

    private function applyPeriod($query, Request $request): void
    {
        $now=now();
        match($request->get('period')){
            'today'=>$query->whereDate('created_at',$now->toDateString()),
            'week'=>$query->whereBetween('created_at',[$now->copy()->startOfWeek(),$now->copy()->endOfWeek()]),
            'month'=>$query->whereBetween('created_at',[$now->copy()->startOfMonth(),$now->copy()->endOfMonth()]),
            'quarter'=>$query->whereBetween('created_at',[$now->copy()->firstOfQuarter(),$now->copy()->lastOfQuarter()]),
            'year'=>$query->whereYear('created_at',$now->year),
            default=>null,
        };

        if($from=$request->get('from_date')) $query->whereDate('created_at','>=',$from);
        if($to=$request->get('to_date')) $query->whereDate('created_at','<=',$to);
    }
}
