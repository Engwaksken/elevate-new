<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseCall;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CourseCallImportTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'administrator'], ['name' => 'Administrator']));

        return $user;
    }

    private function applicationCall(): CourseCall
    {
        $course = Course::create(['title' => 'Digital skills', 'status' => 'published']);
        $call = CourseCall::create(['title' => 'Applications', 'course_id' => $course->id, 'status' => 'closed']);
        $call->courses()->attach($course);

        return $call;
    }

    private function upload(CourseCall $call, string $csv)
    {
        return $this->post(route('admin.course-calls.import-applications', $call), [
            'file' => UploadedFile::fake()->createWithContent('applicants.csv', $csv),
        ]);
    }

    public function test_template_contains_only_headers_and_this_calls_questions(): void
    {
        $call = $this->applicationCall();
        $question = $call->questions()->create(['question_text' => 'Why apply?', 'is_required' => true]);
        $response = $this->actingAs($this->administrator())->get(route('admin.course-calls.import-template', $call));
        $response->assertOk()->assertDownload('application-call-'.$call->id.'-template.csv');
        $this->assertSame("\xEF\xBB\xBFname,email,phone,submitted_at,question_{$question->id}\n", $response->streamedContent());
        $this->actingAs($this->administrator())->get(route('admin.course-calls.applications', $call))
            ->assertOk()->assertSee('Download application template');
    }

    public function test_import_centre_includes_bulk_import_controls_for_application_calls(): void
    {
        $call = $this->applicationCall();
        $call->questions()->create(['question_text' => 'Why apply?', 'is_required' => true]);

        $this->actingAs($this->administrator())
            ->get(route('admin.import-centre.index'))
            ->assertOk()
            ->assertSee('Application call bulk imports')
            ->assertSee('<details class="admin-panel"', false)
            ->assertSee($call->title)
            ->assertSee(route('admin.course-calls.import-template', $call), false)
            ->assertSee(route('admin.course-calls.import-applications', $call), false);
    }

    public function test_import_creates_submitted_applications_and_answers_without_enrolling(): void
    {
        $call = $this->applicationCall();
        $question = $call->questions()->create([
            'question_text' => 'Skills', 'question_type' => 'multiple_choice',
            'options' => ['Excel', 'Word'], 'is_required' => true,
        ]);
        $existing = User::factory()->create(['email' => 'existing@example.com', 'user_type' => 'participant', 'status' => 'active']);
        $this->actingAs($this->administrator());
        $csv = "\xEF\xBB\xBFname,email,phone,submitted_at,question_{$question->id}\nNew Applicant,NEW@example.com,+256700000000,2026-10-01 10:00:00,Excel|Word\nChanged,existing@example.com,,,Word\n";
        $this->upload($call, $csv)->assertSessionHasNoErrors()->assertSessionHas('success', '2 application(s) imported for review. 0 existing application(s) skipped.');
        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('participant', $user->user_type);
        $this->assertNull($user->email_verified_at);
        $this->assertNotNull($user->participant_code);
        $application = $call->applications()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('submitted', $application->status);
        $this->assertSame('2026-10-01 10:00:00', $application->submitted_at->format('Y-m-d H:i:s'));
        $this->assertSame(['Excel', 'Word'], $application->answers()->first()->answer_json);
        $this->assertSame($existing->name, $existing->fresh()->name);
        $this->assertDatabaseCount('enrolments', 0);

        $application->update(['status' => 'approved', 'reviewer_comments' => 'Keep this']);
        $this->upload($call, $csv)->assertSessionHasNoErrors()->assertSessionHas('success', '0 application(s) imported for review. 2 existing application(s) skipped.');
        $this->assertSame('approved', $application->fresh()->status);
        $this->assertSame('Keep this', $application->fresh()->reviewer_comments);
        $this->assertSame(2, $call->applications()->count());
    }

    public function test_invalid_row_rejects_entire_file(): void
    {
        $call = $this->applicationCall();
        $this->actingAs($this->administrator());
        $this->upload($call, "name,email\nValid,valid@example.com\nInvalid,not-an-email\n")
            ->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('users', ['email' => 'valid@example.com']);
        $this->assertSame(0, $call->applications()->count());
    }

    public function test_non_participant_email_rolls_back_prior_rows(): void
    {
        $call = $this->applicationCall();
        $admin = $this->administrator();
        $this->actingAs($admin);
        $this->upload($call, "name,email\nValid,valid@example.com\nStaff,{$admin->email}\n")
            ->assertSessionHasErrors('file');
        $this->assertDatabaseMissing('users', ['email' => 'valid@example.com']);
        $this->assertSame(0, $call->applications()->count());
    }

    public function test_required_answers_and_foreign_question_headers_are_rejected(): void
    {
        $call = $this->applicationCall();
        $call->questions()->create(['question_text' => 'Why?', 'is_required' => true]);
        $this->actingAs($this->administrator());
        foreach (["name,email\nApplicant,a@example.com\n", "name,email,question_999999\nApplicant,a@example.com,Answer\n", "name,email,email\nApplicant,a@example.com,a@example.com\n", "name,email\n"] as $csv) {
            $this->upload($call, $csv)->assertSessionHasErrors('file');
        }
        $this->assertSame(0, $call->applications()->count());
    }

    public function test_staff_with_manage_permission_but_without_admin_role_cannot_import(): void
    {
        $call = $this->applicationCall();
        $staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $role = Role::create(['slug' => 'import-test-staff', 'name' => 'Import test staff']);
        $role->permissions()->attach(Permission::firstOrCreate(['slug' => 'course_calls.manage'], ['name' => 'Manage calls', 'module' => 'elearning']));
        $staff->roles()->attach($role);
        $this->actingAs($staff)->get(route('admin.course-calls.import-template', $call))->assertForbidden();
        $this->upload($call, "name,email\nApplicant,a@example.com\n")->assertForbidden();
        $this->assertSame(0, $call->applications()->count());
    }
}
