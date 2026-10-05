<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The table and column this reconciliation touches.
     */
    private const TABLE = 'leave_requests';

    private const COLUMN = 'status';

    /**
     * Canonical enum values shared by the pending stage.
     *
     * @var array<int, string>
     */
    private const CANONICAL_STATUSES = [
        'draft',
        'pending',
        'submitted',
        'supervisor_approved',
        'hr_approved',
        'rejected',
        'cancelled',
    ];

    /**
     * The original enum values, used to reverse the change.
     *
     * @var array<int, string>
     */
    private const ORIGINAL_STATUSES = [
        'draft',
        'submitted',
        'supervisor_approved',
        'hr_approved',
        'rejected',
        'cancelled',
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        // Widen the enum so 'pending' is a first-class value and make it the default.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->enum(self::COLUMN, self::CANONICAL_STATUSES)
                ->default('pending')
                ->change();
        });

        // Reconcile rows written by the legacy 'submitted' path.
        DB::table(self::TABLE)
            ->where(self::COLUMN, 'submitted')
            ->update([self::COLUMN => 'pending']);
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE) || ! Schema::hasColumn(self::TABLE, self::COLUMN)) {
            return;
        }

        // Collapse the canonical pending value back onto the original value.
        DB::table(self::TABLE)
            ->where(self::COLUMN, 'pending')
            ->update([self::COLUMN => 'submitted']);

        // Restore the original 6-value enum and its default.
        Schema::table(self::TABLE, function (Blueprint $table) {
            $table->enum(self::COLUMN, self::ORIGINAL_STATUSES)
                ->default('submitted')
                ->change();
        });
    }
};
