<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('course_cohort')) {
            Schema::create('course_cohort', function (Blueprint $table) {
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cohort_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['course_id','cohort_id']);
            });
        }
    }

    public function down(): void
    {
        // Repair migration: intentionally do not drop a core table on rollback.
    }
};
