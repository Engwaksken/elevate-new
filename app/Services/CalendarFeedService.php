<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\CourseTimeSlot;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\InstructorAppointment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class CalendarFeedService
{
    public function entries(User $user, CarbonInterface $from, CarbonInterface $to, array $filters = []): Collection
    {
        $zone = config('app.timezone', 'UTC');
        $enrolments = Enrolment::where('user_id', $user->id)->whereIn('status', ['enrolled', 'active', 'in_progress', 'completed'])->get();
        $courseIds = $enrolments->pluck('course_id');
        $cohortIds = $enrolments->pluck('cohort_id')->filter();
        $type = $filters['event_type'] ?? null;
        $programme = $filters['programme_id'] ?? null;
        $entries = collect();

        if (! $type || $type === 'course_timetable') {
            $slots = CourseTimeSlot::with('course')
                ->where('starts_at', '>=', $from->copy()->utc())->where('starts_at', '<', $to->copy()->utc())
                ->whereHas('course', function ($query) use ($user, $courseIds, $programme) {
                    if (! $user->isStaff()) $query->where('status', 'published')->whereIn('id', $courseIds);
                    if ($programme) $query->where('programme_id', $programme);
                })->get();
            foreach ($slots as $slot) {
                $entries->push($this->entry('timetable-'.$slot->id, $slot->title, 'course_timetable', $slot->starts_at, $slot->ends_at, $zone, [
                    'course_title' => $slot->course->title, 'venue' => $slot->venue, 'status' => $slot->status,
                    'url' => $user->isStaff() ? route('admin.elearning.timetable.index', ['course_id'=>$slot->course_id]) : route('learning.course.dashboard', $slot->course),
                    'meeting_link' => $slot->status === 'scheduled' ? $slot->meeting_link : null,
                    'source_timezone' => $slot->timezone,
                ]));
            }
        }

        $general = CalendarEvent::where('starts_at', '>=', $from)->where('starts_at', '<', $to)
            // Live sources below replace mirrored rows and cannot drift after edits.
            ->where(fn ($q) => $q->whereNull('eventable_type')->orWhereNotIn('eventable_type', [(new CourseTimeSlot())->getMorphClass(), (new Event())->getMorphClass()]));
        if ($type) $general->where('event_type', $type);
        if ($programme) $general->where('programme_id', $programme);
        if (! $user->isStaff()) {
            $general->where(fn ($q) => $q->whereNull('responsible_user_id')->orWhere('responsible_user_id', $user->id))
                ->where(fn ($q) => $q->whereNull('cohort_id')->orWhereIn('cohort_id', $cohortIds));
        }
        foreach ($general->get() as $event) {
            $entries->push($this->entry('calendar-'.$event->id, $event->title, $event->event_type, $event->starts_at, $event->ends_at, $zone, [
                'venue'=>$event->venue, 'status'=>$event->status, 'url'=>null, 'meeting_link'=>null,
            ]));
        }

        if (! $type || $type === 'appointment') {
            // Approved instructor appointments are private to the two people involved.
            $appointments = InstructorAppointment::with(['participant:id,name', 'instructor:id,name', 'course:id,title'])
                ->forUser($user)
                ->whereIn('status', [InstructorAppointment::APPROVED, InstructorAppointment::COMPLETED])
                ->where('starts_at', '>=', $from)->where('starts_at', '<', $to)
                ->when($programme, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('programme_id', $programme)))
                ->get();
            foreach ($appointments as $appointment) {
                $isInstructor = $appointment->instructor_user_id === $user->id;
                $other = $isInstructor ? $appointment->participant?->name : $appointment->instructor?->name;
                $entries->push($this->entry('appointment-'.$appointment->id, 'Appointment: '.$appointment->topic.($other ? ' with '.$other : ''), 'appointment', $appointment->starts_at, $appointment->ends_at, $zone, [
                    'course_title' => $appointment->course?->title,
                    'venue' => $appointment->location,
                    'status' => $appointment->status === InstructorAppointment::COMPLETED ? 'completed' : 'scheduled',
                    'url' => $isInstructor
                        ? route('instructor.appointments.index', ['tab' => $appointment->ends_at->isPast() ? 'past' : 'upcoming'])
                        : route('appointments.index'),
                    'meeting_link' => $appointment->status === InstructorAppointment::APPROVED ? $appointment->meeting_url : null,
                ]));
            }
        }

        $events = Event::where('starts_at', '>=', $from)->where('starts_at', '<', $to);
        if ($type) $events->where('event_type', $type);
        if ($programme) $events->where(fn ($q) => $q->whereHas('course', fn ($c) => $c->where('programme_id', $programme))->orWhereHas('cohort', fn ($c) => $c->where('programme_id', $programme)));
        if (! $user->isStaff()) {
            $events->where('is_published', true)
                ->where(fn ($q) => $q->whereNull('course_id')->orWhereIn('course_id', $courseIds))
                ->where(fn ($q) => $q->whereNull('cohort_id')->orWhereIn('cohort_id', $cohortIds));
        }
        foreach ($events->get() as $event) {
            $entries->push($this->entry('event-'.$event->id, $event->title, $event->event_type, $event->starts_at, $event->ends_at, $zone, [
                'venue'=>$event->venue, 'status'=>$event->is_published ? 'scheduled' : 'draft',
                'url'=>$user->isStaff() ? route('admin.events.view', $event) : route('events.show', $event), 'meeting_link'=>null,
            ]));
        }

        return $entries->sortBy(fn ($entry) => $entry['starts_at']->getTimestamp())->values();
    }

    private function entry(string $id, string $title, string $type, CarbonInterface $start, ?CarbonInterface $end, string $zone, array $extra): array
    {
        return $extra + ['id'=>$id, 'title'=>$title, 'event_type'=>$type, 'course_title'=>null,
            'starts_at'=>CarbonImmutable::instance($start)->setTimezone($zone),
            'ends_at'=>$end ? CarbonImmutable::instance($end)->setTimezone($zone) : null,
            'status'=>'scheduled', 'source_timezone'=>$zone];
    }
}
