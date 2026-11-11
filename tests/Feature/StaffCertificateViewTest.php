<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateRecommendation;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Event;
use App\Models\EventCertificate;
use App\Models\EventRegistration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Instructors and administrators can open participants' certificates from the
 * Actions column: issued ones, and draft previews of recommended ones.
 */
class StaffCertificateViewTest extends TestCase
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

    private function staff(string $role): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => $role], ['name' => ucwords(str_replace('-', ' ', $role))])->id);

        return $user;
    }

    private function instructorFor(Course $course): User
    {
        $instructor = $this->staff('instructor');
        $instructor->instructedCourses()->attach($course->id);

        return $instructor;
    }

    private function learner(): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'name' => 'Amina Otieno']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'status' => 'completed', 'progress_percent' => 100]);

        return $user;
    }

    private function recommend(User $instructor, User $learner): CertificateRecommendation
    {
        $this->actingAs($instructor)->post(route('certificates.recommendations.store'), [
            'course_id' => $this->course->id, 'user_ids' => [$learner->id], 'reason' => 'Finished the course.',
        ])->assertSessionHas('success');

        return CertificateRecommendation::firstOrFail();
    }

    public function test_instructor_previews_a_recommended_certificate_without_issuing_it(): void
    {
        $instructor = $this->instructorFor($this->course);
        $recommendation = $this->recommend($instructor, $this->learner());

        $this->actingAs($instructor)
            ->get(route('certificates.recommendations.index'))
            ->assertOk()
            ->assertSee('Preview certificate')
            ->assertSee(route('certificates.staff.recommendation', $recommendation), false);

        $response = $this->get(route('certificates.staff.recommendation', $recommendation))->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->assertSame(0, Certificate::count());
        $this->assertSame('pending', $recommendation->fresh()->status);
    }

    public function test_approved_recommendation_opens_the_issued_certificate(): void
    {
        $instructor = $this->instructorFor($this->course);
        $recommendation = $this->recommend($instructor, $this->learner());

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), [
            'ids' => [$recommendation->id], 'decision' => 'approve',
        ]);
        $certificate = Certificate::firstOrFail();

        $this->get(route('certificates.recommendations.index'))->assertSee('View certificate');

        $this->actingAs($instructor)
            ->get(route('certificates.staff.recommendation', $recommendation))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->admin)
            ->get(route('admin.elearning.certificates.index'))
            ->assertOk()
            ->assertSee(route('certificates.staff.show', ['type' => 'course', 'id' => $certificate->id]), false);

        $this->get(route('certificates.staff.show', ['type' => 'course', 'id' => $certificate->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_instructor_participants_tab_shows_certificate_actions(): void
    {
        $instructor = $this->instructorFor($this->course);
        $recommendation = $this->recommend($instructor, $this->learner());

        $this->actingAs($instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->assertOk()
            ->assertSee(route('certificates.staff.recommendation', $recommendation), false)
            ->assertSee('Preview certificate');
    }

    public function test_instructors_cannot_open_other_courses_certificates_or_participants_any(): void
    {
        $instructor = $this->instructorFor($this->course);
        $recommendation = $this->recommend($instructor, $learner = $this->learner());
        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), ['ids' => [$recommendation->id], 'decision' => 'approve']);
        $certificate = Certificate::firstOrFail();

        $outsider = $this->instructorFor(Course::create(['title' => 'Tailoring', 'status' => 'published']));

        $this->actingAs($outsider)->get(route('certificates.staff.recommendation', $recommendation))->assertForbidden();
        $this->actingAs($outsider)->get(route('certificates.staff.show', ['type' => 'course', 'id' => $certificate->id]))->assertForbidden();
        $this->actingAs($learner)->get(route('certificates.staff.show', ['type' => 'course', 'id' => $certificate->id]))->assertForbidden();
    }

    public function test_event_recommendation_preview_and_issued_certificate(): void
    {
        $officer = $this->staff('program-officer');
        $learner = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        $event = Event::create([
            'title' => 'Women in Tech Summit', 'event_type' => 'training', 'delivery_mode' => 'physical',
            'starts_at' => now()->subDay(), 'certificate_enabled' => false,
        ]);
        EventRegistration::create(['event_id' => $event->id, 'user_id' => $learner->id, 'status' => 'registered', 'registered_at' => now()]);

        $this->actingAs($officer)->post(route('certificates.recommendations.store'), [
            'event_id' => $event->id, 'user_ids' => [$learner->id], 'reason' => 'Facilitated a session.',
        ])->assertSessionHas('success');
        $recommendation = CertificateRecommendation::firstOrFail();

        $this->get(route('certificates.staff.recommendation', $recommendation))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame(0, EventCertificate::count());

        $this->actingAs($this->admin)->post(route('certificates.recommendations.review'), ['ids' => [$recommendation->id], 'decision' => 'approve']);
        $issued = EventCertificate::firstOrFail();

        $this->get(route('certificates.staff.show', ['type' => 'event', 'id' => $issued->id]))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
}
