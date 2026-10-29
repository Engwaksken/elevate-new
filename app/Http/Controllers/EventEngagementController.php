<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\CertificateTemplate;
use App\Models\EventCertificate;
use App\Models\EventFeedbackResponse;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\CourseFeedbackResponse;
use App\Support\PeriodFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class EventEngagementController extends Controller
{
    public function feedbackIndex()
    {
        $events = Event::query()
            ->where('is_published', true)
            ->where('feedback_enabled', true)
            ->whereHas('registrations', fn ($query) => $query->where('user_id', auth()->id()))
            ->latest('starts_at')
            ->get();

        $courses = Course::query()
            ->whereHas('enrolments', fn ($query) => $query->where('user_id', auth()->id()))
            ->with(['enrolments' => fn ($query) => $query->where('user_id', auth()->id())])
            ->orderBy('title')
            ->get();

        $courseFeedbackByCourseId = CourseFeedbackResponse::where('user_id', auth()->id())
            ->whereIn('course_id', $courses->pluck('id'))
            ->get()
            ->keyBy('course_id');
        $courseFeedbackIds = $courseFeedbackByCourseId->keys()->all();
        $eventFeedbackByEventId = EventFeedbackResponse::where('user_id', auth()->id())
            ->whereIn('event_id', $events->pluck('id'))
            ->get()
            ->keyBy('event_id');

        // One list of everything she can rate, so it can be searched, filtered by period and paginated.
        $items = $courses->map(fn ($course) => [
            'type' => 'course', 'id' => $course->id, 'title' => $course->title,
            'date' => $course->enrolments->first()?->enrolled_at ?? $course->enrolments->first()?->created_at,
            'response' => $courseFeedbackByCourseId->get($course->id),
        ])->concat($events->map(fn ($event) => [
            'type' => 'event', 'id' => $event->id, 'title' => $event->title,
            'date' => $event->starts_at,
            'response' => $eventFeedbackByEventId->get($event->id),
        ]));

        $stats = [
            'courses_waiting' => $items->where('type', 'course')->whereNull('response')->count(),
            'events_waiting' => $items->where('type', 'event')->whereNull('response')->count(),
            'submitted' => $items->whereNotNull('response')->count(),
            'average' => ($rated = $items->pluck('response')->filter())->isNotEmpty() ? round($rated->avg('overall_rating'), 1) : null,
        ];

        $request = request();
        $search = mb_strtolower(trim((string) $request->query('search')));
        [$from, $to] = PeriodFilter::range($request);
        $filtered = $items
            ->when($search !== '', fn ($c) => $c->filter(fn ($item) => str_contains(mb_strtolower($item['title']), $search)))
            ->when(in_array($request->query('type'), ['course', 'event'], true), fn ($c) => $c->where('type', $request->query('type')))
            ->when($request->query('status') === 'waiting', fn ($c) => $c->whereNull('response'))
            ->when($request->query('status') === 'submitted', fn ($c) => $c->whereNotNull('response'))
            ->when($from, fn ($c) => $c->filter(fn ($item) => $item['date'] && $item['date']->gte($from)))
            ->when($to, fn ($c) => $c->filter(fn ($item) => $item['date'] && $item['date']->lte($to)))
            // Things still waiting for feedback come first, then the most recent.
            ->sortBy([fn ($a, $b) => (int) ($a['response'] !== null) <=> (int) ($b['response'] !== null), fn ($a, $b) => ($b['date']?->getTimestamp() ?? 0) <=> ($a['date']?->getTimestamp() ?? 0)])
            ->values();

        $page = max(1, (int) $request->query('page', 1));
        $feedbackItems = new \Illuminate\Pagination\LengthAwarePaginator($filtered->forPage($page, 15)->values(), $filtered->count(), 15, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        return view('events.feedback-index', compact(
            'events', 'courses', 'courseFeedbackIds', 'courseFeedbackByCourseId', 'eventFeedbackByEventId', 'feedbackItems', 'stats'
        ));
    }

    public function courseFeedback(Course $course)
    {
        $enrolment = Enrolment::where('course_id', $course->id)->where('user_id', auth()->id())->firstOrFail();

        return view('events.course-feedback', [
            'course' => $course,
            'response' => CourseFeedbackResponse::firstOrNew(['course_id' => $course->id, 'user_id' => auth()->id()]),
        ]);
    }

    public function saveCourseFeedback(Request $request, Course $course)
    {
        $enrolment = Enrolment::where('course_id', $course->id)->where('user_id', auth()->id())->firstOrFail();

        $data = $request->validate([
            'overall_rating' => ['required', 'integer', 'between:1,5'],
            'content_rating' => ['required', 'integer', 'between:1,5'],
            'instructor_rating' => ['required', 'integer', 'between:1,5'],
            'recommend_rating' => ['required', 'integer', 'between:1,5'],
            'key_learning' => ['nullable', 'string', 'max:5000'],
            'what_to_improve' => ['nullable', 'string', 'max:5000'],
            'additional_comments' => ['nullable', 'string', 'max:5000'],
        ]);

        CourseFeedbackResponse::updateOrCreate(
            ['course_id' => $course->id, 'user_id' => auth()->id()],
            $data + ['enrolment_id' => $enrolment->id, 'submitted_at' => now()]
        );

        return redirect()->route('participant.feedback.index')->with('success', 'Thank you. Your course feedback has been submitted.');
    }

    public function feedback(Event $event)
    {
        abort_unless($event->is_published && $event->feedback_enabled,404);

        $registration=$event->registrations()
            ->where('user_id',auth()->id())
            ->firstOrFail();

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

        return redirect()->route('participant.feedback.index')->with('success','Thank you. Your event feedback has been submitted.');
    }

    public function certificate(Event $event)
    {
        // A certificate issued by staff (e.g. an approved recommendation) is always downloadable.
        $certificate=EventCertificate::where('event_id',$event->id)
            ->where('user_id',auth()->id())
            ->whereNotNull('issued_by')
            ->first();

        if(! $certificate){
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
        }

        $template=CertificateTemplate::resolveFor(null,$event->id);

        $pdf=Pdf::loadView('events.certificates.pdf',[
            'event'=>$event,
            'user'=>auth()->user(),
            'certificate'=>$certificate,
            'template'=>$template,
        ])->setPaper('a4',$template?->orientation ?: 'landscape');

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
