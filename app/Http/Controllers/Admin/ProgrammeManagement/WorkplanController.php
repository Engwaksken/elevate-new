<?php

namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use App\Models\Workplan;
use Illuminate\Http\Request;

class WorkplanController extends Controller
{
    public function index(Request $request)
    {
        $query = Workplan::with([
            'milestones' => fn ($q) => $q->orderBy('due_date'),
            'activities' => fn ($q) => $q->orderBy('start_date'),
        ])->withCount(['milestones','activities'])->latest();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title','like',"%{$search}%")
                  ->orWhere('financial_year','like',"%{$search}%")
                  ->orWhere('description','like',"%{$search}%");
            });
        }

        if ($status = $request->get('status')) $query->where('status',$status);
        if ($period = $request->get('period_type')) $query->where('period_type',$period);
        if ($request->filled('from')) $query->whereDate('start_date','>=',$request->date('from'));
        if ($request->filled('to')) $query->whereDate('end_date','<=',$request->date('to'));

        $perPage = in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.workplans.index', [
            'workplans'=>$query->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'stats'=>[
                'total'=>Workplan::count(),
                'draft'=>Workplan::where('status','draft')->count(),
                'submitted'=>Workplan::where('status','submitted')->count(),
                'approved'=>Workplan::where('status','approved')->count(),
            ],
        ]);
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

        Workplan::create($data+[
            'created_by'=>auth()->id(),
            'status'=>'draft',
            'progress_percent'=>0,
        ]);

        return back()->with('success','Workplan created.');
    }

    public function submit(Workplan $workplan)
    {
        if ($workplan->status !== 'draft') {
            return back()->with('error','Only draft workplans can be submitted.');
        }

        $workplan->update(['status'=>'submitted']);
        $workplan->approvals()->create(['user_id'=>auth()->id(),'action'=>'submitted']);

        return back()->with('success','Workplan submitted.');
    }

    public function approve(Request $request, Workplan $workplan)
    {
        if ($workplan->status !== 'submitted') {
            return back()->with('error','Only submitted workplans can be approved.');
        }

        $request->validate(['comments'=>['nullable','string','max:2000']]);

        $workplan->update([
            'status'=>'approved',
            'approved_by'=>auth()->id(),
            'approved_at'=>now(),
        ]);

        $workplan->approvals()->create([
            'user_id'=>auth()->id(),
            'action'=>'approved',
            'comments'=>$request->input('comments'),
        ]);

        return back()->with('success','Workplan approved.');
    }
}
