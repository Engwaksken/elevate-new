<?php

namespace App\Observers;

use App\Models\Programme;
use App\Models\Project;
use App\Services\EnrolmentIdentityService;
use Illuminate\Database\Eloquent\Model;

/**
 * Re-syncs participant identity codes when a programme or project code changes,
 * because the code forms part of the participant ID prefix.
 */
class IdentityResyncObserver
{
    public function updated(Model $model): void
    {
        $service = app(EnrolmentIdentityService::class);

        if ($model instanceof Programme && $model->wasChanged('code')) {
            $service->resyncForProgramme((int) $model->getKey());
        } elseif ($model instanceof Project && $model->wasChanged('code')) {
            $service->resyncForProject((int) $model->getKey());
        }
    }
}
