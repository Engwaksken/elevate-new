<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links the instructor's daily task to the approved appointment it came from. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'instructor_appointment_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('instructor_appointment_id')->nullable()->after('staff_kpi_id')
                ->constrained('instructor_appointments')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'instructor_appointment_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instructor_appointment_id');
        });
    }
};
