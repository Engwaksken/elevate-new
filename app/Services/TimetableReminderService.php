<?php

namespace App\Services;

use App\Models\CourseTimeSlot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TimetableReminderService
{
    public function sendDue(): int
    {
        $now = now()->utc(); $sent = 0;
        $ids = CourseTimeSlot::where('status', 'scheduled')->whereHas('course', fn ($q) => $q->where('status', 'published'))
            ->where('starts_at', '>', $now)->where('starts_at', '<=', $now->copy()->addMinutes(10))->pluck('id');
        foreach ($ids as $id) {
            $sent += DB::transaction(function () use ($id, $now) {
                $slot = CourseTimeSlot::whereKey($id)->lockForUpdate()->first();
                if (! $slot || $slot->status !== 'scheduled' || ! $slot->course || $slot->course->status !== 'published'
                    || $slot->starts_at->lessThanOrEqualTo($now) || $slot->starts_at->greaterThan($now->copy()->addMinutes(10))) return 0;
                $course = $slot->course;
                $participants = $course->enrolments()->whereIn('status', ['enrolled', 'active', 'in_progress'])->whereHas('user', fn ($q) => $q->where('user_type', 'participant')->where('status', 'active'))->pluck('user_id');
                $trainers = $course->instructors()->where('user_type', 'staff')->pluck('users.id');
                $users = User::where('status', 'active')->whereIn('id', $participants->merge($trainers)->unique())->get();
                $count = 0;
                foreach ($users as $user) {
                    $claimed = DB::table('timetable_reminder_deliveries')->insertOrIgnore(['course_time_slot_id' => $slot->id, 'user_id' => $user->id, 'starts_at' => $slot->starts_at->format('Y-m-d H:i:s'), 'sent_at' => $now]);
                    if (! $claimed) continue;
                    $time = $slot->starts_at->setTimezone($slot->timezone)->format('H:i');
                    $url = $user->isStaff() ? route('admin.elearning.timetable.index', ['course_id' => $course->id]) : route('learning.course.dashboard', $course);
                    app(UserNotificationService::class)->send($user, 'course_timetable_reminder', 'Upcoming course session',
                        $course->title.' — '.$slot->title.' starts at '.$time.' ('.$slot->timezone.') in about '.max(1, (int) ceil($now->diffInSeconds($slot->starts_at) / 60)).' minutes.'.($slot->venue ? ' Venue: '.$slot->venue : ''),
                        $url, ['course_id' => $course->id, 'slot_id' => $slot->id, 'starts_at' => $slot->starts_at->toIso8601String(), 'destination' => 'learning']);
                    $count++;
                }
                return $count;
            }, 5);
        }
        return $sent;
    }

    public function forParticipant(User $user): array
    {
        return CourseTimeSlot::where('status', 'scheduled')->where('starts_at', '>', now()->utc())
            ->whereHas('course', fn ($q) => $q->where('status', 'published')->whereHas('enrolments', fn ($e) => $e->where('user_id', $user->id)->whereIn('status', ['enrolled', 'active', 'in_progress'])))
            ->orderBy('starts_at')->get()->map(fn ($slot) => [
                'type' => 'timetable_lesson', 'source_id' => $slot->id, 'course_id' => $slot->course_id,
                'title' => $slot->title, 'message' => 'Your course session starts in 10 minutes.',
                'scheduled_at' => $slot->starts_at->toIso8601String(), 'destination' => 'learning',
            ])->all();
    }
}
