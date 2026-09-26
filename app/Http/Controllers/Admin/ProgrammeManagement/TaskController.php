<?php

namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $query=Task::with(['activity','assignee'])->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('title','like',"%{$search}%")
                    ->orWhere('description','like',"%{$search}%")
                    ->orWhereHas('activity',fn($a)=>$a->where('title','like',"%{$search}%"))
                    ->orWhereHas('assignee',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($status=$request->get('status')) $query->where('status',$status);
        if($priority=$request->get('priority')) $query->where('priority',$priority);

        if($request->filled('from')) {
            $query->whereDate('due_date','>=',$request->date('from'));
        }

        if($request->filled('to')) {
            $query->whereDate('due_date','<=',$request->date('to'));
        }

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.tasks.index',[
            'tasks'=>$query->paginate($perPage)->withQueryString(),
            'activities'=>Activity::orderBy('title')->get(),
            'users'=>User::where('user_type','staff')->orderBy('name')->get(),
            'stats'=>[
                'total'=>Task::count(),
                'in_progress'=>Task::where('status','in_progress')->count(),
                'completed'=>Task::where('status','completed')->count(),
                'overdue'=>Task::where('status','overdue')->count(),
            ],
        ]);
    }

    public function store(Request $request, Activity $activity)
    {
        $activity->tasks()->create($request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'assigned_to'=>['nullable','exists:users,id'],
            'start_date'=>['nullable','date'],
            'due_date'=>['nullable','date','after_or_equal:start_date'],
            'priority'=>['nullable','string','max:50'],
        ])+[
            'status'=>'not_started',
            'progress_percent'=>0,
        ]);

        return back()->with('success','Task created.');
    }

    public function update(Request $request, Task $task)
    {
        $task->update($request->validate([
            'status'=>['required','in:not_started,in_progress,returned_for_revision,completed,overdue'],
            'progress_percent'=>['required','numeric','min:0','max:100'],
        ]));

        return back()->with('success','Task updated.');
    }
}
