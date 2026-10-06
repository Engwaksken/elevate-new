<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCertificate;
use App\Models\EventFeedbackResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventEvaluationController extends Controller
{
    use ExportsTables;
    public function settings(Request $request,Event $event)
    {
        $data=$request->validate([
            'certificate_title'=>['nullable','string','max:190'],
            'certificate_signatory_name'=>['nullable','string','max:190'],
            'certificate_signatory_title'=>['nullable','string','max:190'],
            'feedback_enabled'=>['nullable','boolean'],
            'certificate_enabled'=>['nullable','boolean'],
            'certificate_requires_feedback'=>['nullable','boolean'],
        ]);

        $event->update([
            'feedback_enabled'=>$request->boolean('feedback_enabled'),
            'certificate_enabled'=>$request->boolean('certificate_enabled'),
            'certificate_requires_feedback'=>$request->boolean('certificate_requires_feedback'),
            'certificate_title'=>$data['certificate_title'] ?? null,
            'certificate_signatory_name'=>$data['certificate_signatory_name'] ?? null,
            'certificate_signatory_title'=>$data['certificate_signatory_title'] ?? null,
        ]);

        return back()->with('success','Event evaluation and certificate settings updated.');
    }

    public function feedback(Request $request,Event $event)
    {
        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Event Feedback: '.$event->title,
                EventFeedbackResponse::with('user')->where('event_id',$event->id)->latest('submitted_at'),[
                    'Participant'=>fn($r)=>$r->user?->name ?? 'Anonymous',
                    'Email'=>'user.email',
                    'Overall'=>'overall_rating',
                    'Relevance'=>'relevance_rating',
                    'Facilitation'=>'facilitation_rating',
                    'Organisation'=>'organisation_rating',
                    'Recommend'=>'recommend_rating',
                    'Key learning'=>'key_learning',
                    'Submitted'=>'submitted_at',
                ],null,[],fn($e)=>$e->filename('event-feedback-'.$event->id));
        }

        $responses=EventFeedbackResponse::with('user')
            ->where('event_id',$event->id)
            ->latest('submitted_at')
            ->paginate(25);

        return view('admin.events.feedback',[
            'event'=>$event,
            'responses'=>$responses,
            'stats'=>[
                'responses'=>EventFeedbackResponse::where('event_id',$event->id)->count(),
                'overall'=>round((float)EventFeedbackResponse::where('event_id',$event->id)->avg('overall_rating'),2),
                'relevance'=>round((float)EventFeedbackResponse::where('event_id',$event->id)->avg('relevance_rating'),2),
                'recommend'=>round((float)EventFeedbackResponse::where('event_id',$event->id)->avg('recommend_rating'),2),
            ],
        ]);
    }

    public function calendar(Request $request)
    {
        $request->validate(['month'=>['nullable','date_format:Y-m']]);
        $month=$request->get('month',now()->format('Y-m'));

        try {
            $start=\Carbon\Carbon::createFromFormat('!Y-m',$month,config('app.timezone'))->startOfMonth();
        } catch(\Throwable $e) {
            $start=now()->startOfMonth();
        }

        $end=$start->copy()->endOfMonth();
        $gridStart=$start->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
        $gridEnd=$end->copy()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);

        $events=app(\App\Services\CalendarFeedService::class)->entries($request->user(), $gridStart->copy()->startOfDay(), $gridEnd->copy()->addDay()->startOfDay())
            ->filter(fn ($entry) => str_starts_with($entry['id'], 'event-') || $entry['event_type'] === 'course_timetable')
            ->groupBy(fn($entry)=>$entry['starts_at']->format('Y-m-d'));

        return view('admin.events.calendar',compact('month','start','end','gridStart','gridEnd','events'));
    }

    public function reminderLogs(Request $request,Event $event)
    {
        $query=DB::table('event_reminder_deliveries as d')
            ->join('event_reminders as r','r.id','=','d.event_reminder_id')
            ->leftJoin('users as u','u.id','=','d.user_id')
            ->where('r.event_id',$event->id)
            ->select([
                'd.id','d.channel','d.sent_at','d.error_message',
                'u.name','u.email','r.minutes_before'
            ])
            ->orderByDesc('d.sent_at');

        if($channel=$request->get('channel')){
            $query->where('d.channel',$channel);
        }

        if($request->get('status')==='failed'){
            $query->whereNotNull('d.error_message');
        } elseif($request->get('status')==='sent'){
            $query->whereNull('d.error_message');
        }

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Event Reminder Logs: '.$event->title,$query,[
                'User'=>'name',
                'Email'=>'email',
                'Channel'=>fn($l)=>ucfirst((string)$l->channel),
                'Reminder'=>fn($l)=>$l->minutes_before>=1440 && $l->minutes_before%1440===0
                    ? ($l->minutes_before/1440).' day(s) before'
                    : $l->minutes_before.' minutes before',
                'Sent at'=>'sent_at',
                'Status'=>fn($l)=>$l->error_message ? 'Failed' : 'Sent',
                'Error'=>'error_message',
            ],null,[],fn($e)=>$e->filename('event-reminder-logs-'.$event->id));
        }

        return view('admin.events.reminder-logs',[
            'event'=>$event,
            'logs'=>$query->paginate(30)->withQueryString(),
        ]);
    }

    public function mealReport(Request $request)
    {
        $from=$request->date('from') ?: now()->startOfMonth();
        $to=$request->date('to') ?: now()->endOfMonth();

        $events=Event::withCount(['registrations','attendanceRecords'])
            ->whereBetween('starts_at',[$from->copy()->startOfDay(),$to->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        $feedback=EventFeedbackResponse::whereIn('event_id',$events->pluck('id'))->get();

        $rows=$events->map(function($event) use($feedback){
            $attended=$event->attendanceRecords()
                ->whereIn('attendance_status',['present','late'])
                ->count();

            $eventFeedback=$feedback->where('event_id',$event->id);

            return [
                'event'=>$event,
                'registered'=>$event->registrations_count,
                'attended'=>$attended,
                'attendance_rate'=>$event->registrations_count>0
                    ? round(($attended/$event->registrations_count)*100,1)
                    : 0,
                'feedback_count'=>$eventFeedback->count(),
                'overall_rating'=>round((float)$eventFeedback->avg('overall_rating'),2),
                'relevance_rating'=>round((float)$eventFeedback->avg('relevance_rating'),2),
                'recommend_rating'=>round((float)$eventFeedback->avg('recommend_rating'),2),
            ];
        });

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Event Participation & Evaluation',$rows,[
                'Event'=>'event.title',
                'Type'=>fn($r)=>ucwords(str_replace('_',' ',(string)$r['event']->event_type)),
                'Date'=>fn($r)=>$r['event']->starts_at?->format('Y-m-d'),
                'Registered'=>'registered',
                'Attended'=>'attended',
                'Attendance rate %'=>'attendance_rate',
                'Feedback responses'=>'feedback_count',
                'Overall rating'=>'overall_rating',
                'Relevance rating'=>'relevance_rating',
                'Recommend rating'=>'recommend_rating',
            ],['From'=>$from->format('Y-m-d'),'To'=>$to->format('Y-m-d')]);
        }

        return view('admin.events.meal-report',[
            'from'=>$from,
            'to'=>$to,
            'rows'=>$rows,
            'stats'=>[
                'events'=>$events->count(),
                'registered'=>$rows->sum('registered'),
                'attended'=>$rows->sum('attended'),
                'feedback'=>$rows->sum('feedback_count'),
                'avg_satisfaction'=>round((float)$feedback->avg('overall_rating'),2),
            ],
        ]);
    }

    public function mealCsv(Request $request)
    {
        $from=$request->date('from') ?: now()->startOfMonth();
        $to=$request->date('to') ?: now()->endOfMonth();

        $events=Event::withCount(['registrations','attendanceRecords'])
            ->whereBetween('starts_at',[$from->copy()->startOfDay(),$to->copy()->endOfDay()])
            ->orderBy('starts_at')
            ->get();

        return response()->streamDownload(function() use($events){
            $out=fopen('php://output','w');
            fputcsv($out,[
                'Event','Type','Date','Registered','Attended','Attendance Rate %',
                'Feedback Responses','Overall Rating','Relevance Rating','Recommend Rating'
            ]);

            foreach($events as $event){
                $attended=$event->attendanceRecords()
                    ->whereIn('attendance_status',['present','late'])
                    ->count();

                $feedback=EventFeedbackResponse::where('event_id',$event->id)->get();

                fputcsv($out,[
                    $event->title,
                    $event->event_type,
                    $event->starts_at->format('Y-m-d'),
                    $event->registrations_count,
                    $attended,
                    $event->registrations_count>0 ? round(($attended/$event->registrations_count)*100,1) : 0,
                    $feedback->count(),
                    round((float)$feedback->avg('overall_rating'),2),
                    round((float)$feedback->avg('relevance_rating'),2),
                    round((float)$feedback->avg('recommend_rating'),2),
                ]);
            }

            fclose($out);
        },'event-meal-report-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv',[
            'Content-Type'=>'text/csv',
        ]);
    }
}
