<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'mfa_enabled')) {
                $table->boolean('mfa_enabled')->default(false)->after('last_login_at');
            }
            if (! Schema::hasColumn('users', 'mfa_secret')) {
                $table->text('mfa_secret')->nullable()->after('mfa_enabled');
            }
            if (! Schema::hasColumn('users', 'mfa_recovery_codes')) {
                $table->text('mfa_recovery_codes')->nullable()->after('mfa_secret');
            }
            if (! Schema::hasColumn('users', 'mfa_confirmed_at')) {
                $table->timestamp('mfa_confirmed_at')->nullable()->after('mfa_recovery_codes');
            }
        });

        Schema::create('login_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable()->index();
            $table->boolean('successful')->default(false)->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_audits');

        Schema::table('users', function (Blueprint $table) {
            foreach (['mfa_confirmed_at','mfa_recovery_codes','mfa_secret','mfa_enabled'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
