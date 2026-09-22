<?php
namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Deliverable;
use Illuminate\Http\Request;

class DeliverableController extends Controller
{
    public function index()
    {
        return view('admin.deliverables.index',[
            'deliverables'=>Deliverable::latest()->paginate(25)
        ]);
    }

    public function store(Request $request, Activity $activity)
    {
        $activity->deliverables()->create($request->validate([
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'owner_user_id'=>['nullable','exists:users,id'],
            'due_date'=>['nullable','date'],
        ]));
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
