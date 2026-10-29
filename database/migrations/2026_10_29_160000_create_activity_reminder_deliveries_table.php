<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per reminder sent (appointment, mentorship session or career step), so each is sent once.
        Schema::create('activity_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('remindable_type', 60);
            $table->unsignedBigInteger('remindable_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->timestamp('sent_at');
            $table->unique(['remindable_type', 'remindable_id', 'user_id', 'kind'], 'activity_reminder_once');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reminder_deliveries');
    }
};
