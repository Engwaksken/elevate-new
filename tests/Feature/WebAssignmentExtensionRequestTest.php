<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssignmentExtensionRequest;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Web counterpart of the mobile app's assignment deadline extension requests.
 */
class WebAssignmentExtensionRequestTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private User $participant;
    private User $instructor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->course = Course::create(['title' => 'Media Skills', 'status' => 'published']);
        $this->participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $this->participant->id, 'status' => 'enrolled']);

        $this->instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->instructor->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));
        $this->course->instructors()->attach($this->instructor->id);
    }

    private function assignment(array $attributes = []): Assessment
    {
        return Assessment::create($attributes + [
            'course_id' => $this->course->id, 'title' => 'Pitch', 'type' => 'assignment',
            'max_attempts' => 1, 'pass_mark' => 50, 'is_published' => true, 'due_at' => now()->addWeek(),
        ]);
    }

    public function test_overdue_assignment_hides_the_submit_form_and_offers_an_extension(): void
    {
        $assessment = $this->assignment(['due_at' => now()->subDay()]);

        $this->actingAs($this->participant)
            ->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Request a deadline extension')
            ->assertSee(route('learning.assessment.extension-request', $assessment), false)
            ->assertDontSee(route('learning.assessment.submit', $assessment).'"', false);
    }

    public function test_assignment_far_from_its_due_date_does_not_offer_an_extension(): void
    {
        $assessment = $this->assignment(['due_at' => now()->addDays(5)]);

        $this->actingAs($this->participant)
            ->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertDontSee('Request a deadline extension')
            ->assertSee(route('learning.assessment.submit', $assessment), false);
    }

    public function test_web_submission_is_blocked_after_the_due_date(): void
    {
        $assessment = $this->assignment(['due_at' => now()->subMinute()]);

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.submit', $assessment), [
                'submission_files' => [UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf')],
            ])
            ->assertRedirect(route('learning.assessment.show', $assessment))
            ->assertSessionHasErrors('extension');

        $this->assertDatabaseCount('assessment_attempts', 0);
    }

    public function test_participant_can_request_an_extension_from_the_web(): void
    {
        $assessment = $this->assignment(['due_at' => now()->subDay()]);
        $requested = now()->addDays(2)->startOfMinute();

        $this->actingAs($this->participant)
            ->post(route('learning.assessment.extension-request', $assessment), [
                'reason' => 'I was in hospital last week',
                'requested_due_at' => $requested->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('learning.assessment.show', $assessment))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assignment_extension_requests', [
            'assessment_id' => $assessment->id, 'user_id' => $this->participant->id,
            'status' => 'pending', 'reason' => 'I was in hospital last week',
        ]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $this->instructor->id, 'type' => 'assignment_extension_requested']);

        // Pending: the page shows the status and no second request form.
        $this->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee('Your latest extension request')
            ->assertSee('Pending')
            ->assertDontSee('Request a deadline extension');

        $this->post(route('learning.assessment.extension-request', $assessment), ['reason' => 'Asking once more please'])
            ->assertSessionHasErrors('extension');
        $this->assertDatabaseCount('assignment_extension_requests', 1);
    }

    public function test_extension_request_validation_and_rules(): void
    {
        $overdue = $this->assignment(['due_at' => now()->subDay()]);
        $this->actingAs($this->participant);

        $this->post(route('learning.assessment.extension-request', $overdue), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');
        $this->post(route('learning.assessment.extension-request', $overdue), [
            'reason' => 'I was in hospital last week', 'requested_due_at' => now()->subDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('requested_due_at');

        $farAway = $this->assignment(['due_at' => now()->addDays(5)]);
        $this->post(route('learning.assessment.extension-request', $farAway), ['reason' => 'I need more time please'])
            ->assertSessionHasErrors('extension');

        $draft = $this->assignment(['due_at' => now()->subDay(), 'is_published' => false]);
        $this->post(route('learning.assessment.extension-request', $draft), ['reason' => 'I need more time please'])
            ->assertNotFound();

        $other = Course::create(['title' => 'Other', 'status' => 'published']);
        $foreign = Assessment::create(['course_id' => $other->id, 'title' => 'X', 'type' => 'assignment', 'max_attempts' => 1, 'due_at' => now()->subDay(), 'is_published' => true]);
        $this->post(route('learning.assessment.extension-request', $foreign), ['reason' => 'I need more time please'])
            ->assertForbidden();

        $this->assertDatabaseCount('assignment_extension_requests', 0);
    }

    public function test_approved_extension_reopens_web_submission_with_the_new_due_date(): void
    {
        $assessment = $this->assignment(['due_at' => now()->subDay()]);
        $request = AssignmentExtensionRequest::create([
            'assessment_id' => $assessment->id, 'user_id' => $this->participant->id, 'reason' => 'My laptop was stolen',
        ]);
        $newDue = now()->addDays(3)->startOfMinute();

        $this->actingAs($this->instructor)
            ->post(route('instructor.courses.extension-requests.approve', [$this->course, $request]), [
                'approved_due_at' => $newDue->format('Y-m-d\TH:i'),
                'reviewer_note' => 'Get well soon',
            ])->assertRedirect();

        // The participant's notice links to the web assignment page.
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $this->participant->id, 'type' => 'assignment_extension_approved',
            'action_url' => route('learning.assessment.show', $assessment),
        ]);

        $this->actingAs($this->participant)
            ->get(route('learning.course.dashboard', $this->course))
            ->assertOk()
            ->assertSee($newDue->format('d M Y H:i'))
            ->assertSee('Extended');

        $this->get(route('learning.assessment.show', $assessment))
            ->assertOk()
            ->assertSee($newDue->format('d M Y H:i'))
            ->assertSee('Get well soon')
            ->assertSee(route('learning.assessment.submit', $assessment), false);

        $this->post(route('learning.assessment.submit', $assessment), [
            'submission_files' => [UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf')],
        ])->assertRedirect(route('learning.course.dashboard', $this->course));

        $this->assertSame(1, AssessmentAttempt::where('assessment_id', $assessment->id)->count());
    }

    public function test_course_dashboard_flags_overdue_assignments(): void
    {
        $this->assignment(['title' => 'Late brief', 'due_at' => now()->subDay()]);

        $this->actingAs($this->participant)
            ->get(route('learning.course.dashboard', $this->course))
            ->assertOk()
            ->assertSee('Overdue')
            ->assertSee('Request extension');
    }
}
