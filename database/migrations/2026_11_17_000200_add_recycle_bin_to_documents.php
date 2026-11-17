<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recycle bin for the document libraries. Deleting an item soft-deletes it
 * and everything inside it under one batch id; the item the user picked is
 * the batch root shown in the recycle bin. Items are purged after 93 days.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['document_folders', 'document_files'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->softDeletes();
                $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('delete_batch', 36)->nullable()->index();
                $table->boolean('is_delete_root')->default(false);
            });
        }
    }

    public function down(): void
    {
        foreach (['document_folders', 'document_files'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('deleted_by');
                $table->dropIndex(['delete_batch']);
                $table->dropColumn(['deleted_at', 'delete_batch', 'is_delete_root']);
            });
        }
    }
};
