<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            if (! Schema::hasColumn('assessments', 'allow_link_submissions')) {
                $table->boolean('allow_link_submissions')->default(false);
            }
        });

        Schema::table('assessment_attempts', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_attempts', 'submission_links')) {
                $table->json('submission_links')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('assessment_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('assessment_attempts', 'submission_links')) {
                $table->dropColumn('submission_links');
            }
        });

        Schema::table('assessments', function (Blueprint $table) {
            if (Schema::hasColumn('assessments', 'allow_link_submissions')) {
                $table->dropColumn('allow_link_submissions');
            }
        });
    }
};
