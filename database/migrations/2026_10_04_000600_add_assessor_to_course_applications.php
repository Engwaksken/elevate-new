<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('course_applications', 'assessor_user_id')) {
                $table->unsignedBigInteger('assessor_user_id')->nullable()->after('assessment_attempt_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('course_applications', function (Blueprint $table) {
            $table->dropColumn('assessor_user_id');
        });
    }
};
