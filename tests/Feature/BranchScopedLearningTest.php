<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Models\AssessmentCohortDeadline;
use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseAnnouncement;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\Programme;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * One shared course delivered at two branches (Kampala, Gulu): shared content,
 * group deadlines, group announcements, group-only lessons and branch instructors.
 */
class BranchScopedLearningTest extends TestCase
{
    use RefreshDatabase;

    private const API = '/api/v1/participant';

    private Course $course;
    private Cohort $kampalaCohort;
    private Cohort $guluCohort;
    private Branch $kampala;
    private User $kampalaLearner;
    private User $guluLearner;
    private User $lead;
    private User $kampalaInstructor;
    private CourseModule $module;
    private Lesson $sharedLesson;
    private Lesson $kampalaLesson;

    protected function setUp(): void
    {
        parent::setUp();

        $programme = Programme::create(['name' => 'Programme', 'code' => 'PRG']);
        $this->kampala = Branch::create(['name' => 'Kampala', 'code' => 'KLA', 'is_active' => true]);
        $gulu = Branch::create(['name' => 'Gulu', 'code' => 'GUL', 'is_active' => true]);

        $this->course = Course::create(['title' => 'Digital Skills', 'code' => 'DS', 'programme_id' => $programme->id, 'status' => 'published']);
        $this->kampalaCohort = Cohort::create(['name' => 'Kampala Jan', 'code' => 'KLA-1', 'programme_id' => $programme->id, 'branch_id' => $this->kampala->id]);
        $this->guluCohort = Cohort::create(['name' => 'Gulu Jan', 'code' => 'GUL-1', 'programme_id' => $programme->id, 'branch_id' => $gulu->id]);
        $this->course->cohorts()->attach([$this->kampalaCohort->id, $this->guluCohort->id]);

        $this->kampalaLearner = $this->learner($this->kampalaCohort);
        $this->guluLearner = $this->learner($this->guluCohort);

        $this->lead = $this->instructor();
        $this->course->instructors()->attach($this->lead->id, ['is_lead' => true]);

        $this->kampalaInstructor = $this->instructor();
        $this->kampalaInstructor->branches()->attach($this->kampala->id);
        $this->course->instructors()->attach($this->kampalaInstructor->id, ['is_lead' => false]);

        $this->module = CourseModule::create(['course_id' => $this->course->id, 'title' => 'Basics', 'position' => 1, 'is_published' => true]);
        $this->sharedLesson = Lesson::create(['course_module_id' => $this->module->id, 'title' => 'Shared lesson', 'content_type' => 'text', 'position' => 1, 'is_published' => true]);
        $this->kampalaLesson = Lesson::create(['course_module_id' => $this->module->id, 'title' => 'Kampala market visit', 'content_type' => 'text', 'position' => 2, 'is_published' => true, 'cohort_id' => $this->kampalaCohort->id]);
    }

    private function learner(Cohort $cohort): User
    {
        $user = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);
        Enrolment::create(['course_id' => $this->course->id, 'user_id' => $user->id, 'cohort_id' => $cohort->id, 'status' => 'enrolled']);

        return $user;
    }

    private function instructor(): User
    {
        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $user->roles()->attach(Role::firstOrCreate(['slug' => 'instructor'], ['name' => 'Instructor']));

        return $user;
    }

    private function assignment(array $attributes = []): Assessment
    {
        return Assessment::create($attributes + [
            'course_id' => $this->course->id, 'title' => 'Business plan', 'type' => 'assignment',
            'pass_mark' => 50, 'max_attempts' => 1, 'is_published' => true, 'due_at' => now()->addDays(3),
        ]);
    }

    /* ------------------------------------------------------------ 1. group deadlines */

    public function test_a_group_deadline_replaces_the_course_date_for_that_group_only(): void
    {
        $assessment = $this->assignment(['due_at' => now()->subDay()]);
        AssessmentCohortDeadline::create(['assessment_id' => $assessment->id, 'cohort_id' => $this->kampalaCohort->id, 'due_at' => now()->addWeek()]);

        Sanctum::actingAs($this->kampalaLearner, ['participant']);
        $kampala = collect($this->getJson(self::API."/courses/{$this->course->id}")->assertOk()->json('course.assessments'))->firstWhere('id', $assessment->id);
        $this->assertFalse($kampala['is_overdue']);
        $this->assertTrue($kampala['can_submit']);
        // The app treats due_at as her own original date, so it is not shown as "extended".
        $this->assertSame(
            \Illuminate\Support\Carbon::parse($kampala['effective_due_at'])->timestamp,
            \Illuminate\Support\Carbon::parse($kampala['due_at'])->timestamp
        );

        Sanctum::actingAs($this->guluLearner, ['participant']);
        $gulu = collect($this->getJson(self::API."/courses/{$this->course->id}")->assertOk()->json('course.assessments'))->firstWhere('id', $assessment->id);
        $this->assertTrue($gulu['is_overdue']);
        $this->assertFalse($gulu['can_submit']);
    }

    public function test_branch_instructor_sets_their_groups_deadline_and_that_group_is_notified(): void
    {
        $assessment = $this->assignment();

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.assessments.group-deadlines', [$this->course, $assessment]), [
                'cohort_due' => [
                    $this->kampalaCohort->id => now()->addDays(10)->format('Y-m-d\TH:i'),
                    $this->guluCohort->id => now()->addDays(20)->format('Y-m-d\TH:i'), // not their group: ignored
                ],
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('assessment_cohort_deadlines', ['assessment_id' => $assessment->id, 'cohort_id' => $this->kampalaCohort->id]);
        $this->assertDatabaseMissing('assessment_cohort_deadlines', ['assessment_id' => $assessment->id, 'cohort_id' => $this->guluCohort->id]);

        $this->assertTrue(UserNotification::where('user_id', $this->kampalaLearner->id)->where('type', 'assignment_due_date_changed')->exists());
        $this->assertFalse(UserNotification::where('user_id', $this->guluLearner->id)->where('type', 'assignment_due_date_changed')->exists());

        // Clearing it returns the group to the course date.
        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.assessments.group-deadlines', [$this->course, $assessment]), [
                'cohort_due' => [$this->kampalaCohort->id => ''],
            ]);
        $this->assertDatabaseMissing('assessment_cohort_deadlines', ['assessment_id' => $assessment->id]);
    }

    public function test_new_assignment_tells_each_group_its_own_due_date(): void
    {
        $courseDue = now()->addDays(5)->setTime(17, 0);
        $kampalaDue = now()->addDays(9)->setTime(17, 0);

        $this->actingAs($this->lead)->post(route('instructor.courses.assessments.store', $this->course), [
            'type' => 'assignment', 'title' => 'Market research', 'pass_mark' => 50, 'max_attempts' => 1,
            'due_at' => $courseDue->format('Y-m-d\TH:i'), 'publish' => 1,
            'cohort_due' => [$this->kampalaCohort->id => $kampalaDue->format('Y-m-d\TH:i')],
        ])->assertSessionHasNoErrors();

        $kampalaNote = UserNotification::where('user_id', $this->kampalaLearner->id)->where('type', 'assignment_published')->sole();
        $guluNote = UserNotification::where('user_id', $this->guluLearner->id)->where('type', 'assignment_published')->sole();

        $this->assertStringContainsString($kampalaDue->format('d M Y H:i'), $kampalaNote->message);
        $this->assertStringContainsString($courseDue->format('d M Y H:i'), $guluNote->message);
    }

    /* ------------------------------------------------------------ 2. group announcements */

    public function test_group_announcement_reaches_that_group_only(): void
    {
        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.announcements.store', $this->course), [
                'title' => 'Kampala class moved', 'body' => 'We meet in room 4 on Friday.', 'cohort_id' => $this->kampalaCohort->id,
            ])
            ->assertSessionHas('success');

        $announcement = CourseAnnouncement::sole();
        $this->assertSame($this->kampalaCohort->id, (int) $announcement->cohort_id);

        $this->assertTrue(UserNotification::where('user_id', $this->kampalaLearner->id)->where('type', 'course_announcement')->exists());
        $this->assertFalse(UserNotification::where('user_id', $this->guluLearner->id)->where('type', 'course_announcement')->exists());

        Sanctum::actingAs($this->guluLearner, ['participant']);
        $this->assertSame([], $this->getJson(self::API.'/announcements')->assertOk()->json('data'));
        $this->assertSame([], $this->getJson(self::API."/courses/{$this->course->id}")->json('course.announcements'));

        Sanctum::actingAs($this->kampalaLearner, ['participant']);
        $this->assertCount(1, $this->getJson(self::API.'/announcements')->json('data'));

        // Web course page shows it to Kampala only.
        $this->actingAs($this->kampalaLearner)->get(route('learning.course.dashboard', $this->course))->assertOk()->assertSee('Kampala class moved');
        $this->actingAs($this->guluLearner)->get(route('learning.course.dashboard', $this->course))->assertOk()->assertDontSee('Kampala class moved');
    }

    public function test_branch_instructor_cannot_post_to_the_whole_course_or_another_group(): void
    {
        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.announcements.store', $this->course), ['title' => 'All', 'body' => 'Everyone'])
            ->assertForbidden();

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.announcements.store', $this->course), ['title' => 'Gulu', 'body' => 'Gulu only', 'cohort_id' => $this->guluCohort->id])
            ->assertForbidden();

        $this->actingAs($this->lead)
            ->post(route('instructor.courses.announcements.store', $this->course), ['title' => 'All', 'body' => 'Everyone'])
            ->assertSessionHas('success');
    }

    /* ------------------------------------------------------------ 3. group-only lessons */

    public function test_group_lesson_is_visible_and_counted_for_that_group_only(): void
    {
        Sanctum::actingAs($this->guluLearner, ['participant']);
        $titles = collect($this->getJson(self::API."/courses/{$this->course->id}")->assertOk()->json('course.modules.0.lessons'))->pluck('title');
        $this->assertSame(['Shared lesson'], $titles->all());
        $this->getJson(self::API."/lessons/{$this->kampalaLesson->id}")->assertNotFound();

        $this->actingAs($this->guluLearner)->get(route('learning.lesson.show', $this->kampalaLesson))->assertForbidden();
        $this->actingAs($this->guluLearner)->get(route('learning.course.dashboard', $this->course))->assertDontSee('Kampala market visit');

        Sanctum::actingAs($this->kampalaLearner, ['participant']);
        $titles = collect($this->getJson(self::API."/courses/{$this->course->id}")->json('course.modules.0.lessons'))->pluck('title');
        $this->assertSame(['Shared lesson', 'Kampala market visit'], $titles->all());

        // Gulu finishes the only shared lesson: 100%, the Kampala extra lesson does not count against her.
        $this->actingAs($this->guluLearner)->post(route('learning.lesson.complete', $this->sharedLesson));
        $this->assertSame(100.0, (float) Enrolment::where('user_id', $this->guluLearner->id)->value('progress_percent'));
    }

    public function test_branch_instructor_adds_group_lessons_but_not_shared_ones_when_the_course_has_a_lead(): void
    {
        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Kampala recap', 'content_type' => 'text', 'is_published' => 1, 'cohort_id' => $this->kampalaCohort->id,
            ])
            ->assertSessionHas('success');
        $this->assertSame($this->kampalaCohort->id, (int) Lesson::where('title', 'Kampala recap')->value('cohort_id'));

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Shared attempt', 'content_type' => 'text',
            ])
            ->assertForbidden();

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->module, $this->sharedLesson]), [
                'title' => 'Renamed', 'content_type' => 'text',
            ])
            ->assertForbidden();

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.assessments.store', $this->course), [
                'type' => 'quiz', 'title' => 'Quiz', 'pass_mark' => 50, 'max_attempts' => 1,
            ])
            ->assertForbidden();
    }

    public function test_without_a_lead_instructor_branch_instructors_can_still_edit_shared_content(): void
    {
        $this->course->instructors()->updateExistingPivot($this->lead->id, ['is_lead' => false]);

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Shared by branch', 'content_type' => 'text',
            ])
            ->assertSessionHas('success');

        $this->assertNull(Lesson::where('title', 'Shared by branch')->value('cohort_id'));
    }

    /* ------------------------------------------------------------ 4. instructor scope */

    public function test_branch_instructor_sees_and_marks_only_their_branch(): void
    {
        $assessment = $this->assignment();
        $kampalaAttempt = AssessmentAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $this->kampalaLearner->id, 'attempt_number' => 1, 'status' => 'submitted', 'submitted_at' => now()]);
        $guluAttempt = AssessmentAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $this->guluLearner->id, 'attempt_number' => 1, 'status' => 'submitted', 'submitted_at' => now()]);

        $page = $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->assertOk()
            ->assertSee('your branch', false);

        $this->assertSame([$this->kampalaLearner->id], $page->viewData('participants')->pluck('user_id')->map(fn ($id) => (int) $id)->all());

        $submissions = $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'submissions']))
            ->viewData('submissions');
        $this->assertSame([$kampalaAttempt->id], $submissions->pluck('id')->all());

        $review = ['status' => 'graded', 'percentage' => 80];
        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.submissions.review', [$this->course, $kampalaAttempt]), $review)
            ->assertSessionHas('success');
        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.submissions.review', [$this->course, $guluAttempt]), $review)
            ->assertForbidden();

        // The lead still sees everyone.
        $all = $this->actingAs($this->lead)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->viewData('participants');
        $this->assertCount(2, $all);
    }

    public function test_submission_notifications_go_to_the_participants_own_branch_instructor(): void
    {
        $assessment = $this->assignment();

        AssessmentAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $this->guluLearner->id, 'attempt_number' => 1, 'status' => 'submitted', 'submitted_at' => now()]);

        $this->assertTrue(UserNotification::where('user_id', $this->lead->id)->where('type', 'assignment_submitted')->exists());
        $this->assertFalse(UserNotification::where('user_id', $this->kampalaInstructor->id)->where('type', 'assignment_submitted')->exists());

        AssessmentAttempt::create(['assessment_id' => $assessment->id, 'user_id' => $this->kampalaLearner->id, 'attempt_number' => 1, 'status' => 'submitted', 'submitted_at' => now()]);
        $this->assertTrue(UserNotification::where('user_id', $this->kampalaInstructor->id)->where('type', 'assignment_submitted')->exists());
    }

    public function test_instructor_without_a_branch_keeps_full_access(): void
    {
        $this->kampalaInstructor->branches()->detach();

        $participants = $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->assertOk()
            ->viewData('participants');

        $this->assertCount(2, $participants);
    }

    public function test_instructor_without_a_branch_adds_shared_lessons_on_a_course_with_a_lead(): void
    {
        $this->kampalaInstructor->branches()->detach();

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'lessons' => [['title' => 'Week 2 slides', 'content_type' => 'file']],
                'is_published' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertNull(Lesson::where('title', 'Week 2 slides')->value('cohort_id'));

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.lessons.update', [$this->course, $this->module, $this->sharedLesson]), [
                'title' => 'Shared lesson (updated)', 'content_type' => 'text',
            ])
            ->assertSessionHas('success');
    }

    public function test_branch_instructor_with_no_group_at_their_branch_can_still_add_lessons(): void
    {
        $this->course->cohorts()->detach($this->kampalaCohort->id);

        $this->actingAs($this->kampalaInstructor)
            ->post(route('instructor.courses.lessons.store', [$this->course, $this->module]), [
                'title' => 'Kampala instructor shared notes', 'content_type' => 'text',
            ])
            ->assertSessionHas('success');

        // Still limited to their own branch's participants (none here).
        $participants = $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->assertOk()
            ->viewData('participants');
        $this->assertCount(0, $participants);
    }

    public function test_upload_limit_never_exceeds_what_php_accepts(): void
    {
        $service = app(\App\Services\Learning\LearningFileService::class);
        $php = \App\Services\Learning\LearningFileService::iniBytes((string) ini_get('upload_max_filesize'));

        $this->assertSame(64 * 1024 * 1024, \App\Services\Learning\LearningFileService::iniBytes('64M'));
        $this->assertSame(2048, \App\Services\Learning\LearningFileService::iniBytes('2K'));
        $this->assertLessThanOrEqual(intdiv($php, 1024), $service->maxFileKb());
        $this->assertLessThanOrEqual((int) config('elearning.max_file_kb'), $service->maxFileKb());
    }
}
