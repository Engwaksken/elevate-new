<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_sync_operations')) {
            Schema::create('mobile_sync_operations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('client_operation_id', 190);
                $table->string('operation_type', 80);
                $table->json('payload')->nullable();
                $table->string('status', 30)->default('processed');
                $table->json('result')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id','client_operation_id'], 'mobile_sync_operation_unique');
                $table->index(['user_id','operation_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_sync_operations');
    }
};
