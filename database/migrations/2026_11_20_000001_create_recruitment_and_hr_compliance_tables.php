<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HR operations:
 *  - Recruitment: vacancies posted by HR/admins (public + embeddable on other
 *    websites), external applicants, AI screening against job criteria,
 *    interview questions, interviews with staff panels and per-question scores.
 *  - Compliance: organisation policies shared with staff (with acknowledgement)
 *    and required staff documents (ID, certificates...) with reminders.
 *
 * Kept separate from the participant job board (jobs / job_applications).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('recruitment_vacancies')) {
            Schema::create('recruitment_vacancies', function (Blueprint $table) {
                $table->id();
                $table->string('title', 190);
                $table->string('slug', 190)->unique();
                $table->string('reference', 40)->nullable();
                $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
                $table->string('location', 190)->nullable();
                $table->string('employment_type', 60)->nullable();
                $table->string('salary_range', 120)->nullable();
                $table->unsignedSmallInteger('positions')->default(1);
                $table->text('summary')->nullable();
                $table->longText('description')->nullable();
                $table->longText('responsibilities')->nullable();
                $table->longText('requirements')->nullable();
                $table->string('min_education', 60)->nullable();
                $table->unsignedTinyInteger('min_experience_years')->nullable();
                $table->json('skills')->nullable();
                $table->json('criteria')->nullable(); // [{label, weight, must_have}]
                $table->json('application_questions')->nullable(); // extra questions shown on the form
                $table->date('closing_date')->nullable();
                $table->string('status', 20)->default('draft'); // draft | open | closed | filled
                $table->boolean('cv_required')->default(true);
                $table->timestamp('published_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['status', 'closing_date']);
            });
        }

        if (! Schema::hasTable('recruitment_applicants')) {
            Schema::create('recruitment_applicants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vacancy_id')->constrained('recruitment_vacancies')->cascadeOnDelete();
                $table->string('reference', 40)->unique();
                $table->string('name', 190);
                $table->string('email', 190);
                $table->string('phone', 60)->nullable();
                $table->string('location', 190)->nullable();
                $table->string('education_level', 60)->nullable();
                $table->unsignedTinyInteger('years_experience')->nullable();
                $table->string('linkedin_url', 300)->nullable();
                $table->text('cover_letter')->nullable();
                $table->json('answers')->nullable();
                $table->string('cv_path', 500)->nullable();
                $table->string('cv_original_name', 255)->nullable();
                $table->longText('cv_text')->nullable();
                $table->string('source', 30)->default('careers'); // careers | embed | manual
                $table->string('source_site', 255)->nullable();
                $table->string('status', 20)->default('new'); // new | screened | shortlisted | interview | offered | hired | rejected | withdrawn
                $table->decimal('ai_score', 5, 2)->nullable();
                $table->string('ai_recommendation', 30)->nullable(); // strong | good | possible | weak
                $table->text('ai_summary')->nullable();
                $table->json('ai_breakdown')->nullable(); // {criteria:[{label,score,note}], strengths:[], gaps:[]}
                $table->string('ai_method', 20)->nullable(); // ai | rules
                $table->timestamp('ai_screened_at')->nullable();
                $table->text('hr_notes')->nullable();
                $table->foreignId('shortlisted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('shortlisted_at')->nullable();
                $table->timestamps();
                $table->unique(['vacancy_id', 'email']);
                $table->index(['vacancy_id', 'status']);
            });
        }

        if (! Schema::hasTable('recruitment_interview_questions')) {
            Schema::create('recruitment_interview_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vacancy_id')->constrained('recruitment_vacancies')->cascadeOnDelete();
                $table->text('question');
                $table->text('guidance')->nullable(); // what a good answer looks like
                $table->unsignedTinyInteger('max_score')->default(5);
                $table->unsignedSmallInteger('weight')->default(1);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('recruitment_interviews')) {
            Schema::create('recruitment_interviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('applicant_id')->constrained('recruitment_applicants')->cascadeOnDelete();
                $table->foreignId('vacancy_id')->constrained('recruitment_vacancies')->cascadeOnDelete();
                $table->dateTime('scheduled_at');
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->string('mode', 20)->default('in_person'); // in_person | online | phone
                $table->string('location', 255)->nullable();
                $table->string('meeting_link', 500)->nullable();
                $table->text('candidate_message')->nullable();
                $table->string('status', 20)->default('scheduled'); // scheduled | completed | cancelled
                $table->decimal('average_score', 5, 2)->nullable(); // percentage across submitted panel scores
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('candidate_notified_at')->nullable();
                $table->timestamps();
                $table->index(['vacancy_id', 'scheduled_at']);
            });
        }

        if (! Schema::hasTable('recruitment_interview_panelists')) {
            Schema::create('recruitment_interview_panelists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('interview_id')->constrained('recruitment_interviews')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_chair')->default(false);
                $table->string('recommendation', 20)->nullable(); // hire | maybe | no_hire
                $table->text('overall_comment')->nullable();
                $table->decimal('total_score', 5, 2)->nullable(); // percentage
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
                $table->unique(['interview_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('recruitment_interview_scores')) {
            Schema::create('recruitment_interview_scores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('panelist_id')->constrained('recruitment_interview_panelists')->cascadeOnDelete();
                $table->foreignId('question_id')->constrained('recruitment_interview_questions')->cascadeOnDelete();
                $table->unsignedTinyInteger('score')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
                $table->unique(['panelist_id', 'question_id']);
            });
        }

        if (! Schema::hasTable('hr_policies')) {
            Schema::create('hr_policies', function (Blueprint $table) {
                $table->id();
                $table->string('title', 190);
                $table->string('category', 80)->nullable();
                $table->string('version', 30)->nullable();
                $table->text('summary')->nullable();
                $table->longText('body')->nullable();
                $table->string('file_path', 500)->nullable();
                $table->string('file_name', 255)->nullable();
                $table->date('effective_date')->nullable();
                $table->boolean('requires_acknowledgement')->default(true);
                $table->boolean('assign_to_new_staff')->default(true);
                $table->unsignedSmallInteger('due_days')->default(7);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hr_policy_assignments')) {
            Schema::create('hr_policy_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('policy_id')->constrained('hr_policies')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->date('due_date')->nullable();
                $table->timestamp('viewed_at')->nullable();
                $table->timestamp('acknowledged_at')->nullable();
                $table->unsignedSmallInteger('reminders_sent')->default(0);
                $table->timestamp('last_reminded_at')->nullable();
                $table->timestamps();
                $table->unique(['policy_id', 'user_id']);
                $table->index(['user_id', 'acknowledged_at']);
            });
        }

        if (! Schema::hasTable('hr_document_requirements')) {
            Schema::create('hr_document_requirements', function (Blueprint $table) {
                $table->id();
                $table->string('name', 190);
                $table->text('description')->nullable();
                $table->boolean('is_mandatory')->default(true);
                $table->boolean('is_active')->default(true);
                $table->unsignedSmallInteger('due_days')->default(14);
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('staff_documents')) {
            Schema::create('staff_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('requirement_id')->nullable()->constrained('hr_document_requirements')->nullOnDelete();
                $table->string('title', 190);
                $table->string('path', 500);
                $table->string('original_name', 255);
                $table->string('mime_type', 150)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->string('status', 20)->default('submitted'); // submitted | approved | rejected
                $table->text('review_note')->nullable();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'requirement_id']);
            });
        }

        if (! Schema::hasTable('hr_document_reminders')) {
            // One row per staff member: when they were last chased for missing documents.
            Schema::create('hr_document_reminders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedSmallInteger('reminders_sent')->default(0);
                $table->timestamp('last_reminded_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_document_reminders');
        Schema::dropIfExists('staff_documents');
        Schema::dropIfExists('hr_document_requirements');
        Schema::dropIfExists('hr_policy_assignments');
        Schema::dropIfExists('hr_policies');
        Schema::dropIfExists('recruitment_interview_scores');
        Schema::dropIfExists('recruitment_interview_panelists');
        Schema::dropIfExists('recruitment_interviews');
        Schema::dropIfExists('recruitment_interview_questions');
        Schema::dropIfExists('recruitment_applicants');
        Schema::dropIfExists('recruitment_vacancies');
    }
};
