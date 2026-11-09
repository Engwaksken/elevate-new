<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Library books can belong to one or more courses. A book linked to courses
 * is only available to participants enrolled in one of them; a book with no
 * course stays open to every participant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_library_resource')) {
            return;
        }

        Schema::create('course_library_resource', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignId('library_resource_id')->constrained('library_resources')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'library_resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_library_resource');
    }
};
