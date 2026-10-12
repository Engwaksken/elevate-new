<?php

namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\FundingSource;
use App\Models\Programme;
use App\Models\Project;
use App\Models\User;
use App\Models\Workplan;
use App\Services\WorkplanProgressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkplanController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $query = Workplan::with([
            'milestones' => fn ($q) => $q->orderBy('due_date'),
            'activities' => fn ($q) => $q->orderBy('start_date'),
            'approvals.user',
            'creator',
            'approver',
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

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Workplans', $query->with(['programme','project','responsible']), [
                'Workplan' => 'title',
                'Programme' => 'programme.name',
                'Project' => 'project.name',
                'Financial Year' => 'financial_year',
                'Period' => fn($r)=>str_replace('_',' ',(string)$r->period_type),
                'Start' => 'start_date',
                'End' => 'end_date',
                'Responsible' => 'responsible.name',
                'Progress %' => 'progress_percent',
                'Milestones' => 'milestones_count',
                'Activities' => 'activities_count',
                'Status' => fn($r)=>str_replace('_',' ',(string)$r->status),
                'Approved By' => 'approver.name',
                'Approved At' => 'approved_at',
            ], null, ['period_type' => 'Period']);
        }

        $perPage = in_array((int)$request->get('per_page'),[10,20,25,50,100],true)
            ? (int)$request->get('per_page') : 20;

        return view('admin.workplans.index', [
            'workplans'=>$query->paginate($perPage)->withQueryString(),
            'programmes'=>Programme::orderBy('name')->get(),
            'projects'=>Project::orderBy('name')->get(),
            'cohorts'=>Cohort::orderBy('name')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'fundingSourceOptions'=>FundingSource::activeNames(),
            'stats'=>[
                'total'=>Workplan::count(),
                'draft'=>Workplan::where('status','draft')->count(),
                'submitted'=>Workplan::whereIn('status',['submitted','under_review'])->count(),
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
            'workplan_file'=>['nullable','file','mimes:xlsx,xls,csv','max:20480'],
        ]);

        $file = $request->file('workplan_file');
        if ($file) {
            $data['source_file_path'] = $file->store('workplans', 'local');
            $data['source_file_name'] = $file->getClientOriginalName();
        }

        Workplan::create($data+[
            'created_by'=>auth()->id(),
            'status'=>'draft',
            'progress_percent'=>0,
        ]);

        return back()->with('success','Workplan created.');
    }

    public function template()
    {
        $content = "Workplan Title,Financial Year,Period Type,Programme,Project,Cohort,Responsible Staff,Start Date,End Date,Description\n";
        $content .= "Example Workplan,2026/27,annual,,,,,2026-07-01,2027-06-30,Describe the workplan\n";

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename=workplan-template.csv',
        ]);
    }

    public function downloadSource(Workplan $workplan)
    {
        $user = request()->user();
        abort_unless($user && $user->isStaff() && $user->hasPermission('workplans.view'), 403);
        $isAdmin = $user && ($user->isSuperAdmin() || $user->hasAnyRole(['admin', 'administrator']));
        $isOwner = $user && in_array($user->id, [$workplan->created_by, $workplan->responsible_user_id]);
        $canOversee = $user && ($user->hasPermission('workplans.approve') || $user->hasAnyRole([
            'programs-lead', 'operations-lead',
        ]));
        abort_unless($isAdmin || ($isOwner || ($canOversee && $workplan->status !== 'draft')), 403);

        $path = $workplan->source_file_path;

        // Stored uploads are relative to the local disk's workplans directory.
        // Reject malformed values rather than allowing a persisted path to escape it.
        if (! is_string($path)
            || ! preg_match('#^workplans/[^/\\\\]+$#D', $path)
            || str_contains($path, '..')) {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            abort(404);
        }

        $storedName = $workplan->source_file_name;
        $safeName = is_string($storedName)
            && ! preg_match('~[\x00-\x1F\x7F/\\\\]~', $storedName)
            ? basename($storedName)
            : '';

        return response()->download($disk->path($path), $safeName !== '' ? $safeName : basename($path));
    }

    public function submit(Workplan $workplan)
    {
        if ($workplan->status !== 'draft') {
            return back()->with('error','Only draft workplans can be submitted.');
        }

        $workplan->update(['status'=>'submitted']);
        $workplan->approvals()->create(['user_id'=>auth()->id(),'action'=>'submitted','acted_at'=>now()]);

        return back()->with('success','Workplan submitted for approval.');
    }

    public function approve(Request $request, Workplan $workplan)
    {
        if (! $workplan->canBeApproved()) {
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
            'acted_at'=>now(),
        ]);

        return back()->with('success','Workplan approved.');
    }

    public function returnForRevision(Request $request, Workplan $workplan)
    {
        if (! $workplan->canBeApproved()) {
            return back()->with('error','Only submitted workplans can be returned.');
        }

        $data = $request->validate(['comments'=>['required','string','max:2000']]);

        $workplan->update(['status'=>'draft','approved_by'=>null,'approved_at'=>null]);
        $workplan->approvals()->create([
            'user_id'=>auth()->id(),
            'action'=>'returned',
            'comments'=>$data['comments'],
            'acted_at'=>now(),
        ]);

        return back()->with('success','Workplan returned for revision.');
    }

    public function reject(Request $request, Workplan $workplan)
    {
        if (! $workplan->canBeApproved()) {
            return back()->with('error','Only submitted workplans can be rejected.');
        }

        $data = $request->validate(['comments'=>['required','string','max:2000']]);

        $workplan->update(['status'=>'cancelled']);
        $workplan->approvals()->create([
            'user_id'=>auth()->id(),
            'action'=>'rejected',
            'comments'=>$data['comments'],
            'acted_at'=>now(),
        ]);

        return back()->with('success','Workplan rejected.');
    }

    public function start(Workplan $workplan, WorkplanProgressService $progress)
    {
        if ($workplan->status !== 'approved') {
            return back()->with('error','Only approved workplans can be started.');
        }

        $workplan->update(['status'=>'in_progress']);
        $progress->recalculate($workplan);

        return back()->with('success','Workplan started.');
    }

    public function complete(Workplan $workplan, WorkplanProgressService $progress)
    {
        if (! in_array($workplan->status, ['in_progress','on_hold'], true)) {
            return back()->with('error','Only active workplans can be completed.');
        }

        $progress->recalculate($workplan);
        $workplan->update(['status'=>'completed','progress_percent'=>100]);

        return back()->with('success','Workplan marked completed.');
    }
}
