<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mentor_matches', function (Blueprint $table) {
            if (! Schema::hasColumn('mentor_matches', 'matching_score')) {
                $table->decimal('matching_score', 5, 2)->nullable()->after('matched_by');
            }
            if (! Schema::hasColumn('mentor_matches', 'matching_notes')) {
                $table->text('matching_notes')->nullable()->after('matching_score');
            }
        });

        Schema::create('mentorship_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('remind_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['mentorship_session_id','user_id','remind_at'], 'mentor_reminder_unique');
        });

        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('company_name');
            $table->string('company_type')->nullable();
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_path')->nullable();
            $table->enum('status', ['pending','approved','rejected','suspended'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->nullable();
            $table->string('industry')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->enum('employment_type', ['full_time','part_time','contract','internship','temporary','volunteer'])->nullable();
            $table->enum('work_arrangement', ['onsite','remote','hybrid'])->nullable();
            $table->string('experience_level')->nullable();
            $table->string('education_level')->nullable();
            $table->decimal('salary_min', 15, 2)->nullable();
            $table->decimal('salary_max', 15, 2)->nullable();
            $table->string('salary_currency', 3)->default('UGX');
            $table->longText('description')->nullable();
            $table->longText('responsibilities')->nullable();
            $table->longText('requirements')->nullable();
            $table->json('skills')->nullable();
            $table->date('application_deadline')->nullable();
            $table->unsignedInteger('positions')->default(1);
            $table->enum('status', ['draft','pending_approval','published','closed','expired','rejected'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id','job_id']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained('resumes')->nullOnDelete();
            $table->text('cover_letter')->nullable();
            $table->enum('status', ['submitted','under_review','shortlisted','interview','offer','hired','rejected','withdrawn'])->default('submitted');
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();
            $table->unique(['job_id','user_id']);
        });

        Schema::create('job_application_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at')->useCurrent();
        });

        Schema::create('job_interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('venue')->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled','completed','cancelled','no_show'])->default('scheduled');
            $table->timestamps();
        });

        Schema::create('job_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->decimal('salary_amount', 15, 2)->nullable();
            $table->string('salary_currency', 3)->default('UGX');
            $table->date('start_date')->nullable();
            $table->text('offer_notes')->nullable();
            $table->enum('status', ['draft','sent','accepted','declined','withdrawn'])->default('draft');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('participant_outcomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('outcome_type', [
                'new_self_employment',
                'additional_self_employment',
                'improved_self_employment',
                'new_wage_employment',
                'additional_wage_employment',
                'improved_wage_employment',
                'other'
            ]);
            $table->string('organisation_name')->nullable();
            $table->string('job_title')->nullable();
            $table->date('outcome_date')->nullable();
            $table->decimal('income_amount', 15, 2)->nullable();
            $table->string('income_currency', 3)->default('UGX');
            $table->text('notes')->nullable();
            $table->enum('verification_status', ['draft','submitted','verified','rejected'])->default('draft');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_outcomes');
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('job_interviews');
        Schema::dropIfExists('job_application_status_history');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('employers');
        Schema::dropIfExists('mentorship_reminders');

        Schema::table('mentor_matches', function (Blueprint $table) {
            foreach (['matching_notes','matching_score'] as $column) {
                if (Schema::hasColumn('mentor_matches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
