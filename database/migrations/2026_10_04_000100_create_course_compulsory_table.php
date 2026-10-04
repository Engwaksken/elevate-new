<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_compulsory')) {
            return;
        }

        Schema::create('course_compulsory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('compulsory_course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'compulsory_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_compulsory');
    }
};
