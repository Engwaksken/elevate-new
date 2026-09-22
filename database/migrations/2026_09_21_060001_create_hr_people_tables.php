<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->nullable()->unique();
            $table->foreignId('head_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('grade')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_number')->unique();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('supervisor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employment_type')->nullable();
            $table->string('work_location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('probation_end_date')->nullable();
            $table->enum('status', ['active','probation','on_leave','suspended','exiting','exited'])->default('active');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->timestamps();
        });

        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('contract_type')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('gross_salary', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->string('document_path')->nullable();
            $table->enum('status', ['draft','active','expired','terminated'])->default('draft');
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('document_type');
            $table->string('title');
            $table->string('path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->decimal('default_days', 6, 2)->nullable();
            $table->boolean('requires_attachment')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->string('year');
            $table->decimal('opening_balance', 7, 2)->default(0);
            $table->decimal('accrued', 7, 2)->default(0);
            $table->decimal('used', 7, 2)->default(0);
            $table->decimal('adjustments', 7, 2)->default(0);
            $table->decimal('remaining', 7, 2)->default(0);
            $table->timestamps();
            $table->unique(['employee_id','leave_type_id','year']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 7, 2);
            $table->text('reason')->nullable();
            $table->foreignId('handover_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['draft','submitted','supervisor_approved','hr_approved','rejected','cancelled'])->default('submitted');
            $table->foreignId('supervisor_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_approved_at')->nullable();
            $table->foreignId('hr_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('hr_approved_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('cycle_type', ['probation','mid_year','annual','special'])->default('annual');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('self_assessment_due')->nullable();
            $table->date('manager_review_due')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('appraisals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['goal_setting','self_assessment','manager_review','calibration','acknowledgement','completed'])->default('goal_setting');
            $table->decimal('self_score', 5, 2)->nullable();
            $table->decimal('manager_score', 5, 2)->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->text('employee_comments')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('development_plan')->nullable();
            $table->timestamp('employee_acknowledged_at')->nullable();
            $table->timestamps();
            $table->unique(['appraisal_cycle_id','employee_id']);
        });

        Schema::create('appraisal_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appraisal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workplan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('expected_result')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->decimal('self_rating', 5, 2)->nullable();
            $table->decimal('manager_rating', 5, 2)->nullable();
            $table->text('employee_evidence')->nullable();
            $table->text('manager_feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('exit_type', ['resignation','end_of_contract','termination','retirement','transfer','new_organisation','other']);
            $table->date('notice_date')->nullable();
            $table->date('last_working_date');
            $table->text('reason')->nullable();
            $table->string('destination_organisation')->nullable();
            $table->string('new_role')->nullable();
            $table->string('destination_sector')->nullable();
            $table->foreignId('handover_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['initiated','handover','clearance','access_revoke','completed','cancelled'])->default('initiated');
            $table->timestamps();
        });

        Schema::create('handover_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_exit_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();
            $table->string('title');
            $table->text('details')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending','in_progress','completed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('exit_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_exit_id')->constrained()->cascadeOnDelete();
            $table->string('clearance_area');
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending','cleared','blocked'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('cleared_at')->nullable();
            $table->foreignId('cleared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('exit_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_exit_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('reason_for_leaving')->nullable();
            $table->text('what_worked_well')->nullable();
            $table->text('challenges')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('rehire_eligible')->nullable();
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('conducted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exit_interviews');
        Schema::dropIfExists('exit_clearances');
        Schema::dropIfExists('handover_items');
        Schema::dropIfExists('staff_exits');
        Schema::dropIfExists('appraisal_objectives');
        Schema::dropIfExists('appraisals');
        Schema::dropIfExists('appraisal_cycles');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employment_contracts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
