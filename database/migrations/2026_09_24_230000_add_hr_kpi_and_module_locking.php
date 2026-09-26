<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('hr_kpi_templates')) {
            Schema::create('hr_kpi_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('source_file')->nullable();
                $table->string('source_sheet')->nullable();
                $table->string('template_type')->default('performance_appraisal');
                $table->string('quarter')->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hr_kpi_template_items')) {
            Schema::create('hr_kpi_template_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hr_kpi_template_id')->constrained()->cascadeOnDelete();
                $table->string('item_type'); // kra, kpi, behavioral, okr
                $table->string('section')->nullable();
                $table->string('title');
                $table->decimal('weight', 8, 2)->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('appraisal_kpi_scores')) {
            Schema::create('appraisal_kpi_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('hr_kpi_template_item_id')->constrained()->cascadeOnDelete();
                $table->decimal('employee_rating', 5, 2)->nullable();
                $table->decimal('manager_rating', 5, 2)->nullable();
                $table->decimal('agreed_rating', 5, 2)->nullable();
                $table->decimal('okr_percent', 6, 2)->nullable();
                $table->text('employee_comment')->nullable();
                $table->text('manager_comment')->nullable();
                $table->timestamps();
                $table->unique(['appraisal_id','hr_kpi_template_item_id'],'appraisal_kpi_unique');
            });
        }


        if (!Schema::hasTable('appraisal_kpi_weekly_updates')) {
            Schema::create('appraisal_kpi_weekly_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('hr_kpi_template_item_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('week_number');
                $table->string('actual_target')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->unique(
                    ['appraisal_id','hr_kpi_template_item_id','week_number'],
                    'appraisal_kpi_week_unique'
                );
            });
        }

        if (Schema::hasTable('appraisals') && !Schema::hasColumn('appraisals','hr_kpi_template_id')) {
            Schema::table('appraisals', function (Blueprint $table) {
                $table->foreignId('hr_kpi_template_id')->nullable()
                    ->after('manager_user_id')
                    ->constrained('hr_kpi_templates')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasTable('course_cohort_learning_settings')) {
            Schema::create('course_cohort_learning_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
                $table->boolean('sequential_modules')->default(true);
                $table->boolean('instructor_release_required')->default(false);
                $table->timestamps();
                $table->unique(['course_id','cohort_id'],'course_cohort_settings_unique');
            });
        }

        if (!Schema::hasTable('cohort_module_releases')) {
            Schema::create('cohort_module_releases', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_module_id')->constrained('course_modules')->cascadeOnDelete();
                $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_released')->default(false);
                $table->timestamp('released_at')->nullable();
                $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['course_module_id','cohort_id'],'cohort_module_release_unique');
            });
        }
    }

    public function down(): void
    {

        if (!Schema::hasTable('appraisal_kpi_weekly_updates')) {
            Schema::create('appraisal_kpi_weekly_updates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
                $table->foreignId('hr_kpi_template_item_id')->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('week_number');
                $table->string('actual_target')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->unique(
                    ['appraisal_id','hr_kpi_template_item_id','week_number'],
                    'appraisal_kpi_week_unique'
                );
            });
        }

        if (Schema::hasTable('appraisals') && Schema::hasColumn('appraisals','hr_kpi_template_id')) {
            Schema::table('appraisals', function (Blueprint $table) {
                $table->dropConstrainedForeignId('hr_kpi_template_id');
            });
        }

        Schema::dropIfExists('cohort_module_releases');
        Schema::dropIfExists('course_cohort_learning_settings');
        Schema::dropIfExists('appraisal_kpi_weekly_updates');
        Schema::dropIfExists('appraisal_kpi_scores');
        Schema::dropIfExists('hr_kpi_template_items');
        Schema::dropIfExists('hr_kpi_templates');
    }
};
