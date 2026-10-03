<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('participant_goals')) {
            Schema::create('participant_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('mentor_match_id')->nullable()->constrained('mentor_matches')->nullOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category')->default('career');
                $table->string('unit')->nullable();
                $table->decimal('baseline_value', 18, 4)->nullable();
                $table->decimal('target_value', 18, 4)->nullable();
                $table->decimal('current_value', 18, 4)->nullable();
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('target_date')->nullable();
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->enum('status', ['not_started', 'in_progress', 'completed', 'cancelled'])->default('not_started');
                $table->enum('source', ['self', 'mentor', 'programme'])->default('self');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('participant_goals');
    }
};
