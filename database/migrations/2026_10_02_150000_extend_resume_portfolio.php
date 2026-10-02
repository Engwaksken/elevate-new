<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', fn (Blueprint $table) => $table->string('portfolio_url', 2048)->nullable());
        Schema::create('resume_referees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('name', 190);
            $table->string('job_title', 190)->nullable();
            $table->string('organisation', 190)->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('relationship', 190)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('resume_portfolio_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('label', 190);
            $table->string('original_name');
            $table->string('path');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_portfolio_files');
        Schema::dropIfExists('resume_referees');
        Schema::table('resumes', fn (Blueprint $table) => $table->dropColumn('portfolio_url'));
    }
};
