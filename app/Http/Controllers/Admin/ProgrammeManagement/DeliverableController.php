<?php

namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Deliverable;
use App\Models\User;
use Illuminate\Http\Request;

class DeliverableController extends Controller
{
    public function index(Request $request)
    {
        $query=Deliverable::with(['activity','owner'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('title','like',"%{$search}%")
                    ->orWhere('description','like',"%{$search}%")
                    ->orWhereHas('activity',fn($a)=>$a->where('title','like',"%{$search}%"))
                    ->orWhereHas('owner',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);

        if($request->filled('from')) {
            $query->whereDate('due_date','>=',$request->date('from'));
        }

        if($request->filled('to')) {
            $query->whereDate('due_date','<=',$request->date('to'));
        }

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.deliverables.index',[
            'deliverables'=>$query->paginate($perPage)->withQueryString(),
            'activities'=>Activity::orderBy('title')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'stats'=>[
                'total'=>Deliverable::count(),
                'in_progress'=>Deliverable::where('status','in_progress')->count(),
                'completed'=>Deliverable::where('status','completed')->count(),
                'overdue'=>Deliverable::where('status','overdue')->count(),
            ],
        ]);
    }

    public function store(Request $request, Activity $activity)
    {
        $activity->deliverables()->create($request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'owner_user_id'=>['nullable','exists:users,id'],
            'due_date'=>['nullable','date'],
        ])+[
            'status'=>'not_started',
            'progress_percent'=>0,
        ]);

        return back()->with('success','Deliverable created.');
    }

    public function update(Request $request, Deliverable $deliverable)
    {
        $deliverable->update($request->validate([
            'status'=>['required','in:not_started,in_progress,returned_for_revision,completed,overdue'],
            'progress_percent'=>['required','numeric','min:0','max:100'],
        ]));

        return back()->with('success','Deliverable updated.');
    }
}
