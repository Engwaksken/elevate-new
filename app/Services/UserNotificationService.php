<?php
namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;

class UserNotificationService
{
    public function send(User $user, string $type, string $title, ?string $message = null, ?string $actionUrl = null, array $data = []): UserNotification
    {
        return UserNotification::create([
            'user_id'=>$user->id,
            'type'=>$type,
            'title'=>$title,
            'message'=>$message,
            'action_url'=>$actionUrl,
            'data'=>$data ?: null,
        ]);
    }
}
