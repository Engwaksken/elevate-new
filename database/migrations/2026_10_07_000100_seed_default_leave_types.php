<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Staff couldn't request leave because no leave types existed (there is no
 * seeder or HR screen for them). Adds a standard set — Uganda Employment
 * Act defaults — only when the table is empty, so types HR already set up
 * are never changed.
 */
return new class extends Migration
{
    private const TYPES = [
        ['name' => 'Annual Leave', 'code' => 'ANNUAL', 'default_days' => 21, 'requires_attachment' => false, 'is_paid' => true],
        ['name' => 'Sick Leave', 'code' => 'SICK', 'default_days' => 30, 'requires_attachment' => true, 'is_paid' => true],
        ['name' => 'Maternity Leave', 'code' => 'MATERNITY', 'default_days' => 60, 'requires_attachment' => true, 'is_paid' => true],
        ['name' => 'Paternity Leave', 'code' => 'PATERNITY', 'default_days' => 4, 'requires_attachment' => false, 'is_paid' => true],
        ['name' => 'Compassionate Leave', 'code' => 'COMPASSIONATE', 'default_days' => 5, 'requires_attachment' => false, 'is_paid' => true],
        ['name' => 'Study Leave', 'code' => 'STUDY', 'default_days' => 10, 'requires_attachment' => true, 'is_paid' => true],
        ['name' => 'Unpaid Leave', 'code' => 'UNPAID', 'default_days' => null, 'requires_attachment' => false, 'is_paid' => false],
    ];

    public function up(): void
    {
        if (DB::table('leave_types')->exists()) {
            return;
        }

        $now = now();
        DB::table('leave_types')->insert(array_map(
            fn (array $type) => $type + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            self::TYPES
        ));
    }

    public function down(): void
    {
        // Only remove the defaults that no leave request uses.
        $codes = array_column(self::TYPES, 'code');
        $used = DB::table('leave_requests')->distinct()->pluck('leave_type_id');
        DB::table('leave_types')->whereIn('code', $codes)->whereNotIn('id', $used)->delete();
    }
};
