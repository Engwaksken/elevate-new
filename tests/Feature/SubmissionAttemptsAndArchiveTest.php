<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

/**
 * Resubmissions show as one row with an attempt count, and instructors can
 * download every submission as a ZIP with a folder per participant.
 */
class SubmissionAttemptsAndArchiveTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private User $instructor;
    private Assessment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->course = Course::create(['title' => 'Bookkeeping', 'status' => 'published']);
        $this->instructor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->instructor->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));
        $this->course->instructors()->attach($this->instructor->id);

        $this->assignment = Assessment::create([
            'course_id' => $this->course->id, 'title' => 'Ledger exercise', 'type' => 'assignment',
            'max_attempts' => 3, 'pass_mark' => 50, 'is_published' => true, 'max_submission_files' => 3,
        ]);
    }

    private function participant(string $name): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active', 'name' => $name]);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'status' => 'enrolled']);

        return $user;
    }

    private function submit(User $user, string $file, string $text = ''): void
    {
        $this->actingAs($user)
            ->post(route('learning.assessment.submit', $this->assignment), [
                'submission_text' => $text,
                'submission_files' => [UploadedFile::fake()->createWithContent($file, 'content of '.$file)],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_resubmissions_show_once_with_the_attempt_count(): void
    {
        $amina = $this->participant('Amina Otieno');
        $this->submit($amina, 'draft.pdf');
        $this->submit($amina, 'final.pdf');
        $this->submit($this->participant('Grace Wanjiru'), 'grace.pdf');

        $this->assertSame(3, AssessmentAttempt::count());

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'submissions']))
            ->assertOk()
            ->assertSee('Earlier attempts (1)')
            ->assertSee('2 of 3');

        $submissions = $response->viewData('submissions');
        $this->assertCount(2, $submissions);
        $row = $submissions->firstWhere('user_id', $amina->id);
        $this->assertSame(2, (int) $row->attempt_number);
        $this->assertSame(2, (int) $row->attempts_total);
        $this->assertCount(1, $row->earlierAttempts);
    }

    public function test_gradebook_lists_each_learner_once_per_assessment(): void
    {
        $amina = $this->participant('Amina Otieno');
        $this->submit($amina, 'draft.pdf');
        $this->submit($amina, 'final.pdf');

        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']));

        $attempts = $this->actingAs($admin)
            ->get(route('admin.elearning.gradebook.index', $this->course))
            ->assertOk()
            ->viewData('attempts');

        $this->assertCount(1, $attempts);
        $this->assertSame(2, (int) $attempts->first()->attempts_total);
    }

    public function test_zip_has_a_folder_per_participant_with_earlier_attempts(): void
    {
        $amina = $this->participant('Amina Otieno');
        $this->submit($amina, 'draft.pdf');
        $this->submit($amina, 'final.pdf', 'My notes');
        $this->submit($this->participant('Grace Wanjiru'), 'grace.pdf');

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.courses.submissions.download', [$this->course, 'assessment_id' => $this->assignment->id]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $entries = $this->zipEntries($response->baseResponse->getFile()->getPathname());
        $amina = collect($entries)->first(fn ($e) => str_starts_with($e, 'Amina Otieno'));
        $folder = explode('/', $amina)[0];

        $this->assertContains($folder.'/final.pdf', $entries);
        $this->assertContains($folder.'/response.txt', $entries);
        $this->assertContains($folder.'/Earlier attempts/Attempt 1/draft.pdf', $entries);
        $this->assertNotNull(collect($entries)->first(fn ($e) => str_starts_with($e, 'Grace Wanjiru') && str_ends_with($e, '/grace.pdf')));
    }

    public function test_whole_course_zip_groups_by_assessment(): void
    {
        $this->submit($this->participant('Amina Otieno'), 'final.pdf');

        $response = $this->actingAs($this->instructor)
            ->get(route('instructor.courses.submissions.download', $this->course))
            ->assertOk();

        $entries = $this->zipEntries($response->baseResponse->getFile()->getPathname());
        $this->assertNotNull(collect($entries)->first(fn ($e) => str_starts_with($e, 'Ledger exercise/Amina Otieno') && str_ends_with($e, '/final.pdf')));
    }

    public function test_only_course_instructors_can_download(): void
    {
        $outsider = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $outsider->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));

        $this->actingAs($outsider)
            ->get(route('instructor.courses.submissions.download', $this->course))
            ->assertForbidden();
    }

    /** @return array<int,string> */
    private function zipEntries(string $path): array
    {
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path) === true);
        $entries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entries[] = $zip->getNameIndex($i);
        }
        $zip->close();

        return $entries;
    }
}
