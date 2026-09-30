<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('assignment_extension_requests')) {
            return;
        }

        Schema::create('assignment_extension_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->dateTime('requested_due_at')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->text('reviewer_note')->nullable();
            $table->dateTime('approved_due_at')->nullable();
            $table->timestamps();

            $table->index(['assessment_id', 'user_id', 'status'], 'assignment_ext_req_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_extension_requests');
    }
};
