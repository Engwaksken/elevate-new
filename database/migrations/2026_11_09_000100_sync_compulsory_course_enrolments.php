<?php

use App\Services\EnrolmentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * One-off catch-up: participants registered before compulsory courses were
 * linked to their course are enrolled in those compulsory courses now.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_compulsory') || ! Schema::hasTable('enrolments')) {
            return;
        }

        app(EnrolmentService::class)->syncAllCompulsory();
    }

    public function down(): void
    {
        // Enrolments created here are ordinary enrolments; nothing to undo.
    }
};
