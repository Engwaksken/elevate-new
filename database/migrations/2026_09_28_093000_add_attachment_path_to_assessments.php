<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assessments') && ! Schema::hasColumn('assessments', 'attachment_path')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->string('attachment_path')->nullable()->after('due_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assessments') && Schema::hasColumn('assessments', 'attachment_path')) {
            Schema::table('assessments', function (Blueprint $table) {
                $table->dropColumn('attachment_path');
            });
        }
    }
};
