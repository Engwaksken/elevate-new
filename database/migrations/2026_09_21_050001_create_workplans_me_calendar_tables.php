<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workplans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('financial_year')->nullable();
            $table->enum('period_type', ['annual','quarterly','monthly','programme','project','department','staff'])->default('annual');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->enum('status', ['draft','submitted','under_review','approved','in_progress','on_hold','completed','cancelled'])->default('draft');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('workplan_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('action', ['submitted','approved','returned','rejected']);
            $table->text('comments')->nullable();
            $table->timestamp('acted_at')->useCurrent();
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplan_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('expected_result')->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->enum('status', ['not_started','in_progress','at_risk','delayed','completed','cancelled'])->default('not_started');
            $table->string('priority')->default('medium');
            $table->text('dependencies')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workplan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_code')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('budget', 15, 2)->nullable();
            $table->string('currency', 3)->default('UGX');
            $table->string('funding_source')->nullable();
            $table->string('priority')->default('medium');
            $table->enum('status', ['planned','not_started','in_progress','delayed','completed','cancelled'])->default('planned');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->text('expected_output')->nullable();
            $table->text('actual_output')->nullable();
            $table->text('challenges')->nullable();
            $table->text('lessons_learned')->nullable();
            $table->text('next_action')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_assignments', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['activity_id','user_id']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('medium');
            $table->enum('status', ['not_started','in_progress','returned_for_revision','completed','overdue'])->default('not_started');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('reminder_at')->nullable();
            $table->timestamp('escalation_at')->nullable();
            $table->timestamps();
        });

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->enum('status', ['not_started','in_progress','returned_for_revision','completed','overdue'])->default('not_started');
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('results_frameworks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('results_framework_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('results')->cascadeOnDelete();
            $table->enum('result_level', ['impact','outcome','output']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('indicators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('result_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->text('definition')->nullable();
            $table->enum('result_level', ['impact','outcome','output','activity'])->default('output');
            $table->enum('indicator_type', ['number','percentage','rate','ratio','currency','binary','text'])->default('number');
            $table->string('unit_of_measure')->nullable();
            $table->decimal('baseline_numeric', 18, 4)->nullable();
            $table->text('baseline_text')->nullable();
            $table->string('frequency')->nullable();
            $table->text('data_source')->nullable();
            $table->text('means_of_verification')->nullable();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('disaggregation')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft','active','inactive','closed'])->default('draft');
            $table->string('calculation_key')->nullable();
            $table->timestamps();
        });

        Schema::create('indicator_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('period_type');
            $table->string('period_label');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('target_numeric', 18, 4)->nullable();
            $table->text('target_text')->nullable();
            $table->timestamps();
        });

        Schema::create('indicator_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_target_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reporting_period')->nullable();
            $table->decimal('actual_numeric', 18, 4)->nullable();
            $table->text('actual_text')->nullable();
            $table->text('data_source')->nullable();
            $table->text('evidence_note')->nullable();
            $table->enum('verification_status', ['draft','submitted','verified','rejected','needs_correction'])->default('draft');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_indicator', function (Blueprint $table) {
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('indicator_id')->constrained()->cascadeOnDelete();
            $table->string('contribution_type')->nullable();
            $table->timestamps();
            $table->primary(['activity_id','indicator_id']);
        });

        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('eventable');
            $table->string('title');
            $table->string('event_type')->index();
            $table->text('description')->nullable();
            $table->string('venue')->nullable();
            $table->string('meeting_link')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('calendar_attendees', function (Blueprint $table) {
            $table->foreignId('calendar_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('response_status')->default('pending');
            $table->timestamps();
            $table->primary(['calendar_event_id','user_id']);
        });

        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('riskable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('medium');
            $table->string('likelihood')->default('medium');
            $table->text('mitigation')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('issueable');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('severity')->default('medium');
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
        Schema::dropIfExists('risks');
        Schema::dropIfExists('calendar_attendees');
        Schema::dropIfExists('calendar_events');
        Schema::dropIfExists('activity_indicator');
        Schema::dropIfExists('indicator_results');
        Schema::dropIfExists('indicator_targets');
        Schema::dropIfExists('indicators');
        Schema::dropIfExists('results');
        Schema::dropIfExists('results_frameworks');
        Schema::dropIfExists('deliverables');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('activity_assignments');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('workplan_approvals');
        Schema::dropIfExists('workplans');
    }
};
