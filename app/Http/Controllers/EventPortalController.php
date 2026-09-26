<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventFeedbackResponse;
use App\Models\EventRegistration;
use Illuminate\Http\Request;

class EventPortalController extends Controller
{
    public function index(Request $request)
    {
        $query=Event::where('is_published',true)->where('starts_at','>=',now()->subDay());

        if($search=trim((string)$request->get('search'))){
            $query->where(fn($q)=>$q->where('title','like',"%{$search}%")
                ->orWhere('description','like',"%{$search}%")
                ->orWhere('venue','like',"%{$search}%"));
        }

        return view('events.index',[
            'events'=>$query->orderBy('starts_at')->paginate(12)->withQueryString(),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->is_published,404);

        $registered=auth()->check()
            ? $event->registrations()->where('user_id',auth()->id())->exists()
            : false;

        $attended=auth()->check()
            ? $event->attendanceRecords()
                ->where('user_id',auth()->id())
                ->whereIn('attendance_status',['present','late'])
                ->exists()
            : false;

        $feedbackSubmitted=auth()->check()
            ? EventFeedbackResponse::where('event_id',$event->id)
                ->where('user_id',auth()->id())
                ->exists()
            : false;

        return view('events.show',compact('event','registered','attended','feedbackSubmitted'));
    }

    public function register(Event $event)
    {
        abort_unless($event->is_published,404);

        if($event->capacity && $event->registrations()->count()>=$event->capacity){
            return back()->with('error','This event has reached capacity.');
        }

        EventRegistration::firstOrCreate(
            ['event_id'=>$event->id,'user_id'=>auth()->id()],
            ['status'=>'registered','registered_at'=>now()]
        );

        return back()->with('success','You are registered for this event.');
    }
}
