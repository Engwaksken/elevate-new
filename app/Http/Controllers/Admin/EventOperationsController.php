<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventAttendanceRecord;
use App\Models\EventInvitation;
use App\Models\EventReminder;
use App\Services\EventInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventOperationsController extends Controller
{
    public function show(Event $event, EventInvitationService $invitations)
    {
        $event->load(['registrations.user','attendanceRecords','cohort','course','invitations.user','invitations.inviter']);
        $reminders=EventReminder::where('event_id',$event->id)->orderByDesc('minutes_before')->get();

        $attendance=$event->attendanceRecords;
        $registered=$event->registrations->count();
        $attended=$attendance->whereIn('attendance_status',['present','late'])->count();

        return view('admin.events.show',[
            'event'=>$event,
            'reminders'=>$reminders,
            'registered'=>$registered,
            'attended'=>$attended,
            'attendanceRate'=>$registered>0 ? round(($attended/$registered)*100,1) : 0,
            'people'=>$invitations->people($event),
            'inviteGroups'=>$invitations->groups($event),
        ]);
    }

    /** People-picker search for event invitations (?event= enables the course / cohort groups). */
    public function people(Request $request, EventInvitationService $invitations)
    {
        $data=$request->validate([
            'q'=>['nullable','string','max:100'],
            'group'=>['nullable','string','max:40'],
            'event'=>['nullable','integer'],
            'exclude'=>['nullable','array','max:1000'],
            'exclude.*'=>['integer'],
        ]);
        $event=isset($data['event']) ? Event::find($data['event']) : null;

        return response()->json([
            'people'=>$invitations->search($data['q'] ?? null,$data['group'] ?? null,$event,$data['exclude'] ?? []),
        ]);
    }

    public function invite(Request $request, Event $event, EventInvitationService $invitations)
    {
        $data=$request->validate([
            'invitee_ids'=>['required','array','min:1','max:1000'],
            'invitee_ids.*'=>['integer','distinct'],
            'invitation_note'=>['nullable','string','max:2000'],
        ],['invitee_ids.required'=>'Choose at least one person to invite.']);
        $count=$invitations->invite($event,$data['invitee_ids'],$request->user(),$data['invitation_note'] ?? null);

        return redirect()->to(route('admin.events.view',$event).'#people')
            ->with('success',$count ? "Invitation sent to {$count} ".str('person')->plural($count).'.' : 'Everyone selected was already invited.');
    }

    public function revokeInvitation(Event $event, EventInvitation $invitation)
    {
        abort_unless((int)$invitation->event_id===(int)$event->id,404);
        $invitation->delete();

        return redirect()->to(route('admin.events.view',$event).'#people')->with('success','Invitation withdrawn.');
    }

    public function appreciate(Request $request, Event $event, EventInvitationService $invitations)
    {
        $data=$request->validate([
            'user_ids'=>['required','array','min:1','max:2000'],
            'user_ids.*'=>['integer','distinct'],
            'title'=>['required','string','max:190'],
            'message'=>['required','string','max:5000'],
        ],['user_ids.required'=>'Select at least one person to thank.']);
        $count=$invitations->appreciate($event,$data['user_ids'],$request->user(),trim($data['title']),trim($data['message']),$request->boolean('send_email'));

        return redirect()->to(route('admin.events.view',$event).'#people')
            ->with('success',"Appreciation message sent to {$count} ".str('person')->plural($count).'.');
    }

    public function saveReminder(Request $request, Event $event)
    {
        $data=$request->validate([
            'preset'=>['nullable','in:same_day,1_day,2_days,3_days,7_days,14_days,custom'],
            'custom_value'=>['nullable','integer','min:1'],
            'custom_unit'=>['nullable','in:minutes,hours,days,weeks'],
            'delivery_method'=>['required','in:email,system,both'],
        ]);

        $minutes=match($data['preset'] ?? 'custom'){
            'same_day'=>180,
            '1_day'=>1440,
            '2_days'=>2880,
            '3_days'=>4320,
            '7_days'=>10080,
            '14_days'=>20160,
            default=>$this->customMinutes((int)($data['custom_value'] ?? 0),$data['custom_unit'] ?? 'minutes'),
        };

        abort_if($minutes<1,422,'Enter a valid reminder period.');

        EventReminder::updateOrCreate(
            ['event_id'=>$event->id,'minutes_before'=>$minutes],
            ['delivery_method'=>$data['delivery_method'],'is_active'=>true]
        );

        return redirect()->to(route('admin.events.view',$event).'#settings')->with('success','Event reminder saved.');
    }

    public function deleteReminder(Event $event, EventReminder $reminder)
    {
        abort_unless((int)$reminder->event_id===(int)$event->id,404);
        $reminder->delete();
        return redirect()->to(route('admin.events.view',$event).'#settings')->with('success','Event reminder deleted.');
    }

    public function csv(Event $event)
    {
        $event->load(['registrations.user','attendanceRecords']);
        $records=$event->attendanceRecords->keyBy('event_registration_id');

        $filename='event-attendance-'.$event->id.'.csv';

        return response()->streamDownload(function() use($event,$records){
            $out=fopen('php://output','w');
            fputcsv($out,['Participant','Email','Registration Status','Attendance Status','Check In','Notes']);

            foreach($event->registrations as $registration){
                $record=$records->get($registration->id);

                fputcsv($out,[
                    $registration->user?->name ?: 'Participant',
                    $registration->user?->email ?: '',
                    $registration->status,
                    $record?->attendance_status ?: 'not_recorded',
                    optional($record?->check_in_at)->format('Y-m-d H:i:s'),
                    $record?->notes,
                ]);
            }

            fclose($out);
        },$filename,['Content-Type'=>'text/csv']);
    }

    public function analytics(Request $request)
    {
        $from=$request->date('from') ?: now()->startOfMonth();
        $to=$request->date('to') ?: now()->endOfMonth();

        $courseSessions=AttendanceSession::query()
            ->whereBetween('session_date',[$from->toDateString(),$to->toDateString()])
            ->pluck('id');

        $courseRecords=AttendanceRecord::query()
            ->whereIn('attendance_session_id',$courseSessions)
            ->get();

        $eventIds=Event::query()
            ->whereBetween('starts_at',[$from->copy()->startOfDay(),$to->copy()->endOfDay()])
            ->pluck('id');

        $eventRecords=EventAttendanceRecord::query()
            ->whereIn('event_id',$eventIds)
            ->get();

        $coursePresent=$courseRecords->whereIn('status',['present','late'])->count();
        $eventPresent=$eventRecords->whereIn('attendance_status',['present','late'])->count();

        return view('admin.attendance.analytics',[
            'from'=>$from,
            'to'=>$to,
            'stats'=>[
                'course_sessions'=>$courseSessions->count(),
                'course_records'=>$courseRecords->count(),
                'course_present'=>$coursePresent,
                'event_records'=>$eventRecords->count(),
                'event_present'=>$eventPresent,
                'total_present'=>$coursePresent+$eventPresent,
            ],
            'courseBreakdown'=>$courseRecords->groupBy('status')->map->count(),
            'eventBreakdown'=>$eventRecords->groupBy('attendance_status')->map->count(),
        ]);
    }

    public function analyticsCsv(Request $request)
    {
        $from=$request->date('from') ?: now()->startOfMonth();
        $to=$request->date('to') ?: now()->endOfMonth();

        return response()->streamDownload(function() use($from,$to){
            $out=fopen('php://output','w');
            fputcsv($out,['Source','Date','Reference','Participant/User ID','Status']);

            $sessions=AttendanceSession::whereBetween('session_date',[$from->toDateString(),$to->toDateString()])->get()->keyBy('id');
            $records=AttendanceRecord::whereIn('attendance_session_id',$sessions->keys())->get();

            foreach($records as $record){
                $session=$sessions->get($record->attendance_session_id);
                fputcsv($out,['Course',$session?->session_date?->format('Y-m-d'),$session?->title,$record->user_id,$record->status]);
            }

            $events=Event::whereBetween('starts_at',[$from->copy()->startOfDay(),$to->copy()->endOfDay()])->get()->keyBy('id');
            $eventRecords=EventAttendanceRecord::whereIn('event_id',$events->keys())->get();

            foreach($eventRecords as $record){
                $event=$events->get($record->event_id);
                fputcsv($out,['Event',$event?->starts_at?->format('Y-m-d'),$event?->title,$record->user_id,$record->attendance_status]);
            }

            fclose($out);
        },'attendance-analytics-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv',['Content-Type'=>'text/csv']);
    }

    private function customMinutes(int $value,string $unit): int
    {
        return match($unit){
            'hours'=>$value*60,
            'days'=>$value*1440,
            'weeks'=>$value*10080,
            default=>$value,
        };
    }
}