<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contract KPIs: what a staff member commits to for the length of an employment contract.
        if (! Schema::hasTable('staff_kpis')) {
            Schema::create('staff_kpis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employment_contract_id')->nullable()->constrained()->nullOnDelete();
                $table->string('kra');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('measurement_method')->nullable();
                $table->string('target')->nullable();
                $table->string('unit', 100)->nullable();
                $table->decimal('weight', 5, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_comment')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->index(['employee_id', 'employment_contract_id', 'status']);
            });
        }

        // Quarterly appraisal KPIs and tasks point back at the contract KPI they come from.
        Schema::table('appraisal_kpis', function (Blueprint $table) {
            if (! Schema::hasColumn('appraisal_kpis', 'staff_kpi_id')) {
                $table->foreignId('staff_kpi_id')->nullable()->after('appraisal_kra_id')->constrained('staff_kpis')->nullOnDelete();
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'staff_kpi_id')) {
                $table->foreignId('staff_kpi_id')->nullable()->after('appraisal_kpi_id')->constrained('staff_kpis')->nullOnDelete();
            }
        });

        // Allow quarterly appraisal cycles.
        Schema::table('appraisal_cycles', function (Blueprint $table) {
            $table->string('cycle_type', 30)->default('annual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tasks', fn (Blueprint $table) => $table->dropConstrainedForeignId('staff_kpi_id'));
        Schema::table('appraisal_kpis', fn (Blueprint $table) => $table->dropConstrainedForeignId('staff_kpi_id'));
        Schema::dropIfExists('staff_kpis');
    }
};
