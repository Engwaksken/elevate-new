<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timetable_reminder_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_time_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('sent_at');
            $table->unique(['course_time_slot_id', 'user_id', 'starts_at'], 'timetable_reminder_once');
        });
    }
    public function down(): void { Schema::dropIfExists('timetable_reminder_deliveries'); }
};
