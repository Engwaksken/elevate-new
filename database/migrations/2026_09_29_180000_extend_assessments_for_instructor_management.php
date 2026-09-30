<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class ExtendAssessmentsForInstructorManagement extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('assessments')) {
            return;
        }
        $hasDurationMinutes = Schema::hasColumn('assessments', 'duration_minutes');
        $hasTotalMarks = Schema::hasColumn('assessments', 'total_marks');
        Schema::table('assessments', function (Blueprint $table) use ($hasDurationMinutes, $hasTotalMarks) {
            if (! $hasDurationMinutes) {
                $table->unsignedInteger('duration_minutes')
                    ->nullable()
                    ->after('due_at');
            }
            if (! $hasTotalMarks) {
                $table->decimal('total_marks', 8, 2)
                    ->nullable()
                    ->after('duration_minutes');
            }
        });
    }
    public function down(): void
    {
        if (! Schema::hasTable('assessments')) {
            return;
        }
        $hasDurationMinutes = Schema::hasColumn('assessments', 'duration_minutes');
        $hasTotalMarks = Schema::hasColumn('assessments', 'total_marks');
        Schema::table('assessments', function (Blueprint $table) use ($hasDurationMinutes, $hasTotalMarks) {
            if ($hasTotalMarks) {
                $table->dropColumn('total_marks');
            }
            if ($hasDurationMinutes) {
                $table->dropColumn('duration_minutes');
            }
        });
    }
}