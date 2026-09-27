<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            if (! Schema::hasColumn('certificates', 'certificate_template_id')) {
                $table->foreignId('certificate_template_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('certificate_templates')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('certificates', 'event_id')) {
                $table->foreignId('event_id')
                    ->nullable()
                    ->after('course_id')
                    ->constrained()
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
    }
};