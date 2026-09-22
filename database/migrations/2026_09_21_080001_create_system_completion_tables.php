<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->default('general')->index();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->string('type')->default('string');
            $table->boolean('is_public')->default(false);
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('scheduled_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel')->default('in_app');
            $table->string('type')->index();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('action_url')->nullable();
            $table->timestamp('send_at')->index();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending','sent','failed','cancelled'])->default('pending')->index();
            $table->json('payload')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('api_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('client_key')->unique();
            $table->string('client_secret_hash');
            $table->json('allowed_scopes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('report_type');
            $table->string('format');
            $table->json('filters')->nullable();
            $table->string('path')->nullable();
            $table->enum('status', ['queued','processing','completed','failed'])->default('queued');
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->index();
            $table->string('severity')->default('info')->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
        Schema::dropIfExists('report_exports');
        Schema::dropIfExists('api_clients');
        Schema::dropIfExists('scheduled_reminders');
        Schema::dropIfExists('system_settings');
    }
};
