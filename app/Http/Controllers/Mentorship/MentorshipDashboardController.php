<?php
namespace App\Http\Controllers\Mentorship;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MentorMatch;
use App\Models\MentorshipGoal;
use App\Models\MentorshipSession;
use App\Models\CareerActionItem;
use App\Models\CareerMentorMessage;
use App\Models\ParticipantGoal;
use App\Support\PeriodFilter;
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
        $hasMentees=$menteeIds->isNotEmpty();
        // Mentee goals tab: search (goal, description or mentee name) and a period on the target date.
        $menteeGoalsQuery=ParticipantGoal::with('user:id,name','mentorReviewer:id,name')->whereIn('user_id',$menteeIds)
            ->when(trim((string)$request->query('mg_q'))!=='',function($q)use($request){
                $term='%'.trim((string)$request->query('mg_q')).'%';
                $q->where(fn($w)=>$w->where('title','like',$term)->orWhere('description','like',$term)->orWhereHas('user',fn($u)=>$u->where('name','like',$term)));
            })->orderByDesc('id');
        PeriodFilter::apply($menteeGoalsQuery,'target_date',$request,'mg_');
        $menteeGoals=$hasMentees?(clone $menteeGoalsQuery)->paginate(10,['*'],'mg_page')->withQueryString()->fragment('mentee-goals'):collect();
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
                'mentee-goals'=>$this->exportTable($format,'Mentee Goals',$menteeGoalsQuery->get(),['Mentee'=>'user.name']+$goalColumns,[]),
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

        $aiEnabled=(bool)\App\Models\AiIntegration::activeSystem()?->encrypted_api_key;
        // AI Career Mentor history, saved answers and the approved action plan.
        $aiHistory=$aiEnabled?\App\Models\CareerMentorMessage::with('actions.goal')->where('user_id',$userId)->latest('id')->take(30)->get()->reverse()->values()->map->toChatArray()->all():[];
        // Saved answers tab: search the answer text, period on the date it was saved.
        $savedQuery=CareerMentorMessage::where('user_id',$userId)->where('role','assistant')->whereNotNull('saved_at')
            ->when(trim((string)$request->query('saved_q'))!=='',fn($q)=>$q->where('content','like','%'.trim((string)$request->query('saved_q')).'%'))
            ->latest('saved_at');
        PeriodFilter::apply($savedQuery,'saved_at',$request,'saved_');
        $aiSaved=$aiEnabled?$savedQuery->paginate(10,['*'],'saved_page')->withQueryString()->fragment('saved-answers'):collect();
        // Action plan tab: search step or goal, status, period on the due date.
        $planQuery=CareerActionItem::with('goal')->where('user_id',$userId)
            ->whereIn('status',in_array($request->query('plan_status'),[CareerActionItem::APPROVED,CareerActionItem::COMPLETED],true)?[$request->query('plan_status')]:[CareerActionItem::APPROVED,CareerActionItem::COMPLETED])
            ->when(trim((string)$request->query('plan_q'))!=='',function($q)use($request){
                $term='%'.trim((string)$request->query('plan_q')).'%';
                $q->where(fn($w)=>$w->where('title','like',$term)->orWhereHas('goal',fn($g)=>$g->where('title','like',$term)));
            })
            ->orderByRaw("status = 'completed'")->orderBy('due_on');
        PeriodFilter::apply($planQuery,'due_on',$request,'plan_');
        $actionPlan=$aiEnabled?$planQuery->paginate(10,['*'],'plan_page')->withQueryString()->fragment('action-plan'):collect();
        $completedSessions=$sessions->filter(fn($s)=>in_array(strtolower((string)$s->status),['completed','done'],true))->count();
        $upcomingSessions=$sessions->filter(fn($s)=>$s->scheduled_at&&$s->scheduled_at->isFuture()&&!in_array(strtolower((string)$s->status),['completed','cancelled'],true))->count();
        $achieved=$goals->filter(fn($g)=>(float)($g->progress_percent??0)>=100||in_array(strtolower((string)$g->status),['completed','achieved'],true))->count();
        $inProgress=$goals->filter(fn($g)=>(float)($g->progress_percent??0)>0&&(float)($g->progress_percent??0)<100&&!in_array(strtolower((string)$g->status),['completed','achieved','cancelled'],true))->count();
        return view('mentorship.dashboard',compact('mentorMatches','menteeMatches','sessions','goals','menteeGoals','hasMentees','myGoals','myGoalStats','aiEnabled','aiHistory','aiSaved','actionPlan')+['stats'=>['mentors'=>$menteeMatches->count(),'sessions'=>$sessions->count(),'completed_sessions'=>$completedSessions,'upcoming_sessions'=>$upcomingSessions,'goals'=>$goals->count(),'goals_in_progress'=>$inProgress,'goals_achieved'=>$achieved,'goal_progress'=>$goals->count()?round((float)$goals->avg('progress_percent'),1):0]]);
    }
}