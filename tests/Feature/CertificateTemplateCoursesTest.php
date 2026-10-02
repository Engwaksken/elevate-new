<?php

namespace Tests\Feature;

use App\Models\CertificateTemplate;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CertificateTemplateCoursesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::create(['name' => 'Super Administrator', 'slug' => 'super-administrator']));
        $this->actingAs($admin);
    }

    public function test_one_upload_applies_to_multiple_courses_and_can_be_disabled_or_deleted(): void
    {
        $first = Course::create(['title' => 'Course A']);
        $second = Course::create(['title' => 'Course B']);
        $other = Course::create(['title' => 'Course C']);
        $default = CertificateTemplate::create(['name' => 'Default', 'context_type' => 'default', 'is_active' => true, 'background_path' => 'default.png']);
        $this->post(route('admin.elearning.certificates.templates.store'), [
            'name' => 'Shared design', 'context_type' => 'course', 'course_ids' => [$first->id, $second->id],
            'orientation' => 'landscape', 'background' => UploadedFile::fake()->image('background.png'),
        ])->assertSessionHasNoErrors();
        $template = CertificateTemplate::where('name', 'Shared design')->sole();
        $this->assertSame(2, $template->courses()->count());
        $this->assertSame($template->id, CertificateTemplate::resolveFor($first->id)->id);
        $this->assertSame($template->id, CertificateTemplate::resolveFor($second->id)->id);
        $this->assertSame($default->id, CertificateTemplate::resolveFor($other->id)->id);
        $this->assertCount(1, Storage::disk('public')->allFiles('certificate-templates'));
        $this->get(route('admin.elearning.certificates.templates.index'))->assertOk()->assertSee('Course A')->assertSee('Course B');
        $this->patch(route('admin.elearning.certificates.templates.toggle', $template));
        $this->assertSame($default->id, CertificateTemplate::resolveFor($second->id)->id);
        $this->delete(route('admin.elearning.certificates.templates.destroy', $template));
        $this->assertDatabaseCount('certificate_template_course', 0);
        Storage::disk('public')->assertMissing($template->background_path);
    }

    public function test_missing_duplicate_and_invalid_courses_are_rejected(): void
    {
        $course = Course::create(['title' => 'Course']);
        foreach ([[], [$course->id, $course->id], [9999]] as $ids) {
            $this->post(route('admin.elearning.certificates.templates.store'), [
                'name' => 'Bad', 'context_type' => 'course', 'course_ids' => $ids,
                'orientation' => 'portrait', 'background' => UploadedFile::fake()->image('background.png'),
            ])->assertSessionHasErrors();
        }
        $this->assertDatabaseCount('certificate_templates', 0);
        $this->assertCount(0, Storage::disk('public')->allFiles());
    }
}
