<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class Phase26ReconcilePermissions extends Command
{
    protected $signature = 'phase26:reconcile-permissions {--apply : Apply the recommended role-permission assignments}';
    protected $description = 'Audit/apply Phase 26 Course Call, Survey and Platform Settings permissions';

    private const ROLE_PERMISSIONS = [
        'administrator' => [
            'course_calls.view',
            'course_calls.manage',
            'applications.review',
            'entry_assessments.review',
            'surveys.view',
            'surveys.manage',
            'survey_responses.view',
            'survey_responses.export',
            'settings.branding',
            'settings.backups',
            'settings.maintenance',
        ],

        'program-manager' => [
            'course_calls.view',
            'course_calls.manage',
            'applications.review',
            'entry_assessments.review',
        ],

        'programs-lead' => [
            'course_calls.view',
            'course_calls.manage',
            'applications.review',
            'entry_assessments.review',
        ],

        'program-officer' => [
            'course_calls.view',
            'applications.review',
            'entry_assessments.review',
        ],

        'instructor' => [
            'course_calls.view',
            'applications.review',
            'entry_assessments.review',
        ],

        'meal-lead' => [
            'surveys.view',
            'surveys.manage',
            'survey_responses.view',
            'survey_responses.export',
        ],

        'me-officer' => [
            'surveys.view',
            'surveys.manage',
            'survey_responses.view',
            'survey_responses.export',
        ],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info(
            $apply
                ? 'Phase 26 permission reconciliation: APPLY mode'
                : 'Phase 26 permission reconciliation: DRY-RUN mode'
        );

        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissionSlugs) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if (! $role) {
                $this->warn("Role not found: {$roleSlug}");
                continue;
            }

            $permissions = Permission::query()
                ->whereIn('slug', $permissionSlugs)
                ->get();

            $found = $permissions->pluck('slug')->all();
            $missingDefinitions = array_values(array_diff($permissionSlugs, $found));

            if ($missingDefinitions) {
                $this->warn(
                    "{$role->name}: missing permission definition(s): "
                    .implode(', ', $missingDefinitions)
                );
            }

            $current = $role->permissions()
                ->whereIn('permissions.slug', $permissionSlugs)
                ->pluck('permissions.slug')
                ->all();

            $toAdd = array_values(array_diff($found, $current));

            if (! $toAdd) {
                $this->line("{$role->name}: already reconciled.");
                continue;
            }

            $this->warn(
                "{$role->name}: add ".implode(', ', $toAdd)
            );

            if ($apply) {
                $ids = Permission::query()
                    ->whereIn('slug', $toAdd)
                    ->pluck('id')
                    ->all();

                $role->permissions()->syncWithoutDetaching($ids);
            }
        }

        if (! $apply) {
            $this->newLine();
            $this->comment(
                'Dry run only. Re-run with --apply after reviewing the assignments.'
            );
        }

        return self::SUCCESS;
    }
}
