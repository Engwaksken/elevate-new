<?php
namespace App\Http\Controllers\Mentorship;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MentorMatch;
use App\Models\MentorshipGoal;
use App\Models\MentorshipSession;
use App\Models\ParticipantGoal;
class MentorshipDashboardController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $userId=auth()->id();
        $mentorMatches=MentorMatch::with('mentee')->where('mentor_user_id',$userId)->whereIn('status',['active','pending'])->get();
        $menteeMatches=MentorMatch::with('mentor')->where('mentee_user_id',$userId)->whereIn('status',['active','pending'])->get();
        $matchIds=$mentorMatches->pluck('id')->merge($menteeMatches->pluck('id'))->unique()->values();
        $sessions=MentorshipSession::whereIn('mentor_match_id',$matchIds)->orderByDesc('scheduled_at')->get();
        $goals=MentorshipGoal::whereIn('mentor_match_id',$matchIds)->orderByDesc('id')->get();
        $menteeIds=$mentorMatches->pluck('mentee_user_id')->unique()->values();
        $menteeGoals=$menteeIds->isEmpty()?collect():ParticipantGoal::with('user:id,name','mentorReviewer:id,name')->whereIn('user_id',$menteeIds)->orderByDesc('id')->get();
        $myGoals=ParticipantGoal::where('user_id',$userId)->orderByDesc('id')->get();
        $myGoalStats=[
            'total'=>$myGoals->count(),
            'in_progress'=>$myGoals->where('status','in_progress')->count(),
            'completed'=>$myGoals->where('status','completed')->count(),
            'average'=>$myGoals->isEmpty()?0:round((float)$myGoals->avg('progress_percent'),1),
        ];
        if($format=$this->exportFormat($request)){
            $goalColumns=[
                'Goal'=>'title',
                'Category'=>'category',
                'Progress (%)'=>'progress_percent',
                'Status'=>fn($g)=>ucfirst(str_replace('_',' ',(string)$g->status)),
                'Target date'=>'target_date',
                'Mentor feedback'=>'mentor_comment',
            ];
            return match($request->query('list')){
                'goals'=>$this->exportTable($format,'My Goals',$myGoals,$goalColumns,[]),
                'mentee-goals'=>$this->exportTable($format,'Mentee Goals',$menteeGoals,['Mentee'=>'user.name']+$goalColumns,[]),
                default=>$this->exportTable($format,'Mentorship Sessions',$sessions->load(['match.mentor:id,name','match.mentee:id,name']),[
                    'Session'=>'title',
                    'Mentor'=>'match.mentor.name',
                    'Mentee'=>'match.mentee.name',
                    'Scheduled'=>'scheduled_at',
                    'Duration (min)'=>'duration_minutes',
                    'Venue'=>'venue',
                    'Status'=>'status',
                    'Agreed actions'=>'agreed_actions',
                    'Next session'=>'next_session_at',
                ],[]),
            };
        }

        $aiEnabled=\App\Models\AiIntegration::query()->where('feature','system_ai')->where('enabled',true)->whereNotNull('encrypted_api_key')->exists();
        $completedSessions=$sessions->filter(fn($s)=>in_array(strtolower((string)$s->status),['completed','done'],true))->count();
        $upcomingSessions=$sessions->filter(fn($s)=>$s->scheduled_at&&$s->scheduled_at->isFuture()&&!in_array(strtolower((string)$s->status),['completed','cancelled'],true))->count();
        $achieved=$goals->filter(fn($g)=>(float)($g->progress_percent??0)>=100||in_array(strtolower((string)$g->status),['completed','achieved'],true))->count();
        $inProgress=$goals->filter(fn($g)=>(float)($g->progress_percent??0)>0&&(float)($g->progress_percent??0)<100&&!in_array(strtolower((string)$g->status),['completed','achieved','cancelled'],true))->count();
        return view('mentorship.dashboard',compact('mentorMatches','menteeMatches','sessions','goals','menteeGoals','myGoals','myGoalStats','aiEnabled')+['stats'=>['mentors'=>$menteeMatches->count(),'sessions'=>$sessions->count(),'completed_sessions'=>$completedSessions,'upcoming_sessions'=>$upcomingSessions,'goals'=>$goals->count(),'goals_in_progress'=>$inProgress,'goals_achieved'=>$achieved,'goal_progress'=>$goals->count()?round((float)$goals->avg('progress_percent'),1):0]]);
    }
}