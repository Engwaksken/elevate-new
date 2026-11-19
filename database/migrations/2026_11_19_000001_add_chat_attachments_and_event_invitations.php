<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chat file attachments (staff, instructor and participant chats share the
 * participant_conversations tables) and event invitations for staff and
 * participants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('participant_message_attachments')) {
            Schema::create('participant_message_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('message_id')->constrained('participant_messages')->cascadeOnDelete();
                $table->string('path', 500);
                $table->string('original_name', 255);
                $table->string('mime_type', 150)->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->timestamps();
                $table->index('message_id');
            });
        }

        if (! Schema::hasTable('event_invitations')) {
            Schema::create('event_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('invited'); // invited | registered
                $table->text('note')->nullable();
                $table->timestamps();
                $table->unique(['event_id', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_invitations');
        Schema::dropIfExists('participant_message_attachments');
    }
};
