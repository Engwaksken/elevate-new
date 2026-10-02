<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    public function up(): void { Schema::table('course_time_slots', fn (Blueprint $table) => $table->index('starts_at')); }
    public function down(): void { Schema::table('course_time_slots', fn (Blueprint $table) => $table->dropIndex(['starts_at'])); }
};
