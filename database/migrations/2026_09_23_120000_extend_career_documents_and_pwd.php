<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'disability_types')) {
                $table->json('disability_types')->nullable()->after('is_pwd');
            }
            if (! Schema::hasColumn('profiles', 'disability_other')) {
                $table->string('disability_other')->nullable()->after('disability_types');
            }
        });

        if (! Schema::hasTable('cover_letter_uploads')) {
            Schema::create('cover_letter_uploads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('cover_letter_id')->nullable()->constrained()->nullOnDelete();
                $table->string('original_name');
                $table->string('stored_name');
                $table->string('mime_type', 120);
                $table->unsignedBigInteger('file_size');
                $table->string('path');
                $table->string('status')->default('uploaded');
                $table->longText('extracted_text')->nullable();
                $table->json('parsed_data')->nullable();
                $table->text('parsing_error')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id','status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cover_letter_uploads');

        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'disability_other')) {
                $table->dropColumn('disability_other');
            }
            if (Schema::hasColumn('profiles', 'disability_types')) {
                $table->dropColumn('disability_types');
            }
        });
    }
};
