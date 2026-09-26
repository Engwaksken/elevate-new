<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asset_disposals')) {
            Schema::create('asset_disposals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('asset_id')->unique()->constrained()->cascadeOnDelete();
                $table->date('requested_date');
                $table->text('reason');
                $table->string('disposal_method',100)->nullable();
                $table->decimal('disposal_value',15,2)->nullable();
                $table->string('currency',3)->nullable();
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->string('status',50)->default('requested');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
