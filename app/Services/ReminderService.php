<?php
namespace App\Services;

use App\Models\ScheduledReminder;
use App\Models\User;
use Carbon\CarbonInterface;

class ReminderService
{
    public function schedule(User $user, string $type, string $title, ?string $message, CarbonInterface $sendAt, ?string $actionUrl=null, string $channel='in_app', array $payload=[]): ScheduledReminder
    {
        return ScheduledReminder::create([
            'user_id'=>$user->id,
            'channel'=>$channel,
            'type'=>$type,
            'title'=>$title,
            'message'=>$message,
            'action_url'=>$actionUrl,
            'send_at'=>$sendAt,
            'payload'=>$payload ?: null,
            'status'=>'pending',
        ]);
    }
}
