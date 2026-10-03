<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('certificate_templates') && ! Schema::hasColumn('certificate_templates', 'layout')) {
            Schema::table('certificate_templates', function (Blueprint $table) {
                $table->json('layout')->nullable()->after('orientation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('certificate_templates') && Schema::hasColumn('certificate_templates', 'layout')) {
            Schema::table('certificate_templates', function (Blueprint $table) {
                $table->dropColumn('layout');
            });
        }
    }
};
