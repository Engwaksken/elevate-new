<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateRecommendation;
use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\EventCertificate;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = $this->staff('super-administrator');
        $this->course = Course::create(['title' => 'Digital Marketing', 'status' => 'published']);
    }

    private function staff(string $roleSlug): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucwords(str_replace('-', ' ', $roleSlug))]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function participant(?Course $course = null): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        if ($course) {
            Enrolment::create(['course_id' => $course->id, 'user_id' => $user->id, 'status' => 'enrolled', 'progress_percent' => 80]);
        }

        return $user;
    }

    private function instructorFor(Course $course): User
    {
        $instructor = $this->staff('instructor');
        $instructor->instructedCourses()->attach($course->id);

        return $instructor;
    }

    public function test_instructor_recommends_participants_of_their_course_and_admin_is_notified(): void
    {
        $instructor = $this->instructorFor($this->course);
        $learner = $this->participant($this->course);

        $this->actingAs($instructor)
            ->get(route('certificates.recommendations.create', ['course_id' => $this->course->id]))
            ->assertOk()
            ->assertSee($learner->name)
            ->assertSee('80% progress')
            ->assertDontSee('Issue certificates now');

        $this->actingAs($instructor)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id,
            'user_ids' => [$learner->id],
            'reason' => 'Completed the final project.',
        ])->assertSessionHas('success');

        $this->assertDatabaseHas('certificate_recommendations', [
            'course_id' => $this->course->id,
            'user_id' => $learner->id,
            'recommended_by' => $instructor->id,
            'status' => 'pending',
        ]);
        $this->assertTrue(UserNotification::where('user_id', $this->admin->id)->where('type', 'certificate_recommendation')->exists());

        // A second recommendation for the same participant is skipped while one is pending.
        $this->actingAs($instructor)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id,
            'user_ids' => [$learner->id],
        ])->assertSessionHas('error');
        $this->assertSame(1, CertificateRecommendation::count());

        $this->actingAs($instructor)->get(route('certificates.recommendations.index'))
            ->assertOk()
            ->assertSee($learner->name)
            ->assertSee('Awaiting review');
    }

    public function test_instructor_cannot_recommend_for_unassigned_course_or_issue_directly(): void
    {
        $instructor = $this->instructorFor($this->course);
        $other = Course::create(['title' => 'Data Science', 'status' => 'published']);
        $learner = $this->participant($other);

        $this->actingAs($instructor)
            ->get(route('certificates.recommendations.create', ['course_id' => $other->id]))
            ->assertForbidden();

        $this->actingAs($instructor)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id,
            'user_ids' => [$this->participant($this->course)->id],
            'issue_now' => 1,
        ])->assertForbidden();

        $this->actingAs($instructor)->post(route('certificates.recommendations.review'), [
            'ids' => [1], 'decision' => 'approve',
        ])->assertForbidden();

        $this->assertSame(0, CertificateRecommendation::count());
        $this->assertSame(0, Certificate::where('user_id', $learner->id)->count());
    }

    public function test_admin_approval_issues_course_certificate_and_notifies(): void
    {
        $instructor = $this->instructorFor($this->course);
        $learner = $this->participant($this->course);
        $recommendation = CertificateRecommendation::create([
            'context_type' => 'course', 'course_id' => $this->course->id, 'user_id' => $learner->id,
            'recommended_by' => $instructor->id, 'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->get(route('certificates.recommendations.index', ['status' => 'pending']))
            ->assertOk()
            ->assertSee('id="cert-rec-'.$recommendation->id.'"', false)
            ->assertSee('Approve &amp; issue', false);

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), [
            'ids' => [$recommendation->id],
            'decision' => 'approve',
        ])->assertSessionHas('success');

        $recommendation->refresh();
        $this->assertSame('approved', $recommendation->status);
        $this->assertSame($this->admin->id, $recommendation->reviewed_by);
        $this->assertNotNull($recommendation->certificate_id);
        $this->assertTrue(Certificate::where('course_id', $this->course->id)->where('user_id', $learner->id)->exists());
        $this->assertTrue(UserNotification::where('user_id', $learner->id)->where('type', 'certificate_issued')->exists());
        $this->assertTrue(UserNotification::where('user_id', $instructor->id)->where('title', 'Certificate recommendation approved')->exists());

        $this->actingAs($learner)->get(route('certificates.mine'))
            ->assertOk()
            ->assertSee('Digital Marketing')
            ->assertSee('Download PDF');
    }

    public function test_rejection_requires_a_reason_and_notifies_recommender(): void
    {
        $officer = $this->staff('program-officer');
        $learner = $this->participant($this->course);
        $recommendation = CertificateRecommendation::create([
            'context_type' => 'course', 'course_id' => $this->course->id, 'user_id' => $learner->id,
            'recommended_by' => $officer->id, 'status' => 'pending',
        ]);

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), [
            'ids' => [$recommendation->id], 'decision' => 'reject',
        ])->assertSessionHasErrors('review_notes');
        $this->assertTrue($recommendation->fresh()->isPending());

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), [
            'ids' => [$recommendation->id], 'decision' => 'reject', 'review_notes' => 'Final assessment not submitted.',
        ])->assertSessionHas('success');

        $this->assertSame('rejected', $recommendation->fresh()->status);
        $this->assertFalse(Certificate::where('user_id', $learner->id)->exists());
        $this->assertTrue(UserNotification::where('user_id', $officer->id)
            ->where('message', 'like', '%Final assessment not submitted.%')->exists());
    }

    public function test_programme_officer_recommends_event_participant_and_approval_enables_download(): void
    {
        $officer = $this->staff('program-officer');
        $learner = $this->participant();
        $event = Event::create([
            'title' => 'Women in Tech Summit', 'event_type' => 'training', 'delivery_mode' => 'physical',
            'starts_at' => now()->subDay(), 'certificate_enabled' => false,
        ]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $learner->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($officer)
            ->get(route('certificates.recommendations.create', ['event_id' => $event->id]))
            ->assertOk()
            ->assertSee($learner->name)
            ->assertSee('attendance not recorded');

        $this->actingAs($officer)->post(route('certificates.recommendations.store'), [
            'event_id' => $event->id, 'user_ids' => [$learner->id], 'reason' => 'Facilitated a session.',
        ])->assertSessionHas('success');

        $recommendation = CertificateRecommendation::firstOrFail();
        $this->assertSame('event', $recommendation->context_type);

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), [
            'ids' => [$recommendation->id], 'decision' => 'approve',
        ]);

        $issued = EventCertificate::where('event_id', $event->id)->where('user_id', $learner->id)->firstOrFail();
        $this->assertSame($this->admin->id, $issued->issued_by);
        $this->assertSame($issued->id, $recommendation->fresh()->event_certificate_id);

        // Certificates are disabled for self-service and there is no attendance, but the issued one downloads.
        $learner->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($learner)->get(route('events.certificate', $event))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($learner)->get(route('certificates.mine'))->assertSee('Women in Tech Summit');
    }

    public function test_admin_can_issue_directly_for_selected_participants(): void
    {
        $first = $this->participant($this->course);
        $second = $this->participant($this->course);
        $notSelected = $this->participant($this->course);

        $this->actingAs($this->admin)
            ->get(route('certificates.recommendations.create', ['course_id' => $this->course->id]))
            ->assertOk()
            ->assertSee('Issue certificates now');

        $this->actingAs($this->admin)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id,
            'user_ids' => [$first->id, $second->id],
            'issue_now' => 1,
        ])->assertSessionHas('success', '2 certificates issued.');

        $this->assertSame(2, Certificate::where('course_id', $this->course->id)->count());
        $this->assertFalse(Certificate::where('user_id', $notSelected->id)->exists());
        $this->assertSame(2, CertificateRecommendation::where('status', 'approved')->count());

        // Already-certified participants are skipped on a repeat.
        $this->actingAs($this->admin)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id, 'user_ids' => [$first->id], 'issue_now' => 1,
        ])->assertSessionHas('error');
        $this->assertSame(2, Certificate::count());
    }

    public function test_participants_cannot_reach_recommendations(): void
    {
        $this->actingAs($this->participant($this->course))
            ->get(route('certificates.recommendations.index'))
            ->assertForbidden();
    }

    public function test_admin_uploads_event_template_and_it_is_used_for_event_certificates(): void
    {
        $event = Event::create(['title' => 'Hackathon', 'event_type' => 'training', 'delivery_mode' => 'physical', 'starts_at' => now()]);

        $this->actingAs($this->admin)->post(route('admin.elearning.certificates.templates.store'), [
            'name' => 'Hackathon design',
            'context_type' => 'event',
            'event_id' => $event->id,
            'orientation' => 'landscape',
            'background' => UploadedFile::fake()->image('hackathon.png', 1200, 850),
        ])->assertSessionHas('success');

        $template = CertificateTemplate::firstOrFail();
        $this->assertSame($template->id, CertificateTemplate::resolveFor(null, $event->id)?->id);
        $this->assertNull(CertificateTemplate::resolveFor($this->course->id, null));

        $this->actingAs($this->admin)->get(route('admin.elearning.certificates.templates.index'))
            ->assertOk()
            ->assertSee('Hackathon design')
            ->assertSee(route('admin.elearning.certificates.templates.preview', $template), false);

        $this->actingAs($this->admin)->get(route('admin.elearning.certificates.templates.preview', $template))->assertOk();

        $learner = $this->participant();
        $learner->forceFill(['email_verified_at' => now()])->save();
        EventCertificate::create(['event_id' => $event->id, 'user_id' => $learner->id, 'certificate_code' => 'code-1', 'issued_at' => now(), 'issued_by' => $this->admin->id]);
        $this->actingAs($learner)->get(route('events.certificate', $event))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->admin)->patch(route('admin.elearning.certificates.templates.toggle', $template));
        $this->assertNull(CertificateTemplate::resolveFor(null, $event->id));
    }
}
