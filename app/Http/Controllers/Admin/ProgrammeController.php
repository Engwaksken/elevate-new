<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Programme;
use App\Services\AuditService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function index(Request $request)
    {
        $query=Programme::query();

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%"));
        }

        if($status=$request->get('status')) $query->where('status',$status);

        if($request->filled('from')) $query->whereDate('start_date','>=',$request->date('from'));
        if($request->filled('to')) $query->whereDate('end_date','<=',$request->date('to'));

        $perPage=in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.programmes.index',[
            'programmes'=>$query->latest()->paginate($perPage)->withQueryString(),
            'stats'=>[
                'total'=>Programme::count(),
                'active'=>Programme::where('status','active')->count(),
                'draft'=>Programme::where('status','draft')->count(),
                'completed'=>Programme::where('status','completed')->count(),
            ],
        ]);
    }

    public function create(){ return redirect()->route('admin.programmes.index')->with('open_modal','programme-create'); }

    public function store(Request $request, AuditService $audit)
    {
        $programme=Programme::create($this->validated($request));
        $audit->log('programmes','created',$programme,[],$programme->toArray());
        return redirect()->route('admin.programmes.index')->with('success','Programme created.');
    }

    public function edit(Programme $programme){ return redirect()->route('admin.programmes.index')->with('open_modal','programme-'.$programme->id); }

    public function update(Request $request, Programme $programme, AuditService $audit)
    {
        $old=$programme->toArray();
        $programme->update($this->validated($request,$programme->id));
        $audit->log('programmes','updated',$programme,$old,$programme->fresh()->toArray());
        return redirect()->route('admin.programmes.index')->with('success','Programme updated.');
    }

    public function destroy(Programme $programme, AuditService $audit)
    {
        $old=$programme->toArray();
        try{
            $programme->delete();
            $audit->log('programmes','deleted',null,$old,[]);
            return back()->with('success','Programme deleted.');
        }catch(QueryException $e){
            return back()->with('error','Programme cannot be deleted because related records still depend on it.');
        }
    }

    private function validated(Request $request,?int $id=null): array
    {
        return $request->validate([
            'name'=>['required','string','max:190'],
            'code'=>['nullable','string','max:50','unique:programmes,code,'.($id ?? 'NULL')],
            'description'=>['nullable','string'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'status'=>['required','in:draft,active,completed,on_hold,cancelled'],
        ]);
    }
}
