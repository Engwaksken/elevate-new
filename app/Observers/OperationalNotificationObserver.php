<?php

namespace App\Observers;

use App\Services\NotificationDispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Sends an in-app + email notice to whoever a record is assigned to (tasks,
 * deliverables, milestones, activities, workplans, purchase requests) or is
 * about (certificates) whenever it is created or materially updated.
 */
class OperationalNotificationObserver
{
    public function created(Model $model): void
    {
        $this->notify($model, 'created');
    }

    public function updated(Model $model): void
    {
        if (
            ! $model->wasChanged('status')
            && ! $model->wasChanged('progress_percent')
            && ! $model->wasChanged('pdf_path')
            && ! $model->wasChanged('assigned_to')
            && ! $model->wasChanged('owner_user_id')
            && ! $model->wasChanged('responsible_user_id')
        ) {
            return;
        }

        $this->notify($model, 'updated');
    }

    private function notify(Model $model, string $event): void
    {
        if (! Schema::hasTable('user_notifications')) {
            return;
        }

        $recipientId = $this->recipient($model);

        if (! $recipientId || (int) $recipientId === (int) auth()->id()) {
            return;
        }

        $user = \App\Models\User::find($recipientId);
        if (! $user) {
            return;
        }

        [$type, $title, $message, $url] = $this->content($model, $event);

        app(NotificationDispatcher::class)->notify($user, $type, $title, $message, $url, [
            'model' => $model::class,
            'id' => $model->getKey(),
            'event' => $event,
        ]);
    }

    private function recipient(Model $model): ?int
    {
        return match (class_basename($model)) {
            'Task' => $this->intOrNull($model->getAttribute('assigned_to')),
            'Deliverable' => $this->intOrNull($model->getAttribute('owner_user_id')),
            'Milestone', 'Activity' => $this->intOrNull($model->getAttribute('responsible_user_id')),
            'PurchaseRequest' => $this->intOrNull($model->getAttribute('requester_user_id')),
            'Certificate' => $this->intOrNull($model->getAttribute('user_id')),
            'Workplan' => $this->intOrNull($model->getAttribute('responsible_user_id')),
            default => null,
        };
    }

    private function content(Model $model, string $event): array
    {
        $class = class_basename($model);
        $status = $model->getAttribute('status');
        $name = $model->getAttribute('title')
            ?? $model->getAttribute('request_number')
            ?? $model->getAttribute('certificate_number')
            ?? ('#'.$model->getKey());

        return match ($class) {
            'Task' => [
                'task',
                $event === 'created' ? 'New task assigned' : 'Task updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
                $model->getAttribute('activity_id') ? '/admin/tasks' : '/staff/tasks',
            ],
            'Deliverable' => [
                'deliverable',
                $event === 'created' ? 'New deliverable assigned' : 'Deliverable updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
                '/admin/deliverables',
            ],
            'Milestone' => [
                'milestone',
                $event === 'created' ? 'New workplan milestone assigned' : 'Workplan milestone updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
                '/admin/workplans',
            ],
            'Activity' => [
                'activity',
                $event === 'created' ? 'New activity assigned' : 'Activity updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
                '/admin/workplans',
            ],
            'PurchaseRequest' => [
                'procurement',
                'Purchase request updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
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
                $event === 'created' ? 'Workplan assigned' : 'Workplan updated',
                "{$name}".($status ? ' is now '.str_replace('_', ' ', $status).'.' : '.'),
                '/admin/workplans',
            ],
            default => ['system', 'Record updated', "{$name} was updated.", '/notifications'],
        };
    }

    private function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
