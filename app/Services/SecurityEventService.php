<?php
namespace App\Services;

use App\Models\SecurityEvent;

class SecurityEventService
{
    public function record(string $eventType, string $severity='info', array $context=[]): void
    {
        SecurityEvent::create([
            'user_id'=>auth()->id(),
            'event_type'=>$eventType,
            'severity'=>$severity,
            'ip_address'=>request()?->ip(),
            'user_agent'=>request()?->userAgent(),
            'context'=>$context ?: null,
            'occurred_at'=>now(),
        ]);
    }
}
