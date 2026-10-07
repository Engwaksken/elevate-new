<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionIds = [];

        foreach ([
            ['Manage Roles', 'roles.manage', 'roles'],
            ['Manage Permissions', 'permissions.manage', 'permissions'],
            ['It Support Tickets View', 'it_support_tickets.view', 'it_support_tickets'],
            ['It Support Tickets Manage', 'it_support_tickets.manage', 'it_support_tickets'],
        ] as [$name, $slug, $module]) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'module' => $module,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $permissionIds[] = DB::table('permissions')->where('slug', $slug)->value('id');
        }

        foreach ([
            ['IT Lead', 'it-lead'],
            ['IT Assistant', 'it-assistant'],
        ] as [$name, $slug]) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'is_system' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
            $roleId = DB::table('roles')->where('slug', $slug)->value('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $superAdministratorId = DB::table('roles')
            ->whereIn('slug', ['super-administrator', 'super-admin'])
            ->value('id');

        if ($superAdministratorId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $superAdministratorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $administratorId = DB::table('roles')->where('slug', 'administrator')->value('id');
        if ($administratorId) {
            foreach (DB::table('permissions')->whereIn('slug', ['roles.manage', 'permissions.manage'])->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $administratorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')->whereIn('slug', ['it-lead', 'it-assistant'])->pluck('id');
        $permissionIds = DB::table('permissions')
            ->whereIn('slug', ['it_support_tickets.view', 'it_support_tickets.manage'])
            ->pluck('id');

        DB::table('permission_role')->whereIn('role_id', $roleIds)->whereIn('permission_id', $permissionIds)->delete();
        DB::table('roles')->whereIn('slug', ['it-lead', 'it-assistant'])->delete();
        DB::table('permissions')->whereIn('slug', ['it_support_tickets.view', 'it_support_tickets.manage'])->delete();
    }
};
