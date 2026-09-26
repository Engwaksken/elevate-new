<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platform_backups')) {
            Schema::create('platform_backups', function (Blueprint $table) {
                $table->id();
                $table->string('destination')->default('local');
                $table->string('filename');
                $table->string('path')->nullable();
                $table->unsignedBigInteger('size_bytes')->nullable();
                $table->enum('status', ['pending','completed','failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_backups');
    }
};
