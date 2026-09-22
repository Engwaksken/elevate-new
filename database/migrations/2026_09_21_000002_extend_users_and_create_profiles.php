<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'user_type')) {
                $table->enum('user_type', ['participant', 'staff'])->default('participant')->after('email');
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending')->after('user_type');
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable()->index()->after('status');
            }
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('surname')->nullable();
            $table->string('given_name')->nullable();
            $table->string('other_name')->nullable();
            $table->enum('gender', ['female', 'male', 'other', 'prefer_not_to_say'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('country')->nullable();
            $table->string('district')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_pwd')->default(false);
            $table->string('education_level')->nullable();
            $table->string('employment_status')->nullable();
            $table->text('career_interests')->nullable();
            $table->string('preferred_language')->default('English');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
