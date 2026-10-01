<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Module workspaces: one hub page per area with headline counts and
 * links to the module's screens, instead of a long sidebar.
 */
class WorkspaceController extends Controller
{
    public function learning()
    {
        return $this->hub([
            'eyebrow' => 'Workspace',
            'title' => 'Learning',
            'description' => 'Courses, enrolments, assignments, files and certificates in one place.',
            'stats' => [
                ['Courses', 'fa-book-open', 'courses'],
                ['Enrolments', 'fa-user-graduate', 'enrolments'],
                ['Assignments', 'fa-list-check', 'assessments'],
                ['Certificates', 'fa-award', 'certificates'],
            ],
            'links' => [
                ['admin.elearning.courses.index', 'Courses', 'fa-book-open', 'Create and manage courses, modules and lessons.'],
                ['admin.elearning.enrolments.index', 'Enrolments', 'fa-user-graduate', 'Enrol participants and track their progress.'],
                ['admin.elearning.assignments.index', 'Assignments', 'fa-list-check', 'Assessments, submissions and grading.'],
                ['admin.elearning.learning-files.index', 'Learning Files', 'fa-folder-open', 'Shared course files and resources.'],
                ['admin.elearning.certificates.index', 'Certificates', 'fa-award', 'Issued certificates and templates.'],
            ],
        ]);
    }

    public function planningMeal()
    {
        return $this->hub([
            'eyebrow' => 'Workspace',
            'title' => 'Planning & MEAL',
            'description' => 'Workplans, delivery tracking and monitoring, evaluation, accountability and learning.',
            'stats' => [
                ['Workplans', 'fa-diagram-project', 'workplans'],
                ['Tasks', 'fa-list-check', 'tasks'],
                ['Indicators', 'fa-chart-line', 'indicators'],
                ['Surveys', 'fa-square-poll-vertical', 'surveys'],
            ],
            'links' => [
                ['admin.workplans.index', 'Workplans', 'fa-diagram-project', 'Annual and quarterly plans with approvals.'],
                ['admin.tasks.index', 'Tasks', 'fa-list-check', 'Activity tasks and assignments.'],
                ['admin.deliverables.index', 'Deliverables', 'fa-box-open', 'Outputs and due deliverables.'],
                ['admin.indicators.index', 'Indicators', 'fa-chart-line', 'Targets, results and verification.'],
                ['admin.results-framework.index', 'Results Framework', 'fa-sitemap', 'Outcomes and results hierarchy.'],
                ['admin.surveys.index', 'Surveys', 'fa-square-poll-vertical', 'Data collection and evaluation surveys.'],
            ],
        ]);
    }

    public function mentorship()
    {
        return $this->hub([
            'eyebrow' => 'Workspace',
            'title' => 'Mentorship',
            'description' => 'Mentors, mentee matches, sessions and follow-up.',
            'stats' => [
                ['Mentors', 'fa-user-tie', 'mentor_profiles'],
                ['Matches', 'fa-people-arrows', 'mentor_matches'],
                ['Sessions', 'fa-calendar-check', 'mentorship_sessions'],
                ['Goals', 'fa-bullseye', 'mentorship_goals'],
            ],
            'links' => [
                ['admin.mentorship.mentors.index', 'Mentors', 'fa-user-tie', 'Mentor profiles and availability.'],
                ['admin.mentorship.matches.index', 'Matches', 'fa-people-arrows', 'Mentor–mentee pairings.'],
                ['admin.reports.mentorship-tracking', 'Tracking Report', 'fa-chart-column', 'Session attendance and engagement.'],
            ],
        ]);
    }

    public function jobs()
    {
        return $this->hub([
            'eyebrow' => 'Workspace',
            'title' => 'Jobs & Opportunities',
            'description' => 'Job postings, applications and placement tracking.',
            'stats' => [
                ['Jobs', 'fa-briefcase', 'jobs'],
                ['Applications', 'fa-file-signature', 'job_applications'],
                ['Interviews', 'fa-comments', 'job_interviews'],
                ['Offers', 'fa-handshake', 'job_offers'],
            ],
            'links' => [
                ['admin.jobs.index', 'Jobs', 'fa-briefcase', 'Post, review and publish opportunities.'],
                ['admin.reports.jobs-tracking', 'Tracking Report', 'fa-chart-column', 'Applications, interviews and placements.'],
            ],
        ]);
    }

    public function reports()
    {
        return $this->hub([
            'eyebrow' => 'Workspace',
            'title' => 'Reports',
            'description' => 'Tracking reports and data imports.',
            'stats' => [
                ['Mentorship Sessions', 'fa-calendar-check', 'mentorship_sessions'],
                ['Job Applications', 'fa-file-signature', 'job_applications'],
                ['Tracking Events', 'fa-route', 'job_tracking_events'],
                ['Data Imports', 'fa-file-import', 'data_imports'],
            ],
            'links' => [
                ['admin.reports.mentorship-tracking', 'Mentorship Tracking', 'fa-people-arrows', 'Sessions, attendance and confirmations.'],
                ['admin.reports.jobs-tracking', 'Jobs Tracking', 'fa-briefcase', 'Applications and placement outcomes.'],
                ['admin.import-centre.index', 'Import Centre', 'fa-file-import', 'Upload CSV data using module templates.'],
            ],
        ]);
    }

    private function hub(array $workspace)
    {
        $workspace['stats'] = array_map(fn (array $stat) => [
            'label' => $stat[0],
            'icon' => $stat[1],
            'value' => $this->count($stat[2]),
        ], $workspace['stats']);

        $workspace['links'] = array_values(array_filter(array_map(
            fn (array $link) => Route::has($link[0]) ? [
                'url' => route($link[0]),
                'label' => $link[1],
                'icon' => $link[2],
                'description' => $link[3],
            ] : null,
            $workspace['links']
        )));

        return view('admin.workspaces.hub', compact('workspace'));
    }

    private function count(string $table): ?int
    {
        try {
            return Schema::hasTable($table) ? DB::table($table)->count() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
