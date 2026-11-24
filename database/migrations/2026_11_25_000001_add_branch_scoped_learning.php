<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch-level delivery of shared courses:
 * - a cohort can have its own due date for an assessment;
 * - an announcement or a lesson can be limited to one cohort.
 * A null cohort_id keeps today's behaviour (whole course).
 *
 * cohort_id on lessons/announcements is indexed but not a foreign key on purpose:
 * if a cohort is deleted, its group-only items stay hidden instead of becoming
 * visible to the whole course or being deleted with it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assessment_cohort_deadlines')) {
            Schema::create('assessment_cohort_deadlines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
                $table->timestamp('due_at');
                $table->foreignId('set_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['assessment_id', 'cohort_id']);
            });
        }

        foreach (['course_announcements', 'lessons'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'cohort_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('cohort_id')->nullable()->index();
                });
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_cohort_deadlines');

        foreach (['course_announcements', 'lessons'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'cohort_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['cohort_id']);
                    $table->dropColumn('cohort_id');
                });
            }
        }
    }
};
