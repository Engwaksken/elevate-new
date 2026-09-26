<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventAttendanceRecord;
use App\Models\EventRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventCheckinController extends Controller
{
    public function checkin(Request $request, Event $event, string $token)
    {
        abort_unless($event->is_published,404);
        abort_unless(hash_equals((string)$event->checkin_token,$token),403,'Invalid event check-in code.');

        $registration=EventRegistration::firstOrCreate(
            ['event_id'=>$event->id,'user_id'=>auth()->id()],
            ['status'=>'registered','registered_at'=>now()]
        );

        EventAttendanceRecord::updateOrCreate(
            ['event_id'=>$event->id,'event_registration_id'=>$registration->id],
            [
                'user_id'=>auth()->id(),
                'attendance_status'=>'present',
                'check_in_at'=>now(),
                'recorded_by'=>auth()->id(),
            ]
        );

        return redirect()->route('events.show',$event)->with('success','Event check-in recorded.');
    }

    public function calendar(Event $event)
    {
        abort_unless($event->is_published,404);

        $escape=fn($value)=>str_replace(["\\",",",";","\n"],["\\\\","\\,","\\;","\\n"],(string)$value);
        $start=$event->starts_at->copy()->utc()->format('Ymd\THis\Z');
        $end=($event->ends_at ?: $event->starts_at->copy()->addHour())->copy()->utc()->format('Ymd\THis\Z');

        $ics="BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ElevateHer360//Events//EN\r\n";
        $ics.="BEGIN:VEVENT\r\nUID:event-{$event->id}@elevateher360\r\n";
        $ics.="DTSTAMP:".now()->utc()->format('Ymd\THis\Z')."\r\n";
        $ics.="DTSTART:{$start}\r\nDTEND:{$end}\r\n";
        $ics.="SUMMARY:".$escape($event->title)."\r\n";
        $ics.="DESCRIPTION:".$escape($event->description)."\r\n";
        $ics.="LOCATION:".$escape($event->venue ?: $event->meeting_url)."\r\n";
        $ics.="END:VEVENT\r\nEND:VCALENDAR\r\n";

        return response($ics,200,[
            'Content-Type'=>'text/calendar; charset=utf-8',
            'Content-Disposition'=>'attachment; filename="event-'.$event->id.'.ics"',
        ]);
    }
}