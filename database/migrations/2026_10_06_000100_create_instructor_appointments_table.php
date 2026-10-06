<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participant-requested one-to-one appointments with a course instructor.
 * Status flow: pending -> approved | declined | rescheduled_proposed;
 * rescheduled_proposed -> approved | declined; approved -> completed;
 * any open state -> cancelled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('instructor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('duration_minutes');
            $table->string('mode', 20)->default('online');
            $table->string('location')->nullable();
            $table->string('meeting_url', 500)->nullable();
            $table->string('topic', 150);
            $table->text('details')->nullable();
            $table->string('status', 30)->default('pending');
            $table->dateTime('proposed_starts_at')->nullable();
            $table->text('proposal_note')->nullable();
            $table->text('decision_reason')->nullable();
            $table->dateTime('decided_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['instructor_user_id', 'starts_at']);
            $table->index(['participant_user_id', 'starts_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_appointments');
    }
};
