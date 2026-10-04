<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mentorship_session_reports')) {
            return;
        }

        Schema::create('mentorship_session_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mentorship_session_id')->constrained('mentorship_sessions')->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->enum('role', ['mentor', 'mentee']);
            $table->text('summary');
            $table->text('challenges')->nullable();
            $table->text('achievements')->nullable();
            $table->boolean('mentor_attended')->default(false);
            $table->boolean('mentee_attended')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['mentorship_session_id', 'role'], 'mentorship_session_report_role_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentorship_session_reports');
    }
};
