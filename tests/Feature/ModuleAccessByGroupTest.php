<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Cohort;
use App\Models\Course;
use App\Models\CourseCohortLearningSetting;
use App\Models\CourseModule;
use App\Models\Enrolment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Programme;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Learning\ModuleAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAccessByGroupTest extends TestCase
{
    use RefreshDatabase;

    private Course $course;
    private Cohort $kampalaCohort;
    private Cohort $guluCohort;
    private User $kampalaLearner;
    private User $guluLearner;
    private User $lead;
    private User $kampalaInstructor;
    private CourseModule $first;
    private CourseModule $second;
    private Lesson $firstLesson;

    protected function setUp(): void
    {
        parent::setUp();

        $programme = Programme::create(['name' => 'Programme', 'code' => 'PRG']);
        $kampala = Branch::create(['name' => 'Kampala', 'code' => 'KLA', 'is_active' => true]);
        $gulu = Branch::create(['name' => 'Gulu', 'code' => 'GUL', 'is_active' => true]);

        $this->course = Course::create(['title' => 'Digital Skills', 'code' => 'DS', 'programme_id' => $programme->id, 'status' => 'published']);
        $this->kampalaCohort = Cohort::create(['name' => 'Kampala Jan', 'code' => 'KLA-1', 'programme_id' => $programme->id, 'branch_id' => $kampala->id]);
        $this->guluCohort = Cohort::create(['name' => 'Gulu Jan', 'code' => 'GUL-1', 'programme_id' => $programme->id, 'branch_id' => $gulu->id]);
        $this->course->cohorts()->attach([$this->kampalaCohort->id, $this->guluCohort->id]);

        $this->kampalaLearner = $this->learner($this->kampalaCohort);
        $this->guluLearner = $this->learner($this->guluCohort);

        $this->lead = $this->instructor();
        $this->course->instructors()->attach($this->lead->id, ['is_lead' => true]);
        $this->kampalaInstructor = $this->instructor();
        $this->kampalaInstructor->branches()->attach($kampala->id);
        $this->course->instructors()->attach($this->kampalaInstructor->id, ['is_lead' => false]);

        $this->first = CourseModule::create(['course_id' => $this->course->id, 'title' => 'Basics', 'position' => 1, 'is_published' => true]);
        $this->second = CourseModule::create(['course_id' => $this->course->id, 'title' => 'Selling online', 'position' => 2, 'is_published' => true]);
        $this->firstLesson = Lesson::create(['course_module_id' => $this->first->id, 'title' => 'Intro', 'content_type' => 'text', 'position' => 1, 'is_published' => true]);
        Lesson::create(['course_module_id' => $this->second->id, 'title' => 'Shops', 'content_type' => 'text', 'position' => 1, 'is_published' => true]);
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

    private function canOpenSecond(User $learner): bool
    {
        return app(ModuleAccessService::class)->canAccess($this->second->fresh(), $learner);
    }

    private function completeFirst(User $learner): void
    {
        LessonProgress::create(['lesson_id' => $this->firstLesson->id, 'user_id' => $learner->id, 'completed_at' => now()]);
    }

    public function test_page_is_linked_and_viewing_it_changes_nothing(): void
    {
        $this->actingAs($this->lead)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'modules']))
            ->assertOk()
            ->assertSee(route('instructor.courses.module-access', $this->course), false);

        $this->actingAs($this->lead)
            ->get(route('instructor.courses.module-access', $this->course))
            ->assertOk()
            ->assertSee('Kampala Jan')
            ->assertSee('Gulu Jan')
            ->assertSee('All modules open');

        $this->assertSame(0, CourseCohortLearningSetting::count());
        $this->assertTrue($this->canOpenSecond($this->kampalaLearner));
    }

    public function test_release_mode_locks_until_the_instructor_releases_for_that_group(): void
    {
        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['cohort_id' => $this->kampalaCohort->id, 'mode' => 'release'])
            ->assertRedirect();

        $this->completeFirst($this->kampalaLearner);
        $this->assertFalse($this->canOpenSecond($this->kampalaLearner));
        $this->assertTrue($this->canOpenSecond($this->guluLearner), 'Gulu has no rule, so its modules stay open.');

        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.release', [$this->course, $this->second]), ['cohort_id' => $this->kampalaCohort->id, 'is_released' => 1])
            ->assertRedirect();

        $this->assertTrue($this->canOpenSecond($this->kampalaLearner));
        $this->assertTrue(UserNotification::where('user_id', $this->kampalaLearner->id)->where('type', 'module_released')->exists());
        $this->assertFalse(UserNotification::where('user_id', $this->guluLearner->id)->where('type', 'module_released')->exists());

        // Locking it again closes it.
        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.release', [$this->course, $this->second]), ['cohort_id' => $this->kampalaCohort->id, 'is_released' => 0]);
        $this->assertFalse($this->canOpenSecond($this->kampalaLearner));
    }

    public function test_in_order_mode_opens_the_next_module_on_completion(): void
    {
        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['cohort_id' => $this->guluCohort->id, 'mode' => 'sequential']);

        $this->assertFalse($this->canOpenSecond($this->guluLearner));
        $this->completeFirst($this->guluLearner);
        $this->assertTrue($this->canOpenSecond($this->guluLearner));
    }

    public function test_branch_instructor_manages_only_their_own_groups(): void
    {
        $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.module-access', $this->course))
            ->assertOk()
            ->assertSee('Kampala Jan')
            ->assertDontSee('Gulu Jan');

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['cohort_id' => $this->guluCohort->id, 'mode' => 'release'])
            ->assertForbidden();

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.module-access.release', [$this->course, $this->second]), ['cohort_id' => $this->guluCohort->id, 'is_released' => 1])
            ->assertForbidden();

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['cohort_id' => $this->kampalaCohort->id, 'mode' => 'release'])
            ->assertRedirect();
    }

    public function test_one_save_sets_module_access_for_all_groups(): void
    {
        $this->actingAs($this->lead)
            ->get(route('instructor.courses.module-access', [$this->course, 'target' => 'all']))
            ->assertOk()
            ->assertSee('All groups (2)');

        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['target' => 'all', 'mode' => 'release'])
            ->assertRedirect();
        $this->assertSame(2, CourseCohortLearningSetting::where('instructor_release_required', true)->count());

        $this->completeFirst($this->kampalaLearner);
        $this->completeFirst($this->guluLearner);
        $this->assertFalse($this->canOpenSecond($this->kampalaLearner));
        $this->assertFalse($this->canOpenSecond($this->guluLearner));

        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.release', [$this->course, $this->second]), ['target' => 'all', 'is_released' => 1])
            ->assertRedirect();

        $this->assertTrue($this->canOpenSecond($this->kampalaLearner));
        $this->assertTrue($this->canOpenSecond($this->guluLearner));
        $this->assertTrue(UserNotification::where('user_id', $this->guluLearner->id)->where('type', 'module_released')->exists());
    }

    public function test_a_branch_target_changes_only_that_branchs_groups(): void
    {
        $gulu = Branch::where('code', 'GUL')->first();

        $this->actingAs($this->lead)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['target' => 'branch:'.$gulu->id, 'mode' => 'sequential'])
            ->assertRedirect();

        $this->assertFalse($this->canOpenSecond($this->guluLearner));
        $this->assertTrue($this->canOpenSecond($this->kampalaLearner));
    }

    public function test_branch_instructor_cannot_target_all_groups_or_another_branch(): void
    {
        $gulu = Branch::where('code', 'GUL')->first();

        foreach (['all', 'branch:'.$gulu->id] as $target) {
            $this->actingAs($this->kampalaInstructor)
                ->put(route('instructor.courses.module-access.settings', $this->course), ['target' => $target, 'mode' => 'release'])
                ->assertForbidden();
        }

        $this->assertSame(0, CourseCohortLearningSetting::count());
    }

    public function test_instructor_with_several_branches_sees_every_participant_and_group(): void
    {
        $this->kampalaInstructor->branches()->attach(Branch::create(['name' => 'Mbale', 'code' => 'MBL', 'is_active' => true])->id);

        $participants = $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.manage', [$this->course, 'tab' => 'participants']))
            ->assertOk()
            ->viewData('participants');
        $this->assertCount(2, $participants);

        $this->actingAs($this->kampalaInstructor)
            ->get(route('instructor.courses.module-access', $this->course))
            ->assertOk()
            ->assertSee('Gulu Jan')
            ->assertSee('All groups (2)');

        $this->actingAs($this->kampalaInstructor)
            ->put(route('instructor.courses.module-access.settings', $this->course), ['target' => 'all', 'mode' => 'sequential'])
            ->assertRedirect();
        $this->assertSame(2, CourseCohortLearningSetting::count());
    }

    public function test_staff_not_teaching_the_course_cannot_open_it(): void
    {
        $this->actingAs($this->instructor())
            ->get(route('instructor.courses.module-access', $this->course))
            ->assertForbidden();
    }
}
