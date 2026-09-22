<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('code')->nullable()->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->enum('delivery_mode', ['online','in_person','blended'])->default('blended');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->decimal('pass_mark', 5, 2)->default(50);
            $table->boolean('self_enrolment_enabled')->default(false);
            $table->enum('status', ['draft','published','archived'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('course_instructors', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->timestamps();
            $table->primary(['course_id','user_id']);
        });

        Schema::create('course_cohort', function (Blueprint $table) {
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['course_id','cohort_id']);
        });

        Schema::create('course_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->enum('content_type', ['text','video','file','link','mixed'])->default('text');
            $table->string('video_url')->nullable();
            $table->string('external_url')->nullable();
            $table->string('file_path')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', ['enrolled','in_progress','completed','withdrawn','failed'])->default('enrolled');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->decimal('final_score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['course_id','user_id']);
            $table->index(['course_id','status']);
        });

        Schema::create('lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamps();

            $table->unique(['lesson_id','user_id']);
        });

        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_module_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->enum('type', ['quiz','assignment','exam'])->default('quiz');
            $table->text('instructions')->nullable();
            $table->decimal('pass_mark', 5, 2)->default(50);
            $table->unsignedInteger('max_attempts')->default(1);
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        Schema::create('assessment_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->enum('question_type', ['multiple_choice','true_false','short_text','long_text'])->default('multiple_choice');
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->json('correct_answer')->nullable();
            $table->decimal('marks', 6, 2)->default(1);
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
        });

        Schema::create('assessment_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->enum('status', ['started','submitted','graded'])->default('started');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessment_id','user_id','attempt_number']);
        });

        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessment_question_id')->constrained()->cascadeOnDelete();
            $table->longText('answer_text')->nullable();
            $table->json('answer_json')->nullable();
            $table->decimal('awarded_marks', 6, 2)->nullable();
            $table->text('grader_feedback')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->date('session_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('venue')->nullable();
            $table->timestamps();
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['present','absent','late','excused'])->default('present');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id','user_id']);
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('certificate_number')->unique();
            $table->date('issued_on');
            $table->string('pdf_path')->nullable();
            $table->string('verification_token')->unique();
            $table->timestamps();

            $table->unique(['course_id','user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('assessment_answers');
        Schema::dropIfExists('assessment_attempts');
        Schema::dropIfExists('assessment_questions');
        Schema::dropIfExists('assessments');
        Schema::dropIfExists('lesson_progress');
        Schema::dropIfExists('enrolments');
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('course_modules');
        Schema::dropIfExists('course_cohort');
        Schema::dropIfExists('course_instructors');
        Schema::dropIfExists('courses');
    }
};
