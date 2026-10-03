<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('courses') && ! Schema::hasColumn('courses', 'entry_assessment_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->foreignId('entry_assessment_id')->nullable()->after('self_enrolment_enabled')
                    ->constrained('assessments')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'entry_assessment_id')) {
            Schema::table('courses', function (Blueprint $table) {
                $table->dropConstrainedForeignId('entry_assessment_id');
            });
        }
    }
};
