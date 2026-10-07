<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CohortController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return Cohort::class;
    }

    public function index(Request $request)
    {
        $query=Cohort::with(['programme','project','branch','branches']);

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);
        if($programme=$request->get('programme_id')) $query->where('programme_id',$programme);
        if($branch=$request->get('branch_id')) {
            $query->where(fn ($q) => $q->where('branch_id', $branch)
                ->orWhereHas('branches', fn ($branches) => $branches->whereKey($branch)));
        }

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Cohorts',$query->latest(),[
                'Cohort'=>'name',
                'Code'=>'code',
                'Programme'=>'programme.name',
                'Project'=>'project.name',
                'Branches'=>fn($c)=>$c->branches->pluck('name')->join(', ') ?: $c->branch?->name,
                'Start date'=>'start_date',
                'End date'=>'end_date',
                'Status'=>fn($c)=>ucfirst((string)$c->status),
            ]);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.cohorts.index',[
            'cohorts'=>$query->latest()->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'branches'=>Branch::orderBy('name')->get(),
            'stats'=>[
                'total'=>Cohort::count(),
                'active'=>Cohort::where('status','active')->count(),
                'open'=>Cohort::where('status','open')->count(),
                'completed'=>Cohort::where('status','completed')->count(),
            ],
        ]);
    }

    public function create(){ return redirect()->route('admin.cohorts.index')->with('open_modal','cohort-create'); }

    public function store(Request $request, AuditService $audit)
    {
        $data=$this->validated($request);
        $branchIds=$data['branch_ids'] ?? [];
        unset($data['branch_ids']);
        $data['branch_id']=$branchIds[0] ?? null;
        $cohort=Cohort::create($data);
        $cohort->branches()->sync($branchIds);
        $cohort->load('branches');
        $audit->log('cohorts','created',$cohort,[],$cohort->toArray());
        return redirect()->route('admin.cohorts.index')->with('success','Cohort created.');
    }

    public function edit(Cohort $cohort){ return redirect()->route('admin.cohorts.index')->with('open_modal','cohort-'.$cohort->id); }

    public function update(Request $request, Cohort $cohort, AuditService $audit)
    {
        $old=$cohort->toArray();
        $data=$this->validated($request,$cohort->id);
        $branchIds=$data['branch_ids'] ?? [];
        unset($data['branch_ids']);
        $data['branch_id']=$branchIds[0] ?? null;
        $cohort->update($data);
        $cohort->branches()->sync($branchIds);
        $audit->log('cohorts','updated',$cohort,$old,$cohort->fresh()->toArray());
        return redirect()->route('admin.cohorts.index')->with('success','Cohort updated.');
    }

    public function destroy(Cohort $cohort, AuditService $audit)
    {
        $old=$cohort->toArray();
        try{
            $cohort->delete();
            $audit->log('cohorts','deleted',null,$old,[]);
            return back()->with('success','Cohort deleted.');
        }catch(QueryException $e){
            return back()->with('error','Cohort cannot be deleted because related records still depend on it.');
        }
    }

    private function validated(Request $request,?int $id=null): array
    {
        if (!$request->has('branch_ids') && $request->filled('branch_id')) {
            $request->merge(['branch_ids' => [$request->input('branch_id')]]);
        }

        return $request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'branch_id'=>['nullable','exists:branches,id'],
            'branch_ids'=>['nullable','array'],
            'branch_ids.*'=>['integer','distinct','exists:branches,id'],
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:cohorts,code,'.($id ?? 'NULL')],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:planned,open,active,completed,cancelled'],
        ]);
    }
}
