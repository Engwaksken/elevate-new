<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Cohort;
use App\Models\CohortModuleRelease;
use App\Models\Course;
use App\Models\CourseCohortLearningSetting;
use App\Models\CourseModule;
use App\Observers\LearningNotificationObserver;
use App\Services\Learning\InstructorScope;
use App\Services\UserNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Per-group (cohort) control of when course modules open:
 * - open: every published module is available (the default);
 * - sequential: a module opens once the previous one is complete;
 * - release: as sequential, and the instructor also releases each module after the first.
 *
 * Settings and releases apply to a target: one group, every group of one branch
 * ("branch:ID") or all of the instructor's groups ("all"). Branch instructors
 * manage their own groups; leads, admins and multi-branch instructors manage all groups.
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

        $cohorts = $this->scope->cohorts($request->user(), $course)->load(['branch', 'branches']);
        $targets = $this->targets($request, $course, $cohorts);
        $target = $targets->get($this->requestedTarget($request)) ?? $targets->first();
        $groups = $target['cohorts'] ?? collect();
        $groupIds = $groups->pluck('id')->all();

        $modules = $course->modules()->withCount('lessons')->orderBy('position')->orderBy('id')->get();

        // Looking at the page must not change anything: with no saved rule a group is "open".
        $settings = CourseCohortLearningSetting::where('course_id', $course->id)
            ->whereIn('cohort_id', $groupIds ?: [0])->get()->keyBy('cohort_id');
        $modesInTarget = $groups->map(fn (Cohort $c) => self::modeOf($settings->get($c->id)))->unique()->values();

        // Per module: how many groups of the target have it released.
        $releasedCounts = CohortModuleRelease::whereIn('cohort_id', $groupIds ?: [0])
            ->whereIn('course_module_id', $modules->pluck('id'))
            ->where('is_released', true)
            ->get()
            ->countBy('course_module_id');

        return view('instructor.module-access', [
            'course' => $course,
            'cohorts' => $cohorts,
            'targets' => $targets,
            'target' => $target,
            'groupCount' => $groups->count(),
            'mode' => $modesInTarget->count() === 1 ? $modesInTarget->first() : null,
            'modeSummary' => $groups->groupBy(fn (Cohort $c) => self::modeOf($settings->get($c->id)))
                ->map(fn ($list, $mode) => self::MODES[$mode][0].': '.$list->pluck('name')->join(', '))
                ->values(),
            'modes' => self::MODES,
            'modules' => $modules,
            'releasedCounts' => $releasedCounts,
            'firstPublishedId' => $modules->firstWhere('is_published', true)?->id,
        ]);
    }

    public function settings(Request $request, Course $course)
    {
        $this->authorise($course);

        $data = $request->validate([
            'target' => ['nullable', 'string', 'max:40'],
            'cohort_id' => ['nullable', 'integer'],
            'mode' => ['required', 'in:'.implode(',', array_keys(self::MODES))],
        ]);
        $target = $this->resolveTarget($request, $course);

        foreach ($target['cohorts'] as $cohort) {
            CourseCohortLearningSetting::updateOrCreate(
                ['course_id' => $course->id, 'cohort_id' => $cohort->id],
                [
                    'sequential_modules' => $data['mode'] !== 'open',
                    'instructor_release_required' => $data['mode'] === 'release',
                ]
            );
        }

        return redirect()
            ->route('instructor.courses.module-access', [$course, 'target' => $target['key']])
            ->with('success', 'Module access for '.$target['label'].': '.self::MODES[$data['mode']][0].'.');
    }

    public function release(Request $request, Course $course, CourseModule $module, UserNotificationService $notifications)
    {
        $this->authorise($course);
        abort_unless((int) $module->course_id === (int) $course->id, 404);

        $data = $request->validate([
            'target' => ['nullable', 'string', 'max:40'],
            'cohort_id' => ['nullable', 'integer'],
            'is_released' => ['required', 'boolean'],
        ]);
        $target = $this->resolveTarget($request, $course);
        $released = (bool) $data['is_released'];

        foreach ($target['cohorts'] as $cohort) {
            $release = CohortModuleRelease::updateOrCreate(
                ['course_module_id' => $module->id, 'cohort_id' => $cohort->id],
                [
                    'is_released' => $released,
                    'released_at' => $released ? now() : null,
                    'released_by' => $request->user()->id,
                ]
            );

            // Tell the group when a published module opens for them (not on a repeat click).
            if ($released && $module->is_published && ($release->wasRecentlyCreated || $release->wasChanged('is_released'))) {
                $notifications->sendToManySafely(
                    LearningNotificationObserver::learnerIds($course, $cohort->id),
                    'module_released',
                    'Module open: '.$module->title,
                    "\"{$module->title}\" is now open in \"{$course->title}\". You can start it once you have finished the previous module.",
                    route('learning.course.dashboard', $course),
                    ['course_id' => $course->id, 'module_id' => $module->id, 'cohort_id' => $cohort->id]
                );
            }
        }

        return redirect()
            ->route('instructor.courses.module-access', [$course, 'target' => $target['key']])
            ->with('success', '"'.$module->title.'" '.($released ? 'released' : 'locked').' for '.$target['label'].'.');
    }

    public static function modeOf(?CourseCohortLearningSetting $setting): string
    {
        if (! $setting || ! $setting->sequential_modules) {
            return 'open';
        }

        return $setting->instructor_release_required ? 'release' : 'sequential';
    }

    /**
     * What the instructor can apply settings to: all their groups, each branch
     * (every group running there) and each single group.
     *
     * @return Collection<string, array{key:string,label:string,kind:string,cohorts:Collection}>
     */
    private function targets(Request $request, Course $course, Collection $cohorts): Collection
    {
        $targets = collect();
        if ($cohorts->isEmpty()) {
            return $targets;
        }

        if ($cohorts->count() > 1) {
            $targets->put('all', ['key' => 'all', 'label' => 'all groups', 'kind' => 'all', 'cohorts' => $cohorts]);
        }

        // A branch instructor only sees their own branches here.
        $user = $request->user();
        $ownBranchIds = $this->scope->seesWholeCourse($user, $course)
            ? null
            : $user->branches()->pluck('branches.id')->map(fn ($id) => (int) $id)->all();

        $byBranch = [];
        foreach ($cohorts as $cohort) {
            $branches = collect($cohort->branches->all())->push($cohort->branch)->filter()->unique('id');
            foreach ($branches as $branch) {
                if ($ownBranchIds !== null && ! in_array((int) $branch->id, $ownBranchIds, true)) {
                    continue;
                }
                $byBranch[$branch->id] ??= ['branch' => $branch, 'cohorts' => collect()];
                $byBranch[$branch->id]['cohorts']->push($cohort);
            }
        }

        foreach (collect($byBranch)->sortBy(fn ($row) => $row['branch']->name) as $id => $row) {
            $targets->put('branch:'.$id, [
                'key' => 'branch:'.$id,
                'label' => $row['branch']->name.' branch',
                'kind' => 'branch',
                'cohorts' => $row['cohorts']->values(),
            ]);
        }

        foreach ($cohorts as $cohort) {
            $targets->put('cohort:'.$cohort->id, [
                'key' => 'cohort:'.$cohort->id,
                'label' => $cohort->name,
                'kind' => 'cohort',
                'cohorts' => collect([$cohort]),
            ]);
        }

        return $targets;
    }

    /** "target" from the request, or the older "cohort_id" parameter. */
    private function requestedTarget(Request $request): ?string
    {
        if ($request->filled('target')) {
            return (string) $request->input('target');
        }

        return $request->filled('cohort_id') ? 'cohort:'.(int) $request->input('cohort_id') : null;
    }

    /** The target being changed; only targets made of the user's own groups exist. */
    private function resolveTarget(Request $request, Course $course): array
    {
        $cohorts = $this->scope->cohorts($request->user(), $course)->load(['branch', 'branches']);
        $target = $this->targets($request, $course, $cohorts)->get((string) $this->requestedTarget($request));

        abort_unless($target && $target['cohorts']->isNotEmpty(), 403, 'Choose one of your own groups.');

        return $target;
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
}
