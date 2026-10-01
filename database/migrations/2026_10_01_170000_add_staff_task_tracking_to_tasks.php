<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'appraisal_kpi_id')) {
                $table->foreignId('appraisal_kpi_id')->nullable()->after('milestone_id')->constrained('appraisal_kpis')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('progress_percent');
            }
            if (! Schema::hasColumn('tasks', 'outcome')) {
                $table->text('outcome')->nullable()->after('completed_at');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index(['assigned_to', 'due_date'], 'tasks_assignee_due_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_assignee_due_index');
            $table->dropConstrainedForeignId('appraisal_kpi_id');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['completed_at', 'outcome']);
        });
    }
};
