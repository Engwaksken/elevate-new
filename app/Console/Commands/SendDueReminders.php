<?php
namespace App\Console\Commands;

use App\Models\ScheduledReminder;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class SendDueReminders extends Command
{
    protected $signature='elevateher:send-reminders';
    protected $description='Send due in-app reminders';

    public function handle(): int
    {
        $reminders=ScheduledReminder::where('status','pending')
            ->where('send_at','<=',now())
            ->orderBy('send_at')
            ->limit(500)
            ->get();

        foreach($reminders as $reminder){
            try{
                if($reminder->user_id){
                    UserNotification::create([
                        'user_id'=>$reminder->user_id,
                        'type'=>$reminder->type,
                        'title'=>$reminder->title,
                        'message'=>$reminder->message,
                        'action_url'=>$reminder->action_url,
                        'data'=>$reminder->payload,
                    ]);
                }

                $reminder->update([
                    'status'=>'sent',
                    'sent_at'=>now(),
                ]);
            }catch(\Throwable $e){
                $reminder->update([
                    'status'=>'failed',
                    'failure_reason'=>$e->getMessage(),
                ]);
                report($e);
            }
        }

        $this->info("Processed {$reminders->count()} reminder(s).");
        return self::SUCCESS;
    }
}
