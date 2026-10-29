<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every question and answer exchanged with the AI Career Mentor, so participants keep their history.
        Schema::create('career_mentor_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->string('source', 20)->nullable();
            $table->timestamp('saved_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        // Dated next steps suggested in an answer; approved ones are placed on the participant's calendar.
        Schema::create('career_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('career_mentor_message_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('participant_goal_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calendar_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 190);
            $table->date('due_on');
            $table->string('status', 20)->default('suggested');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('career_action_items');
        Schema::dropIfExists('career_mentor_messages');
    }
};
