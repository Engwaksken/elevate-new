<?php
namespace App\Http\Controllers\Admin\ProgrammeManagement;

use App\Http\Controllers\Controller;
use App\Models\Workplan;
use App\Models\Activity;
use App\Services\CalendarSyncService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function store(Request $request, Workplan $workplan, CalendarSyncService $calendar)
    {
        $data=$request->validate([
            'milestone_id'=>['nullable','exists:milestones,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'cohort_id'=>['nullable','exists:cohorts,id'],
            'activity_code'=>['nullable','string','max:50'],
            'title'=>['required','string','max:190'],
            'description'=>['nullable','string'],
            'location'=>['nullable','string','max:190'],
            'start_date'=>['nullable','date'],
            'end_date'=>['nullable','date','after_or_equal:start_date'],
            'responsible_user_id'=>['nullable','exists:users,id'],
            'budget'=>['nullable','numeric','min:0'],
            'currency'=>['nullable','string','size:3'],
            'funding_source'=>['nullable','string','max:190'],
            'priority'=>['nullable','string','max:50'],
            'expected_output'=>['nullable','string'],
        ]);

        $activity=$workplan->activities()->create($data);

        if($activity->start_date){
            $calendar->syncFromModel($activity,'activity',$activity->title,$activity->start_date->startOfDay(),$activity->end_date?->endOfDay());
        }

        return back()->with('success','Activity created.');
    }

    public function updateProgress(Request $request, Activity $activity)
    {
        $activity->update($request->validate([
            'status'=>['required','in:planned,not_started,in_progress,delayed,completed,cancelled'],
            'progress_percent'=>['required','numeric','min:0','max:100'],
            'actual_output'=>['nullable','string'],
            'challenges'=>['nullable','string'],
            'lessons_learned'=>['nullable','string'],
            'next_action'=>['nullable','string'],
        ]));
        return back()->with('success','Activity progress updated.');
    }
}
