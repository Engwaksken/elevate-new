<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificatePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTemplateCoursesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->admin->roles()->attach(Role::create(['name' => 'Super Administrator', 'slug' => 'super-administrator'])->id);
    }

    public function test_one_template_can_serve_several_courses_and_be_edited(): void
    {
        [$web, $data, $design] = collect(['Web Development', 'Data Analysis', 'UX Design'])
            ->map(fn ($title) => Course::create(['title' => $title, 'status' => 'published']))
            ->all();

        $this->actingAs($this->admin)->post(route('admin.elearning.certificates.templates.store'), [
            'name' => 'Tech Skills 2026',
            'context_type' => 'course',
            'course_ids' => [$web->id, $data->id],
            'orientation' => 'landscape',
            'background' => UploadedFile::fake()->image('tech.png', 1200, 850),
        ])->assertSessionHas('success');

        $template = CertificateTemplate::firstOrFail();
        $this->assertEqualsCanonicalizing([$web->id, $data->id], $template->courses->pluck('id')->all());
        $this->assertSame($template->id, CertificateTemplate::resolveFor($web->id)?->id);
        $this->assertSame($template->id, CertificateTemplate::resolveFor($data->id)?->id);
        $this->assertNull(CertificateTemplate::resolveFor($design->id));

        $this->actingAs($this->admin)->get(route('admin.elearning.certificates.templates.index'))
            ->assertOk()
            ->assertSee('Web Development, Data Analysis')
            ->assertSee('id="cert-template-edit-'.$template->id.'"', false);

        // Edit: swap Data Analysis for UX Design without replacing the image.
        $image = $template->background_path;
        $this->actingAs($this->admin)->put(route('admin.elearning.certificates.templates.update', $template), [
            'template_id' => $template->id,
            'name' => 'Tech Skills 2026',
            'context_type' => 'course',
            'course_ids' => [$web->id, $design->id],
            'orientation' => 'landscape',
        ])->assertSessionHas('success');

        $template->refresh();
        $this->assertSame($image, $template->background_path);
        $this->assertEqualsCanonicalizing([$web->id, $design->id], $template->courses->pluck('id')->all());
        $this->assertNull(CertificateTemplate::resolveFor($data->id));
        $this->assertSame($template->id, CertificateTemplate::resolveFor($design->id)?->id);
    }

    public function test_course_template_requires_at_least_one_course(): void
    {
        $this->actingAs($this->admin)->post(route('admin.elearning.certificates.templates.store'), [
            'name' => 'Empty',
            'context_type' => 'course',
            'orientation' => 'landscape',
            'background' => UploadedFile::fake()->image('x.png'),
        ])->assertSessionHasErrors('course_ids');

        $this->assertSame(0, CertificateTemplate::count());
    }

    public function test_legacy_single_course_templates_still_resolve(): void
    {
        $course = Course::create(['title' => 'Legacy Course', 'status' => 'published']);
        $template = CertificateTemplate::create([
            'name' => 'Old', 'context_type' => 'course', 'course_id' => $course->id,
            'background_path' => 'certificate-templates/old.png', 'orientation' => 'landscape', 'is_active' => true,
        ]);

        $this->assertSame($template->id, CertificateTemplate::resolveFor($course->id)?->id);
    }

    public function test_certificate_prints_full_name_cohort_and_period(): void
    {
        $course = Course::create(['title' => 'Digital Marketing', 'status' => 'published', 'start_date' => '2026-01-05', 'end_date' => '2026-06-30']);
        $cohort = Cohort::create(['name' => 'Kampala Cohort 3', 'start_date' => '2026-02-02', 'end_date' => '2026-04-24', 'status' => 'active']);
        $learner = User::factory()->create(['name' => 'jane', 'user_type' => 'participant', 'status' => 'active']);
        $learner->profile()->create(['given_name' => 'Jane', 'other_name' => 'Akello', 'surname' => 'Namusoke']);
        Enrolment::create(['course_id' => $course->id, 'user_id' => $learner->id, 'cohort_id' => $cohort->id, 'status' => 'completed']);
        $certificate = Certificate::create([
            'course_id' => $course->id, 'user_id' => $learner->id, 'certificate_number' => 'EH360-2026-ABC',
            'issued_on' => '2026-05-01', 'verification_token' => 'token-abc',
        ]);

        $details = app(CertificatePdfService::class)->details($certificate);

        $this->assertSame('Jane Akello Namusoke', $details['full_name']);
        $this->assertSame('Kampala Cohort 3', $details['cohort']);
        $this->assertSame('02 February – 24 April 2026', $details['period']);
        $this->assertSame($learner->fresh()->participant_code, $details['participant_code']);

        $html = view('certificates.pdf', ['certificate' => $certificate, 'template' => null, 'details' => $details])->render();
        $this->assertStringContainsString('Jane Akello Namusoke', $html);
        $this->assertStringContainsString('Kampala Cohort 3', $html);
        $this->assertStringContainsString('02 February – 24 April 2026', $html);

        // Without a cohort the course dates are used.
        Enrolment::where('user_id', $learner->id)->update(['cohort_id' => null]);
        $details = app(CertificatePdfService::class)->details($certificate->fresh());
        $this->assertNull($details['cohort']);
        $this->assertSame('05 January – 30 June 2026', $details['period']);

        // The participant's download is generated with these details.
        $learner->forceFill(['email_verified_at' => now()])->save();
        $this->actingAs($learner)->get(route('certificates.download', $certificate))->assertOk();
        Storage::disk('local')->assertExists('certificates/EH360-2026-ABC.pdf');
    }
}
