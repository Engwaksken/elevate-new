<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCertificate;
use App\Models\EventFeedbackResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class EventEngagementController extends Controller
{
    public function feedback(Event $event)
    {
        abort_unless($event->is_published && $event->feedback_enabled,404);

        $registration=$event->registrations()
            ->where('user_id',auth()->id())
            ->firstOrFail();

        $attendance=$event->attendanceRecords()
            ->where('user_id',auth()->id())
            ->whereIn('attendance_status',['present','late'])
            ->first();

        abort_unless($attendance,403,'Feedback is available after attendance has been recorded.');

        return view('events.feedback',[
            'event'=>$event,
            'response'=>EventFeedbackResponse::firstOrNew([
                'event_id'=>$event->id,
                'user_id'=>auth()->id(),
            ]),
        ]);
    }

    public function saveFeedback(Request $request,Event $event)
    {
        abort_unless($event->is_published && $event->feedback_enabled,404);

        $registration=$event->registrations()
            ->where('user_id',auth()->id())
            ->firstOrFail();

        abort_unless(
            $event->attendanceRecords()
                ->where('user_id',auth()->id())
                ->whereIn('attendance_status',['present','late'])
                ->exists(),
            403,
            'Feedback is available after attendance has been recorded.'
        );

        $data=$request->validate([
            'overall_rating'=>['required','integer','between:1,5'],
            'relevance_rating'=>['required','integer','between:1,5'],
            'facilitation_rating'=>['required','integer','between:1,5'],
            'organisation_rating'=>['required','integer','between:1,5'],
            'recommend_rating'=>['required','integer','between:1,5'],
            'key_learning'=>['nullable','string','max:5000'],
            'what_worked'=>['nullable','string','max:5000'],
            'what_to_improve'=>['nullable','string','max:5000'],
            'additional_comments'=>['nullable','string','max:5000'],
        ]);

        EventFeedbackResponse::updateOrCreate(
            ['event_id'=>$event->id,'user_id'=>auth()->id()],
            $data+[
                'event_registration_id'=>$registration->id,
                'submitted_at'=>now(),
            ]
        );

        return redirect()->route('events.show',$event)->with('success','Thank you. Your event feedback has been submitted.');
    }

    public function certificate(Event $event)
    {
        abort_unless($event->certificate_enabled,404);

        $attendance=$event->attendanceRecords()
            ->where('user_id',auth()->id())
            ->whereIn('attendance_status',['present','late'])
            ->firstOrFail();

        if($event->certificate_requires_feedback){
            abort_unless(
                EventFeedbackResponse::where('event_id',$event->id)
                    ->where('user_id',auth()->id())
                    ->exists(),
                403,
                'Submit event feedback before downloading your certificate.'
            );
        }

        $certificate=EventCertificate::firstOrCreate(
            ['event_id'=>$event->id,'user_id'=>auth()->id()],
            [
                'event_attendance_record_id'=>$attendance->id,
                'certificate_code'=>(string)Str::uuid(),
                'issued_at'=>now(),
            ]
        );

        $pdf=Pdf::loadView('events.certificates.pdf',[
            'event'=>$event,
            'user'=>auth()->user(),
            'certificate'=>$certificate,
        ])->setPaper('a4','landscape');

        return $pdf->download('event-certificate-'.$event->id.'-'.$certificate->certificate_code.'.pdf');
    }

    public function verify(string $code)
    {
        $certificate=EventCertificate::with(['event','user'])
            ->where('certificate_code',$code)
            ->firstOrFail();

        return view('events.certificates.verify',compact('certificate'));
    }
}
