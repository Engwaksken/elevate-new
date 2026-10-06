<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Procurement Admin is now limited to people with procurement permissions,
 * but the Procurement Officer and Finance roles had none. Grant them the
 * permissions their work needs (only adds missing links; never removes).
 */
return new class extends Migration
{
    private const GRANTS = [
        'procurement-officer' => ['procurement.view', 'procurement.create', 'procurement.approve', 'procurement.receive'],
        'finance' => ['procurement.view', 'procurement.approve'],
    ];

    public function up(): void
    {
        foreach (self::GRANTS as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            foreach (DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id') as $permissionId) {
                DB::table('permission_role')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::GRANTS as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            DB::table('permission_role')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', DB::table('permissions')->whereIn('slug', $permissionSlugs)->pluck('id'))
                ->delete();
        }
    }
};
