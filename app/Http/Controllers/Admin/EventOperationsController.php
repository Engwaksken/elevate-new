<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\AttendanceSession;
use App\Models\Event;
use App\Models\EventAttendanceRecord;
use App\Models\EventReminder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventOperationsController extends Controller
{
    public function show(Event $event)
    {
        $event->load(['registrations.user','attendanceRecords','cohort','course']);
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
        ]);
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

        return back()->with('success','Event reminder saved.');
    }

    public function deleteReminder(Event $event, EventReminder $reminder)
    {
        abort_unless((int)$reminder->event_id===(int)$event->id,404);
        $reminder->delete();
        return back()->with('success','Event reminder deleted.');
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