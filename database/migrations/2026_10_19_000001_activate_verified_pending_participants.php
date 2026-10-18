<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Participants who verified their email but never signed in again stayed
     * "pending". Verification now activates them; catch up existing accounts.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('user_type', 'participant')
            ->where('status', 'pending')
            ->whereNotNull('email_verified_at')
            ->update(['status' => 'active', 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Data fix only; the previous pending state is not recoverable.
    }
};
