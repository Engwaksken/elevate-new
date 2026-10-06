<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * There was no screen to add departments, so the list was empty and staff
 * typed department names by hand. Only when the departments table is still
 * empty: create one department per distinct name already typed on purchase
 * requests, or — if there are none — a small default set.
 */
return new class extends Migration
{
    /** Tables that store a department name as text. */
    private const SOURCE_TABLES = ['purchase_requests'];

    private const DEFAULTS = [
        ['name' => 'Programmes', 'code' => 'PROG'],
        ['name' => 'Finance & Administration', 'code' => 'FIN'],
        ['name' => 'Human Resources', 'code' => 'HR'],
        ['name' => 'IT', 'code' => 'IT'],
        ['name' => 'Monitoring & Evaluation', 'code' => 'ME'],
        ['name' => 'Communications', 'code' => 'COMMS'],
    ];

    public function up(): void
    {
        if (DB::table('departments')->exists()) {
            return;
        }

        $rows = [];
        foreach (self::SOURCE_TABLES as $table) {
            if (! Schema::hasColumn($table, 'department')) {
                continue;
            }
            foreach (DB::table($table)->whereNotNull('department')->distinct()->pluck('department') as $value) {
                $value = trim((string) $value);
                if ($value !== '') {
                    $rows[mb_strtolower($value)] ??= ['name' => mb_substr($value, 0, 190), 'code' => null];
                }
            }
        }

        if ($rows === []) {
            $rows = self::DEFAULTS;
        }

        $now = now();
        DB::table('departments')->insert(array_map(
            fn (array $row) => $row + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            array_values($rows)
        ));
    }

    public function down(): void
    {
        // Remove only the defaults that nothing references yet.
        $used = DB::table('employees')->whereNotNull('department_id')->pluck('department_id')
            ->merge(DB::table('positions')->whereNotNull('department_id')->pluck('department_id'));

        DB::table('departments')
            ->whereIn('code', array_column(self::DEFAULTS, 'code'))
            ->whereNotIn('id', $used)
            ->delete();
    }
};
