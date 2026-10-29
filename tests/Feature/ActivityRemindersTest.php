<?php

namespace Tests\Feature;

use App\Models\CareerActionItem;
use App\Models\Course;
use App\Models\InstructorAppointment;
use App\Models\MentorMatch;
use App\Models\MentorshipSession;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\ActivityReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ActivityRemindersTest extends TestCase
{
    use RefreshDatabase;

    private const REMINDER_TYPES = ['appointment_reminder', 'mentorship_session_reminder', 'reminder_career_step'];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['app.timezone' => 'Africa/Kampala']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function at(string $time): void
    {
        Carbon::setTestNow(Carbon::parse($time, 'Africa/Kampala'));
    }

    private function sendReminders(): int
    {
        return app(ActivityReminderService::class)->sendDue();
    }

    private function user(string $type = 'participant', string $name = 'Person'): User
    {
        return User::factory()->create(['name' => $name, 'user_type' => $type, 'status' => 'active', 'email_verified_at' => now()]);
    }

    private function titlesFor(User $user): array
    {
        return UserNotification::where('user_id', $user->id)->whereIn('type', self::REMINDER_TYPES)->orderBy('id')->pluck('title')->all();
    }

    public function test_approved_appointments_are_reminded_the_day_before_and_shortly_before_once_each(): void
    {
        $this->at('2026-10-29 10:00');
        $participant = $this->user('participant', 'Amina');
        $instructor = $this->user('staff', 'Mr Okello');
        $course = Course::create(['title' => 'Web Basics', 'status' => 'published']);
        $base = ['participant_user_id' => $participant->id, 'instructor_user_id' => $instructor->id, 'course_id' => $course->id, 'duration_minutes' => 30, 'mode' => 'online', 'topic' => 'Module 2 help'];
        InstructorAppointment::create($base + ['starts_at' => '2026-10-30 09:00', 'ends_at' => '2026-10-30 09:30', 'status' => 'approved']);
        InstructorAppointment::create($base + ['topic' => 'Pending one', 'starts_at' => '2026-10-30 09:00', 'ends_at' => '2026-10-30 09:30', 'status' => 'pending']);

        $this->assertSame(2, $this->sendReminders());
        $this->assertSame(['Appointment tomorrow'], $this->titlesFor($participant));
        $this->assertStringContainsString('"Module 2 help" with Mr Okello is tomorrow at 09:00', UserNotification::where('user_id', $participant->id)->whereIn('type', self::REMINDER_TYPES)->sole()->message);
        $this->assertSame(['Appointment tomorrow'], $this->titlesFor($instructor));

        $this->assertSame(0, $this->sendReminders(), 'Re-running in the same window sends nothing new.');

        $this->at('2026-10-30 08:40');
        $this->assertSame(2, $this->sendReminders());
        $this->assertSame(['Appointment tomorrow', 'Appointment starts soon'], $this->titlesFor($participant));
        $this->assertSame(0, $this->sendReminders());
    }

    public function test_mentorship_sessions_remind_mentor_and_mentee(): void
    {
        $this->at('2026-10-29 07:00');
        $mentor = $this->user('participant', 'Mentor Mary');
        $mentee = $this->user('participant', 'Grace');
        $match = MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);
        MentorshipSession::create(['mentor_match_id' => $match->id, 'title' => 'CV review', 'scheduled_at' => '2026-10-29 15:00', 'status' => 'scheduled', 'venue' => 'Hub room 2']);
        MentorshipSession::create(['mentor_match_id' => $match->id, 'title' => 'Cancelled one', 'scheduled_at' => '2026-10-29 16:00', 'status' => 'cancelled']);

        $this->assertSame(2, $this->sendReminders());
        $menteeNote = UserNotification::where('user_id', $mentee->id)->whereIn('type', self::REMINDER_TYPES)->sole();
        $this->assertSame('Mentorship session later today', $menteeNote->title);
        $this->assertStringContainsString('"CV review" with Mentor Mary is later today at 15:00. Venue: Hub room 2.', $menteeNote->message);
        $this->assertStringContainsString('with Grace', UserNotification::where('user_id', $mentor->id)->whereIn('type', self::REMINDER_TYPES)->sole()->message);
    }

    public function test_an_activity_booked_less_than_two_hours_ahead_only_gets_the_soon_reminder(): void
    {
        $this->at('2026-10-29 10:00');
        $mentor = $this->user();
        $mentee = $this->user();
        $match = MentorMatch::create(['mentor_user_id' => $mentor->id, 'mentee_user_id' => $mentee->id, 'status' => 'active']);
        MentorshipSession::create(['mentor_match_id' => $match->id, 'title' => 'Quick chat', 'scheduled_at' => '2026-10-29 11:00', 'status' => 'scheduled']);

        $this->assertSame(0, $this->sendReminders());
        $this->at('2026-10-29 10:35');
        $this->assertSame(2, $this->sendReminders());
        $this->assertSame(['Mentorship session starts soon'], $this->titlesFor($mentee));
    }

    public function test_career_steps_are_reminded_on_the_due_morning_and_once_when_overdue(): void
    {
        $user = $this->user();
        $step = CareerActionItem::create(['user_id' => $user->id, 'title' => 'Apply to two internships', 'due_on' => '2026-10-30', 'status' => 'approved']);
        CareerActionItem::create(['user_id' => $user->id, 'title' => 'Done already', 'due_on' => '2026-10-30', 'status' => 'completed']);
        CareerActionItem::create(['user_id' => $user->id, 'title' => 'Very old step', 'due_on' => '2026-09-01', 'status' => 'approved']);

        $this->at('2026-10-30 07:30');
        $this->assertSame(0, $this->sendReminders(), 'Nothing before 08:00.');

        $this->at('2026-10-30 08:05');
        $this->assertSame(1, $this->sendReminders());
        $this->assertSame(['Career step due today'], $this->titlesFor($user));
        $this->assertSame(0, $this->sendReminders());

        $this->at('2026-10-31 09:10');
        $this->assertSame(1, $this->sendReminders());
        $this->assertSame(['Career step due today', 'A career step is waiting'], $this->titlesFor($user));

        $this->at('2026-11-01 09:10');
        $this->assertSame(0, $this->sendReminders(), 'The overdue nudge is sent only once.');

        $step->update(['status' => 'completed']);
        $this->assertSame(0, $this->sendReminders());
    }

    public function test_reminders_respect_notification_preferences_and_the_command_is_scheduled(): void
    {
        $this->at('2026-10-30 08:05');
        $user = $this->user();
        $user->forceFill(['notification_preferences' => ['disabled' => ['reminders']]])->save();
        CareerActionItem::create(['user_id' => $user->id, 'title' => 'Step', 'due_on' => '2026-10-30', 'status' => 'approved']);

        $this->sendReminders();
        $this->assertSame(0, UserNotification::where('user_id', $user->id)->count());

        $this->artisan('activities:send-reminders')->assertSuccessful();
        $events = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->map->command->implode(' ');
        $this->assertStringContainsString('activities:send-reminders', $events);
    }
}
