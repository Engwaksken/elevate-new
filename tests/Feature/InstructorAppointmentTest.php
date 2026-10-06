<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\InstructorAppointment;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\CalendarFeedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class InstructorAppointmentTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private User $instructor;
    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-06 09:00:00', config('app.timezone')));

        $this->course = Course::create(['title' => 'Web Basics', 'status' => 'published']);
        $this->instructor = $this->makeInstructor();
        $this->instructor->instructedCourses()->attach($this->course);
        $this->participant = $this->makeParticipant();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeInstructor(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']);
        $user->roles()->attach($role);

        return $user;
    }

    private function makeParticipant(bool $enrolled = true): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        if ($enrolled) {
            Enrolment::create(['user_id' => $user->id, 'course_id' => $this->course->id, 'status' => 'enrolled']);
        }

        return $user;
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'instructor_user_id' => $this->instructor->id,
            'course_id' => $this->course->id,
            'date' => '2026-10-08',
            'start_time' => '10:00',
            'duration_minutes' => 30,
            'mode' => 'online',
            'topic' => 'Help with assignment 2',
            'details' => 'Stuck on CSS grid.',
        ], $changes);
    }

    private function book(array $changes = [], ?User $as = null): InstructorAppointment
    {
        $this->actingAs($as ?? $this->participant)
            ->post(route('appointments.store'), $this->payload($changes))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('appointments.index'));

        return InstructorAppointment::latest('id')->firstOrFail();
    }

    public function test_participant_can_book_and_instructor_is_notified(): void
    {
        $this->actingAs($this->participant)->get(route('appointments.index'))
            ->assertOk()->assertSee('Book an appointment')->assertSee($this->instructor->name);

        $appointment = $this->book();

        $this->assertSame('pending', $appointment->status);
        $this->assertSame('2026-10-08 10:30', $appointment->ends_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_requested']);

        $this->actingAs($this->instructor)->get(route('instructor.appointments.index'))
            ->assertOk()->assertSee('Help with assignment 2')->assertSee('Approve');
    }

    public function test_booking_rejects_instructor_of_a_course_the_participant_is_not_in(): void
    {
        $other = $this->makeInstructor();
        $otherCourse = Course::create(['title' => 'Other', 'status' => 'published']);
        $other->instructedCourses()->attach($otherCourse);

        $this->actingAs($this->participant)
            ->post(route('appointments.store'), $this->payload(['instructor_user_id' => $other->id, 'course_id' => $otherCourse->id]))
            ->assertSessionHasErrors('instructor_user_id');

        // Right instructor, wrong course.
        $this->post(route('appointments.store'), $this->payload(['course_id' => $otherCourse->id]))
            ->assertSessionHasErrors('course_id');

        // Not enrolled at all.
        $this->actingAs($this->makeParticipant(false))
            ->post(route('appointments.store'), $this->payload())
            ->assertSessionHasErrors('instructor_user_id');

        $this->assertDatabaseCount('instructor_appointments', 0);
    }

    public function test_booking_rejects_past_time_overlap_and_too_many_open_requests(): void
    {
        $this->actingAs($this->participant)
            ->post(route('appointments.store'), $this->payload(['date' => '2026-10-06', 'start_time' => '08:00']))
            ->assertSessionHasErrors('date');

        $this->book();
        $this->post(route('appointments.store'), $this->payload(['start_time' => '10:15']))
            ->assertSessionHasErrors('date');

        $this->book(['start_time' => '11:00']);
        $this->book(['start_time' => '12:00']);
        $this->post(route('appointments.store'), $this->payload(['start_time' => '14:00']))
            ->assertSessionHasErrors('date');

        $this->assertDatabaseCount('instructor_appointments', 3);
    }

    public function test_instructor_can_approve_and_appointment_appears_on_both_calendars(): void
    {
        $appointment = $this->book();

        $this->actingAs($this->instructor)
            ->post(route('instructor.appointments.approve', $appointment), ['meeting_url' => 'https://meet.example.test/abc'])
            ->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('approved', $appointment->status);
        $this->assertSame('https://meet.example.test/abc', $appointment->meeting_url);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->participant->id, 'type' => 'appointment_approved']);

        $feed = app(CalendarFeedService::class);
        $from = Carbon::parse('2026-10-01');
        $to = Carbon::parse('2026-11-01');
        $this->assertTrue($feed->entries($this->participant, $from, $to)->contains('id', 'appointment-'.$appointment->id));
        $this->assertTrue($feed->entries($this->instructor, $from, $to)->contains('id', 'appointment-'.$appointment->id));

        // Another participant/staff member does not see it.
        $this->assertFalse($feed->entries($this->makeParticipant(), $from, $to)->contains('id', 'appointment-'.$appointment->id));
        $this->assertFalse($feed->entries($this->makeInstructor(), $from, $to)->contains('id', 'appointment-'.$appointment->id));

        $this->actingAs($this->participant)->post(route('appointments.cancel', $appointment))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $appointment->fresh()->status);
        $this->assertFalse($feed->entries($this->participant, $from, $to)->contains('id', 'appointment-'.$appointment->id));
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_cancelled']);
    }

    public function test_instructor_cannot_approve_a_clashing_appointment(): void
    {
        $first = $this->book();
        $second = $this->book(['start_time' => '10:15'], $this->makeParticipant());
        // second was accepted because instructor has no approved slot yet
        $this->actingAs($this->instructor)->post(route('instructor.appointments.approve', $first))->assertSessionHasNoErrors();
        $this->post(route('instructor.appointments.approve', $second))->assertSessionHasErrors('appointment');
        $this->assertSame('pending', $second->fresh()->status);

        // New bookings for a slot the instructor already has approved are refused.
        $this->actingAs($this->makeParticipant())
            ->post(route('appointments.store'), $this->payload(['start_time' => '10:10']))
            ->assertSessionHasErrors('date');
    }

    public function test_decline_requires_reason_and_notifies(): void
    {
        $appointment = $this->book();

        $this->actingAs($this->instructor)->post(route('instructor.appointments.decline', $appointment))
            ->assertSessionHasErrors('reason');
        $this->post(route('instructor.appointments.decline', $appointment), ['reason' => 'On leave that week'])
            ->assertSessionHasNoErrors();

        $this->assertSame('declined', $appointment->fresh()->status);
        $this->assertSame('On leave that week', $appointment->fresh()->decision_reason);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->participant->id, 'type' => 'appointment_declined']);
    }

    public function test_propose_then_participant_accepts_or_declines(): void
    {
        $appointment = $this->book();

        $this->actingAs($this->instructor)->post(route('instructor.appointments.propose', $appointment), [
            'proposed_date' => '2026-10-09', 'proposed_time' => '14:00', 'note' => 'Afternoon works better',
        ])->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('rescheduled_proposed', $appointment->status);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->participant->id, 'type' => 'appointment_proposed']);

        $this->actingAs($this->participant)->get(route('appointments.index'))->assertOk()->assertSee('Accept new time');
        $this->post(route('appointments.accept', $appointment))->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('approved', $appointment->status);
        $this->assertSame('2026-10-09 14:00', $appointment->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2026-10-09 14:30', $appointment->ends_at->format('Y-m-d H:i'));
        $this->assertNull($appointment->proposed_starts_at);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_proposal_accepted']);

        $second = $this->book(['date' => '2026-10-12']);
        $this->actingAs($this->instructor)->post(route('instructor.appointments.propose', $second), [
            'proposed_date' => '2026-10-13', 'proposed_time' => '09:00',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->participant)->post(route('appointments.decline-proposal', $second))->assertSessionHasNoErrors();
        $this->assertSame('declined', $second->fresh()->status);
    }

    public function test_propose_cannot_target_past_or_clashing_time(): void
    {
        $approved = $this->book();
        $this->actingAs($this->instructor)->post(route('instructor.appointments.approve', $approved));
        $other = $this->book(['date' => '2026-10-10'], $this->makeParticipant());

        $this->actingAs($this->instructor)->post(route('instructor.appointments.propose', $other), ['proposed_date' => '2026-10-05', 'proposed_time' => '09:00'])
            ->assertSessionHasErrors('proposed_date');
        $this->post(route('instructor.appointments.propose', $other), ['proposed_date' => '2026-10-08', 'proposed_time' => '10:00'])
            ->assertSessionHasErrors('proposed_date');
        $this->assertSame('pending', $other->fresh()->status);
    }

    public function test_participant_cannot_cancel_approved_appointment_within_cutoff(): void
    {
        $appointment = $this->book(['date' => '2026-10-06', 'start_time' => '10:30']);
        $this->actingAs($this->instructor)->post(route('instructor.appointments.approve', $appointment));

        $this->actingAs($this->participant)->post(route('appointments.cancel', $appointment))
            ->assertSessionHasErrors('appointment');
        $this->assertSame('approved', $appointment->fresh()->status);
    }

    public function test_instructor_marks_completed_only_after_start(): void
    {
        $appointment = $this->book();
        $this->actingAs($this->instructor)->post(route('instructor.appointments.approve', $appointment));
        $this->post(route('instructor.appointments.complete', $appointment))->assertSessionHasErrors('appointment');

        Carbon::setTestNow(Carbon::parse('2026-10-08 11:00', config('app.timezone')));
        $this->post(route('instructor.appointments.complete', $appointment))->assertSessionHasNoErrors();
        $this->assertSame('completed', $appointment->fresh()->status);
        $this->get(route('instructor.appointments.index', ['tab' => 'past']))->assertOk()->assertSee('Help with assignment 2');
    }

    public function test_other_users_are_forbidden(): void
    {
        $appointment = $this->book();
        $otherParticipant = $this->makeParticipant();
        $otherInstructor = $this->makeInstructor();

        $this->actingAs($otherParticipant)->post(route('appointments.cancel', $appointment))->assertForbidden();
        $this->post(route('appointments.accept', $appointment))->assertForbidden();
        $this->post(route('instructor.appointments.approve', $appointment))->assertForbidden();

        $this->actingAs($otherInstructor)->post(route('instructor.appointments.approve', $appointment))->assertForbidden();
        $this->post(route('instructor.appointments.decline', $appointment), ['reason' => 'x'])->assertForbidden();
        $this->post(route('instructor.appointments.propose', $appointment), ['proposed_date' => '2026-10-09', 'proposed_time' => '09:00'])->assertForbidden();
        $this->get(route('instructor.appointments.index'))->assertOk()->assertDontSee('Help with assignment 2');

        // Staff cannot use the participant booking page.
        $this->get(route('appointments.index'))->assertForbidden();

        $this->assertSame('pending', $appointment->fresh()->status);
    }

    public function test_super_admin_can_view_all_but_not_act(): void
    {
        $appointment = $this->book();
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::create(['name' => 'Super Administrator', 'slug' => 'super-administrator']));

        $this->actingAs($admin)->get(route('instructor.appointments.index'))->assertOk()->assertDontSee('Help with assignment 2');
        $this->get(route('instructor.appointments.index', ['scope' => 'all']))->assertOk()->assertSee('Help with assignment 2');
        $this->post(route('instructor.appointments.approve', $appointment))->assertForbidden();
    }

    public function test_exports_are_scoped_to_own_appointments(): void
    {
        $this->book();
        $other = $this->makeParticipant();
        $this->book(['date' => '2026-10-09', 'topic' => 'Someone else topic'], $other);

        $csv = $this->actingAs($this->participant)->get(route('appointments.index', ['export' => 'csv']));
        $csv->assertOk();
        $body = $csv->streamedContent();
        $this->assertStringContainsString('Help with assignment 2', $body);
        $this->assertStringNotContainsString('Someone else topic', $body);

        $this->actingAs($this->makeInstructor());
        $body = $this->get(route('instructor.appointments.index', ['export' => 'csv']))->streamedContent();
        $this->assertStringNotContainsString('Help with assignment 2', $body);

        $this->actingAs($this->instructor);
        $body = $this->get(route('instructor.appointments.index', ['export' => 'csv', 'tab' => 'requests']))->streamedContent();
        $this->assertStringContainsString('Someone else topic', $body);

        $this->get(route('instructor.appointments.index', ['export' => 'pdf']))->assertOk();
    }

    public function test_sidebars_link_to_appointments_with_badge(): void
    {
        $this->book();

        $this->actingAs($this->participant)->get(route('appointments.index'))
            ->assertSee(route('appointments.index'), false)->assertSee('Appointments');

        $this->actingAs($this->instructor)->get(route('instructor.appointments.index'))
            ->assertSee(route('instructor.appointments.index'), false)
            ->assertSee('1 pending', false);
    }

    public function test_notification_preferences_suppress_appointment_notices(): void
    {
        $this->instructor->forceFill(['notification_preferences' => ['disabled' => ['appointments']]])->save();
        $this->book();

        $this->assertSame(0, UserNotification::where('user_id', $this->instructor->id)->count());
    }

    public function test_approved_appointment_becomes_an_instructor_task_on_its_date(): void
    {
        $appointment = $this->book();
        $this->assertDatabaseMissing('tasks', ['instructor_appointment_id' => $appointment->id]);

        $this->actingAs($this->instructor)
            ->post(route('instructor.appointments.approve', $appointment), ['meeting_url' => 'https://meet.example.test/abc'])
            ->assertSessionHasNoErrors();

        $task = \App\Models\Task::where('instructor_appointment_id', $appointment->id)->sole();
        $this->assertSame($this->instructor->id, (int) $task->assigned_to);
        $this->assertSame('2026-10-08', $task->due_date->toDateString());
        $this->assertSame('not_started', $task->status);
        $this->assertStringContainsString('Help with assignment 2', $task->title);
        $this->assertStringContainsString('https://meet.example.test/abc', (string) $task->description);

        // It shows in the instructor's My Tasks for that week.
        $this->actingAs($this->instructor)
            ->get(route('staff.tasks.index', ['view' => 'week', 'week' => '2026-10-05']))
            ->assertOk()->assertSee('Help with assignment 2');
    }

    public function test_accepted_proposal_creates_task_on_the_new_date_and_cancel_removes_it(): void
    {
        $appointment = $this->book();

        $this->actingAs($this->instructor)
            ->post(route('instructor.appointments.propose', $appointment), ['proposed_date' => '2026-10-09', 'proposed_time' => '14:00', 'note' => 'Friday works better'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tasks', ['instructor_appointment_id' => $appointment->id]);

        $this->actingAs($this->participant)->post(route('appointments.accept', $appointment))->assertSessionHasNoErrors();
        $task = \App\Models\Task::where('instructor_appointment_id', $appointment->id)->sole();
        $this->assertSame('2026-10-09', $task->due_date->toDateString());

        $this->actingAs($this->participant)->post(route('appointments.cancel', $appointment))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('tasks', ['instructor_appointment_id' => $appointment->id]);
    }

    public function test_completing_the_appointment_completes_its_task(): void
    {
        $appointment = $this->book();
        $this->actingAs($this->instructor)->post(route('instructor.appointments.approve', $appointment))->assertSessionHasNoErrors();

        Carbon::setTestNow(Carbon::parse('2026-10-08 11:00:00', config('app.timezone')));
        $this->actingAs($this->instructor)->post(route('instructor.appointments.complete', $appointment))->assertSessionHasNoErrors();

        $task = \App\Models\Task::where('instructor_appointment_id', $appointment->id)->sole();
        $this->assertSame('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }
}
