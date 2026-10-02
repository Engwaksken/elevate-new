<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_course', function (Blueprint $table) {
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->primary(['branch_id', 'course_id']);
        });
        DB::table('courses')->whereNotNull('branch_id')->orderBy('id')->chunkById(200, function ($courses) {
            foreach ($courses as $course) {
                DB::table('branch_course')->insert(['branch_id' => $course->branch_id, 'course_id' => $course->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_course');
    }
};
