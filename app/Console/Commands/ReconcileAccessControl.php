<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconcileAccessControl extends Command
{
    protected $signature = 'access:reconcile {--apply : Apply safe reconciliation changes}';
    protected $description = 'Audit and reconcile roles, permissions and user-role assignments';

    private const PARTICIPANT_ROLE_SLUGS = [
        'student',
        'alumni',
        'job-seeker',
        'mentor',
        'employer',
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->info($apply
            ? 'Running access-control reconciliation in APPLY mode.'
            : 'Running access-control reconciliation in DRY-RUN mode.'
        );

        $permissions = Permission::pluck('id');
        $superRoles = Role::whereIn('slug', [
            'super-administrator',
            'super-admin',
        ])->get();

        foreach ($superRoles as $role) {
            $missing = $permissions->diff($role->permissions()->pluck('permissions.id'));

            if ($missing->isNotEmpty()) {
                $this->warn(
                    "{$role->name}: missing {$missing->count()} permission(s)."
                );

                if ($apply) {
                    $role->permissions()->syncWithoutDetaching($missing->all());
                    $this->line('  Added missing permissions.');
                }
            } else {
                $this->line("{$role->name}: permission set complete.");
            }
        }

        $roleIds = Role::pluck('id');
        $userIds = User::pluck('id');
        $permissionIds = Permission::pluck('id');

        $orphanRoleUser = DB::table('role_user')
            ->whereNotIn('role_id', $roleIds)
            ->orWhereNotIn('user_id', $userIds)
            ->get();

        $orphanPermissionRole = DB::table('permission_role')
            ->whereNotIn('role_id', $roleIds)
            ->orWhereNotIn('permission_id', $permissionIds)
            ->get();

        $this->line("Orphan role_user rows: {$orphanRoleUser->count()}");
        $this->line("Orphan permission_role rows: {$orphanPermissionRole->count()}");

        if ($apply) {
            foreach ($orphanRoleUser as $row) {
                DB::table('role_user')
                    ->where('role_id', $row->role_id)
                    ->where('user_id', $row->user_id)
                    ->delete();
            }

            foreach ($orphanPermissionRole as $row) {
                DB::table('permission_role')
                    ->where('role_id', $row->role_id)
                    ->where('permission_id', $row->permission_id)
                    ->delete();
            }
        }

        $crossType = [];

        User::with('roles')->orderBy('id')->chunkById(100, function ($users) use (&$crossType, $apply) {
            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    $participantRole = in_array(
                        $role->slug,
                        self::PARTICIPANT_ROLE_SLUGS,
                        true
                    );

                    $invalid = ($user->user_type === 'participant' && ! $participantRole)
                        || ($user->user_type === 'staff' && $participantRole);

                    if (! $invalid) {
                        continue;
                    }

                    $crossType[] = [
                        'user_id' => $user->id,
                        'email' => $user->email,
                        'user_type' => $user->user_type,
                        'role' => $role->slug,
                    ];

                    if ($apply) {
                        $user->roles()->detach($role->id);
                    }
                }
            }
        });

        $this->line('Cross-type role assignments: '.count($crossType));

        foreach ($crossType as $row) {
            $this->warn(
                "  User {$row['user_id']} ({$row['email']}) "
                ."type={$row['user_type']} role={$row['role']}"
            );
        }

        $duplicateSuperRoles = $superRoles->count();

        if ($duplicateSuperRoles > 1) {
            $this->warn(
                'Two Super Administrator role records exist (super-administrator and super-admin). '
                .'This command keeps both for backward compatibility and ensures both have full permissions.'
            );
        }

        if (! $apply) {
            $this->newLine();
            $this->comment(
                'Dry run only. Run "php artisan access:reconcile --apply" to apply the safe fixes listed above.'
            );
        }

        return self::SUCCESS;
    }
}
