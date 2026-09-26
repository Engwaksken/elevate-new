<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                if (!Schema::hasColumn('appraisals','completion_percent')) {
                    $table->decimal('completion_percent',5,2)->default(0);
                }

                if (!Schema::hasColumn('appraisals','performance_percent')) {
                    $table->decimal('performance_percent',5,2)->nullable();
                }

                if (!Schema::hasColumn('appraisals','employee_submitted_at')) {
                    $table->timestamp('employee_submitted_at')->nullable();
                }

                if (!Schema::hasColumn('appraisals','manager_submitted_at')) {
                    $table->timestamp('manager_submitted_at')->nullable();
                }

                if (!Schema::hasColumn('appraisals','manager_acknowledged_at')) {
                    $table->timestamp('manager_acknowledged_at')->nullable();
                }

                if (!Schema::hasColumn('appraisals','manager_acknowledgement_name')) {
                    $table->string('manager_acknowledgement_name')->nullable();
                }

                if (!Schema::hasColumn('appraisals','employee_acknowledgement_name')) {
                    $table->string('employee_acknowledgement_name')->nullable();
                }

                if (!Schema::hasColumn('appraisals','hr_finalised_at')) {
                    $table->timestamp('hr_finalised_at')->nullable();
                }

                if (!Schema::hasColumn('appraisals','hr_finalised_by')) {
                    $table->foreignId('hr_finalised_by')->nullable()
                        ->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('appraisal_kpi_scores')) {
            Schema::table('appraisal_kpi_scores', function (Blueprint $table) {
                if (!Schema::hasColumn('appraisal_kpi_scores','evidence_note')) {
                    $table->text('evidence_note')->nullable();
                }

                if (!Schema::hasColumn('appraisal_kpi_scores','evidence_url')) {
                    $table->string('evidence_url',1000)->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('appraisal_kpi_scores')) {
            Schema::table('appraisal_kpi_scores', function (Blueprint $table) {
                foreach (['evidence_url','evidence_note'] as $column) {
                    if (Schema::hasColumn('appraisal_kpi_scores',$column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('appraisals')) {
            Schema::table('appraisals', function (Blueprint $table) {
                if (Schema::hasColumn('appraisals','hr_finalised_by')) {
                    $table->dropConstrainedForeignId('hr_finalised_by');
                }

                foreach ([
                    'hr_finalised_at',
                    'employee_acknowledgement_name',
                    'manager_acknowledgement_name',
                    'manager_acknowledged_at',
                    'manager_submitted_at',
                    'employee_submitted_at',
                    'performance_percent',
                    'completion_percent',
                ] as $column) {
                    if (Schema::hasColumn('appraisals',$column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
