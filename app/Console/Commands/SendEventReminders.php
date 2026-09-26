<?php
namespace App\Console\Commands;

use App\Models\EventReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class SendEventReminders extends Command
{
    protected $signature='events:send-reminders';
    protected $description='Send due ElevateHer360 event reminders';

    public function handle(): int
    {
        $now=now();

        $reminders=EventReminder::with(['event.registrations.user'])
            ->where('is_active',true)
            ->whereHas('event',fn($q)=>$q->where('is_published',true)->where('starts_at','>',now()))
            ->get();

        $sent=0;

        foreach($reminders as $reminder){
            $event=$reminder->event;
            $dueAt=$event->starts_at->copy()->subMinutes($reminder->minutes_before);

            if($now->lt($dueAt) || $now->gt($dueAt->copy()->addMinutes(10))){
                continue;
            }

            foreach($event->registrations as $registration){
                $user=$registration->user;
                if(!$user) continue;

                foreach($this->channels($reminder->delivery_method) as $channel){
                    $already=DB::table('event_reminder_deliveries')
                        ->where('event_reminder_id',$reminder->id)
                        ->where('user_id',$user->id)
                        ->where('channel',$channel)
                        ->exists();

                    if($already) continue;

                    $error=null;

                    try{
                        if($channel==='email' && $user->email){
                            Mail::raw(
                                "Reminder: {$event->title}\n\n".
                                "Starts: {$event->starts_at->format('d M Y H:i')}\n".
                                "Venue: ".($event->venue ?: ucfirst($event->delivery_mode))."\n\n".
                                route('events.show',$event),
                                fn($message)=>$message->to($user->email)->subject("Event Reminder: {$event->title}")
                            );
                        }

                        if($channel==='system' && Schema::hasTable('user_notifications')){
                            DB::table('user_notifications')->insert([
                                'user_id'=>$user->id,
                                'type'=>'event_reminder',
                                'title'=>'Event Reminder',
                                'message'=>"{$event->title} starts on {$event->starts_at->format('d M Y H:i')}.",
                                'action_url'=>route('events.show',$event),
                                'data'=>json_encode(['event_id'=>$event->id]),
                                'created_at'=>now(),
                                'updated_at'=>now(),
                            ]);
                        }
                    }catch(\Throwable $e){
                        $error=$e->getMessage();
                    }

                    DB::table('event_reminder_deliveries')->insert([
                        'event_reminder_id'=>$reminder->id,
                        'user_id'=>$user->id,
                        'channel'=>$channel,
                        'sent_at'=>now(),
                        'error_message'=>$error,
                        'created_at'=>now(),
                        'updated_at'=>now(),
                    ]);

                    $sent++;
                }
            }

            $reminder->update(['last_processed_at'=>now()]);
        }

        $this->info("Processed {$sent} reminder delivery record(s).");
        return self::SUCCESS;
    }

    private function channels(string $method): array
    {
        return $method==='both' ? ['email','system'] : [$method];
    }
}