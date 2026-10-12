<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enrolment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('content_rating');
            $table->unsignedTinyInteger('instructor_rating');
            $table->unsignedTinyInteger('recommend_rating');
            $table->text('key_learning')->nullable();
            $table->text('what_to_improve')->nullable();
            $table->text('additional_comments')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->unique(['course_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_feedback_responses');
    }
};
