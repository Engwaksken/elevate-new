<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $query=Branch::query();

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('district','like',"%{$search}%")
                ->orWhere('country','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if($request->filled('status')){
            $query->where('is_active',$request->get('status')==='active');
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.branches.index',[
            'branches'=>$query->orderBy('name')->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>Branch::count(),
                'active'=>Branch::where('is_active',true)->count(),
                'inactive'=>Branch::where('is_active',false)->count(),
                'districts'=>Branch::whereNotNull('district')->distinct('district')->count('district'),
            ],
        ]);
    }

    public function create(){ return redirect()->route('admin.branches.index')->with('open_modal','branch-create'); }

    public function store(Request $request, AuditService $audit)
    {
        $branch=Branch::create($this->validated($request));
        $audit->log('branches','created',$branch,[],$branch->toArray());
        return redirect()->route('admin.branches.index')->with('success','Branch created.');
    }

    public function edit(Branch $branch){ return redirect()->route('admin.branches.index')->with('open_modal','branch-'.$branch->id); }

    public function update(Request $request, Branch $branch, AuditService $audit)
    {
        $old=$branch->toArray();
        $branch->update($this->validated($request,$branch->id));
        $audit->log('branches','updated',$branch,$old,$branch->fresh()->toArray());
        return redirect()->route('admin.branches.index')->with('success','Branch updated.');
    }

    public function destroy(Branch $branch, AuditService $audit)
    {
        $old=$branch->toArray();
        try{
            $branch->delete();
            $audit->log('branches','deleted',null,$old,[]);
            return back()->with('success','Branch deleted.');
        }catch(QueryException $e){
            return back()->with('error','Branch cannot be deleted because related records still depend on it.');
        }
    }

    private function validated(Request $request,?int $id=null): array
    {
        return $request->validate([
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:branches,code,'.($id ?? 'NULL')],
            'district'=>['nullable','string','max:100'],
            'country'=>['required','string','max:100'],
            'is_active'=>['nullable','boolean'],
        ])+['is_active'=>$request->boolean('is_active')];
    }
}
