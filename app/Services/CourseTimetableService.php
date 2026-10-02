<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseTimeSlot;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseTimetableService
{
    public function save(Course $course, array $data, User $user, ?CourseTimeSlot $slot = null): CourseTimeSlot
    {
        $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['session_date'].' '.$data['start_time'], $data['timezone']);
        $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['session_date'].' '.$data['end_time'], $data['timezone']);
        // Reject local times that are silently normalized at daylight-saving transitions.
        if ($start->format('Y-m-d H:i') !== $data['session_date'].' '.$data['start_time']
            || $end->format('Y-m-d H:i') !== $data['session_date'].' '.$data['end_time']
            || $end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages(['end_time' => 'Enter valid local times with the end after the start.']);
        }

        return DB::transaction(function () use ($course, $data, $user, $slot, $start, $end) {
            // Serialize timetable changes to prevent concurrent overlapping bookings.
            Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
            $conflict = $course->timeSlots()->where('status', 'scheduled')
                ->where('starts_at', '<', $end->utc())
                ->where('ends_at', '>', $start->utc())
                ->when($slot, fn ($query) => $query->whereKeyNot($slot->id))
                ->exists();
            if ($data['status'] === 'scheduled' && $conflict) {
                throw ValidationException::withMessages(['start_time' => 'This course already has a scheduled session during that time.']);
            }

            $attributes = collect($data)->only(['title', 'timezone', 'venue', 'meeting_link', 'notes', 'status'])->all()
                + ['starts_at' => $start->utc(), 'ends_at' => $end->utc()];
            if ($slot) {
                $slot->update($attributes);

                return $slot;
            }

            return $course->timeSlots()->create($attributes + ['created_by' => $user->id]);
        }, 5);
    }

    public function present(CourseTimeSlot $slot): array
    {
        $start = $slot->starts_at->setTimezone($slot->timezone);
        $end = $slot->ends_at->setTimezone($slot->timezone);

        return [
            'id' => $slot->id,
            'title' => $slot->title,
            'starts_at' => $slot->starts_at->utc()->toIso8601String(),
            'ends_at' => $slot->ends_at->utc()->toIso8601String(),
            'timezone' => $slot->timezone,
            'date_label' => $start->format('D, d M Y'),
            'time_label' => $start->format('H:i').' – '.$end->format('H:i'),
            'venue' => $slot->venue,
            'meeting_link' => $slot->meeting_link,
            'notes' => $slot->notes,
            'status' => $slot->status,
            'is_past' => $slot->ends_at->isPast(),
        ];
    }

    public function forCourse(Course $course): array
    {
        return $course->timeSlots()->orderBy('starts_at')->orderBy('id')->get()
            ->map(fn ($slot) => $this->present($slot))->all();
    }
}
