<?php

namespace Tests\Feature\Participant;

use App\Models\Course;
use App\Models\Enrolment;
use App\Models\InstructorAppointment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ParticipantAppointmentsApiTest extends TestCase
{
    use RefreshDatabase;

    private const BASE = '/api/v1/participant/appointments';

    private Course $course;
    private User $instructor;
    private User $participant;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.timezone' => 'Africa/Kampala']);
        date_default_timezone_set('Africa/Kampala');
        Carbon::setTestNow(Carbon::parse('2026-10-06 09:00:00', 'Africa/Kampala'));

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
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));

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

    private function as(User $user): static
    {
        Sanctum::actingAs($user, ['participant']);

        return $this;
    }

    private function payload(array $changes = []): array
    {
        return array_replace([
            'instructor_user_id' => $this->instructor->id,
            'course_id' => $this->course->id,
            'starts_at' => '2026-10-08T10:00:00+03:00',
            'duration_minutes' => 30,
            'mode' => 'online',
            'topic' => 'Help with assignment 2',
            'details' => 'Stuck on CSS grid.',
        ], $changes);
    }

    private function book(array $changes = [], ?User $as = null): InstructorAppointment
    {
        $this->as($as ?? $this->participant)
            ->postJson(self::BASE, $this->payload($changes))
            ->assertCreated();

        return InstructorAppointment::latest('id')->firstOrFail();
    }

    private function approve(InstructorAppointment $appointment, array $data = []): void
    {
        $appointment->update(['status' => 'approved', 'decided_at' => now()] + $data);
    }

    private function propose(InstructorAppointment $appointment, string $start): void
    {
        $appointment->update([
            'status' => 'rescheduled_proposed',
            'proposed_starts_at' => Carbon::parse($start, 'Africa/Kampala'),
            'proposal_note' => 'Afternoon works better',
        ]);
    }

    public function test_options_list_only_instructors_of_enrolled_courses(): void
    {
        $other = $this->makeInstructor();
        $other->instructedCourses()->attach(Course::create(['title' => 'Other course', 'status' => 'published']));

        $this->as($this->participant)->getJson(self::BASE.'/options')
            ->assertOk()
            ->assertJsonCount(1, 'instructors')
            ->assertJsonPath('instructors.0.id', $this->instructor->id)
            ->assertJsonPath('instructors.0.courses.0.id', $this->course->id)
            ->assertJsonPath('instructors.0.courses.0.title', 'Web Basics')
            ->assertJsonPath('durations', [15, 30, 45, 60])
            ->assertJsonPath('modes.0.value', 'online')
            ->assertJsonPath('timezone', 'Africa/Kampala')
            ->assertJsonPath('rules.max_open_requests', 3);

        $this->as($this->makeParticipant(false))->getJson(self::BASE.'/options')
            ->assertOk()->assertJsonCount(0, 'instructors');
    }

    public function test_participant_can_book_with_iso_start_and_offset_is_honoured(): void
    {
        $response = $this->as($this->participant)->postJson(self::BASE, $this->payload([
            // 07:00 UTC == 10:00 in Kampala (+03:00)
            'starts_at' => '2026-10-08T07:00:00Z',
        ]));

        $response->assertCreated()
            ->assertJsonPath('appointment.status', 'pending')
            ->assertJsonPath('appointment.status_label', 'Pending')
            ->assertJsonPath('appointment.starts_at', '2026-10-08T10:00:00+03:00')
            ->assertJsonPath('appointment.ends_at', '2026-10-08T10:30:00+03:00')
            ->assertJsonPath('appointment.timezone', 'Africa/Kampala')
            ->assertJsonPath('appointment.instructor.id', $this->instructor->id)
            ->assertJsonPath('appointment.course.title', 'Web Basics')
            ->assertJsonPath('appointment.meeting_url', null)
            ->assertJsonPath('appointment.can_cancel', true)
            ->assertJsonPath('appointment.can_respond_to_proposal', false);

        $appointment = InstructorAppointment::firstOrFail();
        $this->assertSame('2026-10-08 10:00', $appointment->starts_at->format('Y-m-d H:i'));
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_requested']);
    }

    public function test_booking_also_accepts_web_style_date_and_time(): void
    {
        $data = $this->payload(['date' => '2026-10-09', 'start_time' => '14:15']);
        unset($data['starts_at']);

        $this->as($this->participant)->postJson(self::BASE, $data)
            ->assertCreated()
            ->assertJsonPath('appointment.starts_at', '2026-10-09T14:15:00+03:00');
    }

    public function test_booking_validation_errors_use_json_shape(): void
    {
        $this->as($this->participant)->postJson(self::BASE, [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors'])
            ->assertJsonValidationErrors(['instructor_user_id', 'course_id', 'starts_at', 'duration_minutes', 'mode', 'topic']);

        $this->postJson(self::BASE, $this->payload(['duration_minutes' => 20, 'mode' => 'phone']))
            ->assertStatus(422)->assertJsonValidationErrors(['duration_minutes', 'mode']);

        // Past time.
        $this->postJson(self::BASE, $this->payload(['starts_at' => '2026-10-06T08:00:00+03:00']))
            ->assertStatus(422)->assertJsonValidationErrors('starts_at');

        $this->assertDatabaseCount('instructor_appointments', 0);
    }

    public function test_booking_rejects_non_enrolled_instructor_and_wrong_course(): void
    {
        $other = $this->makeInstructor();
        $otherCourse = Course::create(['title' => 'Other', 'status' => 'published']);
        $other->instructedCourses()->attach($otherCourse);

        $this->as($this->participant)
            ->postJson(self::BASE, $this->payload(['instructor_user_id' => $other->id, 'course_id' => $otherCourse->id]))
            ->assertStatus(422)->assertJsonValidationErrors('instructor_user_id');

        $this->postJson(self::BASE, $this->payload(['course_id' => $otherCourse->id]))
            ->assertStatus(422)->assertJsonValidationErrors('course_id');

        $this->assertDatabaseCount('instructor_appointments', 0);
    }

    public function test_booking_rejects_overlap_and_fourth_open_request(): void
    {
        $this->book();

        $this->as($this->participant)
            ->postJson(self::BASE, $this->payload(['starts_at' => '2026-10-08T10:15:00+03:00']))
            ->assertStatus(422)->assertJsonValidationErrors('starts_at');

        $this->book(['starts_at' => '2026-10-08T11:00:00+03:00']);
        $this->book(['starts_at' => '2026-10-08T12:00:00+03:00']);

        $this->postJson(self::BASE, $this->payload(['starts_at' => '2026-10-08T14:00:00+03:00']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('starts_at')
            ->assertJsonFragment(['You already have 3 requests awaiting a response. Wait for a reply or cancel one first.']);

        $this->assertDatabaseCount('instructor_appointments', 3);
    }

    public function test_list_is_scoped_to_own_and_split_upcoming_past(): void
    {
        $mine = $this->book();
        $old = $this->book(['starts_at' => '2026-10-07T09:00:00+03:00']);
        $old->update(['status' => 'declined', 'decision_reason' => 'Busy']);
        $this->book(['topic' => 'Not mine'], $this->makeParticipant());

        $this->as($this->participant)->getJson(self::BASE)
            ->assertOk()
            ->assertJsonCount(1, 'upcoming')
            ->assertJsonCount(1, 'past')
            ->assertJsonPath('upcoming.0.id', $mine->id)
            ->assertJsonPath('past.0.id', $old->id)
            ->assertJsonPath('past.0.decision_reason', 'Busy')
            ->assertJsonPath('summary.pending', 1)
            ->assertJsonMissing(['topic' => 'Not mine']);

        $this->getJson(self::BASE.'?scope=past')->assertOk()
            ->assertJsonCount(0, 'upcoming')->assertJsonCount(1, 'past');
        $this->getJson(self::BASE.'?scope=upcoming')->assertOk()
            ->assertJsonCount(1, 'upcoming')->assertJsonCount(0, 'past');
        $this->getJson(self::BASE.'?scope=bogus')->assertStatus(422);

        $this->getJson(self::BASE."/{$mine->id}")->assertOk()
            ->assertJsonPath('appointment.topic', 'Help with assignment 2');
    }

    public function test_meeting_url_only_exposed_once_approved(): void
    {
        $appointment = $this->book();
        $appointment->update(['meeting_url' => 'https://meet.example.test/abc']);

        $this->as($this->participant)->getJson(self::BASE."/{$appointment->id}")
            ->assertOk()->assertJsonPath('appointment.meeting_url', null);

        $this->approve($appointment);

        $this->getJson(self::BASE."/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('appointment.status', 'approved')
            ->assertJsonPath('appointment.meeting_url', 'https://meet.example.test/abc');
    }

    public function test_participant_can_accept_a_proposal(): void
    {
        $appointment = $this->book();
        $this->propose($appointment, '2026-10-09 14:00');

        $this->as($this->participant)->getJson(self::BASE."/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('appointment.status_label', 'New time proposed')
            ->assertJsonPath('appointment.proposed_starts_at', '2026-10-09T14:00:00+03:00')
            ->assertJsonPath('appointment.proposed_ends_at', '2026-10-09T14:30:00+03:00')
            ->assertJsonPath('appointment.proposal_note', 'Afternoon works better')
            ->assertJsonPath('appointment.can_respond_to_proposal', true)
            ->assertJsonPath('appointment.can_accept_proposal', true);

        $this->postJson(self::BASE."/{$appointment->id}/accept-proposal")
            ->assertOk()
            ->assertJsonPath('appointment.status', 'approved')
            ->assertJsonPath('appointment.starts_at', '2026-10-09T14:00:00+03:00')
            ->assertJsonPath('appointment.proposed_starts_at', null);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_proposal_accepted']);

        // Accepting again is a business-rule error, not a crash.
        $this->postJson(self::BASE."/{$appointment->id}/accept-proposal")
            ->assertStatus(422)->assertJsonValidationErrors('appointment');
    }

    public function test_participant_can_decline_a_proposal(): void
    {
        $appointment = $this->book();
        $this->propose($appointment, '2026-10-09 14:00');

        $this->as($this->participant)->postJson(self::BASE."/{$appointment->id}/decline-proposal")
            ->assertOk()
            ->assertJsonPath('appointment.status', 'declined')
            ->assertJsonPath('appointment.can_cancel', false);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'appointment_proposal_declined']);
    }

    public function test_cancel_rules_respect_two_hour_cutoff(): void
    {
        $pending = $this->book();
        $this->as($this->participant)->postJson(self::BASE."/{$pending->id}/cancel", ['reason' => 'Solved it myself'])
            ->assertOk()
            ->assertJsonPath('appointment.status', 'cancelled')
            ->assertJsonPath('appointment.decision_reason', 'Solved it myself')
            ->assertJsonPath('appointment.cancelled_by_me', true);

        // Already cancelled.
        $this->postJson(self::BASE."/{$pending->id}/cancel")
            ->assertStatus(422)->assertJsonValidationErrors('appointment');

        // Approved and starting in 90 minutes: inside the cutoff.
        $soon = $this->book(['starts_at' => '2026-10-06T10:30:00+03:00']);
        $this->approve($soon);
        $this->getJson(self::BASE."/{$soon->id}")->assertJsonPath('appointment.can_cancel', false);
        $this->postJson(self::BASE."/{$soon->id}/cancel")
            ->assertStatus(422)->assertJsonValidationErrors('appointment');
        $this->assertSame('approved', $soon->fresh()->status);

        // Approved and starting in 3 hours: allowed, no reason needed.
        $later = $this->book(['starts_at' => '2026-10-06T12:00:00+03:00']);
        $this->approve($later);
        $this->getJson(self::BASE."/{$later->id}")->assertJsonPath('appointment.can_cancel', true);
        $this->postJson(self::BASE."/{$later->id}/cancel")
            ->assertOk()->assertJsonPath('appointment.status', 'cancelled');
    }

    public function test_other_participants_cannot_see_or_act_on_an_appointment(): void
    {
        $appointment = $this->book();
        $this->propose($appointment, '2026-10-09 14:00');

        $this->as($this->makeParticipant());
        $this->getJson(self::BASE."/{$appointment->id}")->assertForbidden()
            ->assertJsonPath('message', 'This appointment does not belong to you.');
        $this->postJson(self::BASE."/{$appointment->id}/accept-proposal")->assertForbidden();
        $this->postJson(self::BASE."/{$appointment->id}/decline-proposal")->assertForbidden();
        $this->postJson(self::BASE."/{$appointment->id}/cancel")->assertForbidden();

        $this->getJson(self::BASE.'/999999')->assertNotFound();

        $this->assertSame('rescheduled_proposed', $appointment->fresh()->status);
    }

    public function test_staff_tokens_and_guests_cannot_use_participant_endpoints(): void
    {
        $appointment = $this->book();

        $this->getJson(self::BASE)->assertOk();
        $this->app['auth']->forgetGuards();

        $this->getJson(self::BASE)->assertUnauthorized();

        Sanctum::actingAs($this->instructor);
        $this->getJson(self::BASE)->assertForbidden();
        $this->getJson(self::BASE.'/options')->assertForbidden();
        $this->getJson(self::BASE."/{$appointment->id}")->assertForbidden();
        $this->postJson(self::BASE, $this->payload())->assertForbidden();
        $this->postJson(self::BASE."/{$appointment->id}/cancel")->assertForbidden();

        $this->assertSame('pending', $appointment->fresh()->status);
    }
}
