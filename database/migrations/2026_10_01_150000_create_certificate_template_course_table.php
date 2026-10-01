<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificate_template_course')) {
            Schema::create('certificate_template_course', function (Blueprint $table) {
                $table->foreignId('certificate_template_id')->constrained()->cascadeOnDelete();
                $table->foreignId('course_id')->constrained()->cascadeOnDelete();
                $table->primary(['certificate_template_id', 'course_id'], 'certificate_template_course_primary');
            });
        }

        // Existing single-course templates keep working through the new many-to-many link.
        DB::table('certificate_templates')
            ->where('context_type', 'course')
            ->whereNotNull('course_id')
            ->orderBy('id')
            ->get(['id', 'course_id'])
            ->each(fn ($template) => DB::table('certificate_template_course')->insertOrIgnore([
                'certificate_template_id' => $template->id,
                'course_id' => $template->course_id,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_template_course');
    }
};
