<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('events')) {
            Schema::table('events', function(Blueprint $table){
                if (!Schema::hasColumn('events','checkin_token')) {
                    $table->uuid('checkin_token')->nullable()->unique();
                }
            });

            \DB::table('events')->whereNull('checkin_token')->orderBy('id')->chunkById(100,function($rows){
                foreach($rows as $row){
                    \DB::table('events')->where('id',$row->id)->update(['checkin_token'=>(string)Str::uuid()]);
                }
            });
        }

        if (!Schema::hasTable('event_reminders')) {
            Schema::create('event_reminders', function(Blueprint $table){
                $table->id();
                $table->foreignId('event_id')->constrained()->cascadeOnDelete();
                $table->unsignedInteger('minutes_before');
                $table->string('delivery_method')->default('both');
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_processed_at')->nullable();
                $table->timestamps();
                $table->unique(['event_id','minutes_before'],'event_reminder_unique');
            });
        }

        if (!Schema::hasTable('event_reminder_deliveries')) {
            Schema::create('event_reminder_deliveries', function(Blueprint $table){
                $table->id();
                $table->foreignId('event_reminder_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('channel');
                $table->timestamp('sent_at');
                $table->text('error_message')->nullable();
                $table->timestamps();
                $table->unique(['event_reminder_id','user_id','channel'],'event_reminder_delivery_unique');
            });
        }
    }

    public function down(): void {
        Schema::dropIfExists('event_reminder_deliveries');
        Schema::dropIfExists('event_reminders');
        if (Schema::hasTable('events') && Schema::hasColumn('events','checkin_token')) {
            Schema::table('events', fn(Blueprint $table)=>$table->dropColumn('checkin_token'));
        }
    }
};