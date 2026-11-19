<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Upcoming events that have no reminders get the automatic defaults
 * (1 day and 1 hour before), matching what new events now receive.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_reminders') || ! Schema::hasTable('events')) {
            return;
        }

        $eventIds = DB::table('events')
            ->whereNull('deleted_at')
            ->where('starts_at', '>', now())
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('event_reminders')->whereColumn('event_reminders.event_id', 'events.id'))
            ->pluck('id');

        foreach ($eventIds as $eventId) {
            foreach ([1440, 60] as $minutes) {
                DB::table('event_reminders')->insert([
                    'event_id' => $eventId,
                    'minutes_before' => $minutes,
                    'delivery_method' => 'both',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Defaults are ordinary reminders once created; nothing to undo.
    }
};
