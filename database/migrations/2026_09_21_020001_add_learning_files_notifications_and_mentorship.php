<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id','read_at']);
        });

        Schema::create('mentor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('organisation')->nullable();
            $table->string('job_title')->nullable();
            $table->string('industry')->nullable();
            $table->unsignedInteger('years_experience')->nullable();
            $table->text('professional_bio')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->json('mentoring_areas')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('country')->nullable();
            $table->json('availability')->nullable();
            $table->enum('status', ['pending','approved','inactive','rejected'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('mentee_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->text('career_goals')->nullable();
            $table->json('skills')->nullable();
            $table->json('support_needs')->nullable();
            $table->json('preferred_mentor_areas')->nullable();
            $table->json('availability')->nullable();
            $table->timestamps();
        });

        Schema::create('mentor_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('mentee_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('programme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['pending','active','paused','completed','cancelled'])->default('pending');
            $table->foreignId('matched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mentor_user_id','mentee_user_id','programme_id','cohort_id'], 'mentor_match_unique');
        });

        Schema::create('mentorship_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_match_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('agenda')->nullable();
            $table->dateTime('scheduled_at');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->string('meeting_link')->nullable();
            $table->string('venue')->nullable();
            $table->enum('status', ['scheduled','completed','cancelled','missed'])->default('scheduled');
            $table->boolean('mentor_attended')->nullable();
            $table->boolean('mentee_attended')->nullable();
            $table->text('session_notes')->nullable();
            $table->text('agreed_actions')->nullable();
            $table->dateTime('next_session_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mentorship_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentor_match_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('target_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->enum('status', ['not_started','in_progress','completed','cancelled'])->default('not_started');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_goals');
        Schema::dropIfExists('mentorship_sessions');
        Schema::dropIfExists('mentor_matches');
        Schema::dropIfExists('mentee_profiles');
        Schema::dropIfExists('mentor_profiles');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('learning_files');
    }
};
