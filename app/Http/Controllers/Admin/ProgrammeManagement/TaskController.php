<?php
namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Task;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index()
    {
        return view('admin.tasks.index',[
            'tasks'=>Task::latest()->paginate(25)
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
        ]));
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
