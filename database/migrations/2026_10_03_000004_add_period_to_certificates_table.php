<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('certificates')) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'period_start')) {
                $table->date('period_start')->nullable()->after('issued_on');
            }

            if (! Schema::hasColumn('certificates', 'period_end')) {
                $table->date('period_end')->nullable()->after('period_start');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('certificates')) {
            return;
        }

        Schema::table('certificates', function (Blueprint $table) {
            if (Schema::hasColumn('certificates', 'period_start')) {
                $table->dropColumn('period_start');
            }

            if (Schema::hasColumn('certificates', 'period_end')) {
                $table->dropColumn('period_end');
            }
        });
    }
};
