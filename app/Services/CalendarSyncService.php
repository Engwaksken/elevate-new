<?php
namespace App\Services;

use App\Models\CalendarEvent;
use Illuminate\Database\Eloquent\Model;

class CalendarSyncService
{
    public function syncFromModel(Model $model, string $type, string $title, $start, $end = null): CalendarEvent
    {
        return CalendarEvent::updateOrCreate(
            [
                'eventable_type'=>$model->getMorphClass(),
                'eventable_id'=>$model->getKey(),
                'event_type'=>$type,
            ],
            [
                'title'=>$title,
                'starts_at'=>$start,
                'ends_at'=>$end,
                'status'=>'scheduled',
            ]
        );
    }
}
