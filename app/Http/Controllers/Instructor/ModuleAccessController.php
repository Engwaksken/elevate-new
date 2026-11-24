<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\CohortModuleRelease;
use App\Models\Course;
use App\Models\CourseCohortLearningSetting;
use App\Models\CourseModule;
use App\Observers\LearningNotificationObserver;
use App\Services\Learning\InstructorScope;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;

/**
 * Per-group (cohort) control of when course modules open:
 * - open: every published module is available (the default);
 * - sequential: a module opens once the previous one is complete;
 * - release: as sequential, and the instructor also releases each module after the first.
 *
 * Branch instructors manage their own groups; leads and admins manage all groups.
 */
class ModuleAccessController extends Controller
{
    public const MODES = [
        'open' => ['All modules open', 'Participants can open any published module at any time.'],
        'sequential' => ['In order', 'Each module opens when the participant has completed the previous one.'],
        'release' => ['Released by the instructor', 'You release each module for the group. The first module is always open, and participants must also finish the previous module.'],
    ];

    public function __construct(private readonly InstructorScope $scope)
    {
    }

    public function index(Request $request, Course $course)
    {
        $this->authorise($course);

        $user = $request->user();
        $cohorts = $this->scope->cohorts($user, $course);
        $cohortId = (int) $request->get('cohort_id');
        $cohort = $cohorts->firstWhere('id', $cohortId) ?? $cohorts->first();

        $modules = $course->modules()->withCount('lessons')->orderBy('position')->orderBy('id')->get();

        // Looking at the page must not change anything: with no saved rule the group is "open".
        $setting = $cohort
            ? CourseCohortLearningSetting::where('course_id', $course->id)->where('cohort_id', $cohort->id)->first()
            : null;

        $releases = $cohort
            ? CohortModuleRelease::where('cohort_id', $cohort->id)
                ->whereIn('course_module_id', $modules->pluck('id'))
                ->get()->keyBy('course_module_id')
            : collect();

        return view('instructor.module-access', [
            'course' => $course,
            'cohorts' => $cohorts,
            'cohort' => $cohort,
            'mode' => self::modeOf($setting),
            'modes' => self::MODES,
            'modules' => $modules,
            'releases' => $releases,
            'firstPublishedId' => $modules->firstWhere('is_published', true)?->id,
        ]);
    }

    public function settings(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $request->validate([
            'cohort_id' => ['required', 'integer'],
            'mode' => ['required', 'in:'.implode(',', array_keys(self::MODES))],
        ]);
        $this->authoriseCohort($request, $course, (int) $data['cohort_id']);

        CourseCohortLearningSetting::updateOrCreate(
            ['course_id' => $course->id, 'cohort_id' => (int) $data['cohort_id']],
            [
                'sequential_modules' => $data['mode'] !== 'open',
                'instructor_release_required' => $data['mode'] === 'release',
            ]
        );

        return redirect()
            ->route('instructor.courses.module-access', [$course, 'cohort_id' => $data['cohort_id']])
            ->with('success', 'Module access for this group: '.self::MODES[$data['mode']][0].'.');
    }

    public function release(Request $request, Course $course, CourseModule $module, UserNotificationService $notifications)
    {
        $this->authorise($course);
        abort_unless((int) $module->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'cohort_id' => ['required', 'integer'],
            'is_released' => ['required', 'boolean'],
        ]);
        $cohortId = (int) $data['cohort_id'];
        $this->authoriseCohort($request, $course, $cohortId);
        $released = (bool) $data['is_released'];

        $release = CohortModuleRelease::updateOrCreate(
            ['course_module_id' => $module->id, 'cohort_id' => $cohortId],
            [
                'is_released' => $released,
                'released_at' => $released ? now() : null,
                'released_by' => $request->user()->id,
            ]
        );

        // Tell the group when a published module opens for them (not on a repeat click).
        if ($released && $module->is_published && ($release->wasRecentlyCreated || $release->wasChanged('is_released'))) {
            $notifications->sendToManySafely(
                LearningNotificationObserver::learnerIds($course, $cohortId),
                'module_released',
                'Module open: '.$module->title,
                "\"{$module->title}\" is now open in \"{$course->title}\". You can start it once you have finished the previous module.",
                route('learning.course.dashboard', $course),
                ['course_id' => $course->id, 'module_id' => $module->id, 'cohort_id' => $cohortId]
            );
        }

        return redirect()
            ->route('instructor.courses.module-access', [$course, 'cohort_id' => $cohortId])
            ->with('success', '"'.$module->title.'" '.($released ? 'released for this group.' : 'locked for this group.'));
    }

    public static function modeOf(?CourseCohortLearningSetting $setting): string
    {
        if (! $setting || ! $setting->sequential_modules) {
            return 'open';
        }

        return $setting->instructor_release_required ? 'release' : 'sequential';
    }

    /** Same staff rule as the rest of the instructor workspace. */
    private function authorise(Course $course): void
    {
        $user = auth()->user();
        abort_unless($user && $user->isStaff() && $user->isActive(), 403);

        if ($this->scope->isAdmin($user)) {
            return;
        }

        abort_unless(
            $user->hasAnyRole(['instructor', 'trainer'])
            && $user->instructedCourses()->whereKey($course->id)->exists(),
            403
        );
    }

    private function authoriseCohort(Request $request, Course $course, int $cohortId): void
    {
        abort_unless(
            $cohortId > 0 && $this->scope->coversCohort($request->user(), $course, $cohortId),
            403,
            'Choose one of your own groups.'
        );
    }
}
