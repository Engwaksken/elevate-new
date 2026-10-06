<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Models\Project;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ExportsTables;
    use BulkDeletesRecords;

    protected function bulkDeleteModel(): string
    {
        return Project::class;
    }

    public function index(Request $request)
    {
        $query=Project::with('programme');

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);
        if($programme=$request->get('programme_id')) $query->where('programme_id',$programme);

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Projects',$query->latest(),[
                'Project'=>'name',
                'Code'=>'code',
                'Programme'=>'programme.name',
                'Start date'=>'start_date',
                'End date'=>'end_date',
                'Status'=>fn($p)=>ucfirst((string)$p->status),
            ]);
        }

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.projects.index',[
            'projects'=>$query->latest()->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'stats'=>[
                'total'=>Project::count(),
                'active'=>Project::where('status','active')->count(),
                'draft'=>Project::where('status','draft')->count(),
                'completed'=>Project::where('status','completed')->count(),
            ],
        ]);
    }

    public function create(){ return redirect()->route('admin.projects.index')->with('open_modal','project-create'); }

    public function store(Request $request, AuditService $audit)
    {
        $data=$this->validated($request);

        if(blank($data['code'] ?? null)){
            $data['code']=app(\App\Services\CodeGenerator::class)->next('PRJ',Project::class);
        }

        $project=Project::create($data);
        $audit->log('projects','created',$project,[],$project->toArray());
        return redirect()->route('admin.projects.index')->with('success','Project created. Code: '.$project->code);
    }

    public function edit(Project $project){ return redirect()->route('admin.projects.index')->with('open_modal','project-'.$project->id); }

    public function update(Request $request, Project $project, AuditService $audit)
    {
        $old=$project->toArray();
        $data=$this->validated($request,$project->id);

        if(blank($data['code'] ?? null)){
            $data['code']=$project->code ?: app(\App\Services\CodeGenerator::class)->next('PRJ',Project::class);
        }

        $project->update($data);
        $audit->log('projects','updated',$project,$old,$project->fresh()->toArray());
        return redirect()->route('admin.projects.index')->with('success','Project updated. Code: '.$project->code);
    }

    public function destroy(Project $project, AuditService $audit)
    {
        $old=$project->toArray();
        try{
            $project->delete();
            $audit->log('projects','deleted',null,$old,[]);
            return back()->with('success','Project deleted.');
        }catch(QueryException $e){
            return back()->with('error','Project cannot be deleted because related records still depend on it.');
        }
    }

    private function validated(Request $request,?int $id=null): array
    {
        return $request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:projects,code,'.($id ?? 'NULL')],
            'description'=>['nullable','string'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:draft,active,completed,on_hold,cancelled'],
        ]);
    }
}
