<?php

namespace App\Observers;

use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class OperationalNotificationObserver
{
    public function created(Model $model): void
    {
        $this->notify($model,'created');
    }

    public function updated(Model $model): void
    {
        if (
            ! $model->wasChanged('status')
            && ! $model->wasChanged('progress_percent')
            && ! $model->wasChanged('pdf_path')
            && ! $model->wasChanged('assigned_to')
            && ! $model->wasChanged('owner_user_id')
        ) {
            return;
        }

        $this->notify($model,'updated');
    }

    private function notify(Model $model,string $event): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        $recipient=$this->recipient($model);

        if (! $recipient || (int)$recipient === (int)auth()->id()) {
            return;
        }

        [$type,$title,$message,$url]=$this->content($model,$event);

        UserNotification::create([
            'user_id'=>$recipient,
            'type'=>$type,
            'title'=>$title,
            'message'=>$message,
            'action_url'=>$url,
            'data'=>[
                'model'=>$model::class,
                'id'=>$model->getKey(),
                'event'=>$event,
            ],
        ]);
    }

    private function recipient(Model $model): ?int
    {
        $class=class_basename($model);

        return match($class) {
            'Task' => $this->intOrNull($model->getAttribute('assigned_to')),
            'Deliverable' => $this->intOrNull($model->getAttribute('owner_user_id')),
            'PurchaseRequest' => $this->intOrNull($model->getAttribute('requester_user_id')),
            'Certificate' => $this->intOrNull($model->getAttribute('user_id')),
            'Workplan' => $this->intOrNull($model->getAttribute('responsible_user_id')),
            default => null,
        };
    }

    private function content(Model $model,string $event): array
    {
        $class=class_basename($model);
        $status=$model->getAttribute('status');
        $name=$model->getAttribute('title')
            ?? $model->getAttribute('request_number')
            ?? $model->getAttribute('certificate_number')
            ?? ('#'.$model->getKey());

        return match($class) {
            'Task' => [
                'task',
                $event==='created' ? 'New task assigned' : 'Task updated',
                "{$name}".($status ? " is now ".str_replace('_',' ',$status)."." : '.'),
                '/admin/tasks',
            ],
            'Deliverable' => [
                'deliverable',
                $event==='created' ? 'New deliverable assigned' : 'Deliverable updated',
                "{$name}".($status ? " is now ".str_replace('_',' ',$status)."." : '.'),
                '/admin/deliverables',
            ],
            'PurchaseRequest' => [
                'procurement',
                'Purchase request updated',
                "{$name}".($status ? " is now ".str_replace('_',' ',$status)."." : '.'),
                '/admin/procurement/requests',
            ],
            'Certificate' => [
                'certificate',
                'Certificate available',
                "Your certificate {$name} has been generated or updated.",
                '/notifications',
            ],
            'Workplan' => [
                'workplan',
                'Workplan updated',
                "{$name}".($status ? " is now ".str_replace('_',' ',$status)."." : '.'),
                '/admin/workplans',
            ],
            default => ['system','Record updated',"{$name} was updated.",'/notifications'],
        };
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) && (int)$value > 0 ? (int)$value : null;
    }
}
