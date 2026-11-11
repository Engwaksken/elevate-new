<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participant_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16)->default('direct');
            $table->string('title', 120)->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->index(['type', 'updated_at']);
        });

        Schema::create('participant_conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('participant_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['conversation_id', 'user_id']);
            $table->index(['user_id', 'conversation_id']);
        });

        Schema::create('participant_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('participant_conversations')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_messages');
        Schema::dropIfExists('participant_conversation_user');
        Schema::dropIfExists('participant_conversations');
    }
};
