<?php
namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Workplan;
use App\Services\WorkplanProgressService;
use Illuminate\Http\Request;

class WorkplanController extends Controller
{
    public function index(Request $request)
    {
        $query = Workplan::latest();
        if($status=$request->get('status')) $query->where('status',$status);
        return view('admin.workplans.index',['workplans'=>$query->paginate(20)->withQueryString()]);
    }

    public function store(Request $request)
    {
        $data=$request->validate([
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'title'=>['required','string','max:190'],
            'financial_year'=>['nullable','string','max:20'],
            'period_type'=>['required','in:annual,quarterly,monthly,programme,project,department,staff'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'responsible_user_id'=>['nullable','exists:users,id'],
            'description'=>['nullable','string'],
        ]);
        Workplan::create($data+['created_by'=>auth()->id()]);
        return back()->with('success','Workplan created.');
    }

    public function submit(Workplan $workplan)
    {
        $workplan->update(['status'=>'submitted']);
        $workplan->approvals()->create(['user_id'=>auth()->id(),'action'=>'submitted']);
        return back()->with('success','Workplan submitted.');
    }

    public function approve(Request $request, Workplan $workplan)
    {
        $workplan->update(['status'=>'approved','approved_by'=>auth()->id(),'approved_at'=>now()]);
        $workplan->approvals()->create([
            'user_id'=>auth()->id(),'action'=>'approved','comments'=>$request->input('comments')
        ]);
        return back()->with('success','Workplan approved.');
    }
}
