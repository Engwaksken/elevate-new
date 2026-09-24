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
        $query=Cohort::with(['programme','project','branch']);

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q
                ->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);
        if($programmeId=$request->get('programme_id')) $query->where('programme_id',$programmeId);
        if($projectId=$request->get('project_id')) $query->where('project_id',$projectId);
        if($branchId=$request->get('branch_id')) $query->where('branch_id',$branchId);

        $this->applyPeriod($query,$request);

        $stats=[
            'total'=>Cohort::count(),
            'active'=>Cohort::where('status','active')->count(),
            'open'=>Cohort::where('status','open')->count(),
            'completed'=>Cohort::where('status','completed')->count(),
        ];

        $perPage=in_array((int)$request->get('per_page'),[10,15,25,50,100],true)
            ? (int)$request->get('per_page') : 15;

        return view('admin.cohorts.index',[
            'cohorts'=>$query->latest()->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'branches'=>Branch::where('is_active',true)->orderBy('name')->get(),
            'stats'=>$stats,
        ]);
    }

    public function create()
    {
        return redirect()->route('admin.cohorts.index')->with('info','Use the New Cohort button.');
    }

    public function store(Request $request, AuditService $audit)
    {
        $cohort=Cohort::create($this->validated($request));
        $audit->log('cohorts','created',$cohort,[],$cohort->toArray());

        return back()->with('success','Cohort created successfully.');
    }

    public function edit(Cohort $cohort)
    {
        return redirect()->route('admin.cohorts.index')->with('info','Use the Edit action on the cohort row.');
    }

    public function update(Request $request, Cohort $cohort, AuditService $audit)
    {
        $old=$cohort->toArray();
        $cohort->update($this->validated($request,$cohort->id));
        $audit->log('cohorts','updated',$cohort,$old,$cohort->fresh()->toArray());

        return back()->with('success','Cohort updated successfully.');
    }

    public function destroy(Cohort $cohort, AuditService $audit)
    {
        $old=$cohort->toArray();
        $cohort->delete();
        $audit->log('cohorts','deleted',null,$old,[]);

        return back()->with('success','Cohort deleted successfully.');
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data=$request->validate([
            'ids'=>['required','array','min:1'],
            'ids.*'=>['integer','exists:cohorts,id'],
        ]);

        $items=Cohort::whereKey($data['ids'])->get();

        foreach($items as $cohort){
            $old=$cohort->toArray();
            $cohort->delete();
            $audit->log('cohorts','deleted',null,$old,[]);
        }

        return back()->with('success',$items->count().' cohort(s) deleted.');
    }

    private function validated(Request $request, ?int $id=null): array
    {
        return $request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'branch_id'=>['nullable','exists:branches,id'],
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:cohorts,code,'.($id ?? 'NULL')],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:planned,open,active,completed,cancelled'],
        ]);
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
