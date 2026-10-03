<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('programmes') && ! Schema::hasColumn('programmes', 'progress_percent')) {
            Schema::table('programmes', function (Blueprint $table) {
                $table->decimal('progress_percent', 5, 2)->default(0)->after('status');
            });
        }

        if (! Schema::hasTable('programme_targets')) {
            Schema::create('programme_targets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('programme_id')->constrained()->cascadeOnDelete();
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('cohort_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('result_area')->nullable();
                $table->string('unit')->nullable();
                $table->decimal('baseline_value', 18, 4)->nullable();
                $table->decimal('target_value', 18, 4)->default(0);
                $table->decimal('achieved_value', 18, 4)->default(0);
                $table->decimal('weight', 5, 2)->default(1);
                $table->decimal('progress_percent', 5, 2)->default(0);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->enum('status', ['not_started', 'in_progress', 'achieved', 'at_risk', 'cancelled'])->default('not_started');
                $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('achieved_at')->nullable();
                $table->timestamps();

                $table->index(['programme_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_targets');
    }
};
