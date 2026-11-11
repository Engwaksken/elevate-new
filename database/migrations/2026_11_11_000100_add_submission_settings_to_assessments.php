<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Instructors choose which file formats participants may submit for an
 * assignment and how many files a submission may contain.
 * A null submission_formats keeps the formats accepted before this change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'submission_formats')) {
                $table->json('submission_formats')->nullable();
            }
            if (! Schema::hasColumn('assessments', 'max_submission_files')) {
                $table->unsignedTinyInteger('max_submission_files')->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['submission_formats', 'max_submission_files']);
        });
    }
};
