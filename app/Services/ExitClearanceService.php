<?php
namespace App\Services;

use App\Models\StaffExit;

class ExitClearanceService
{
    public function createDefaultClearances(StaffExit $exit): void
    {
        foreach (['Supervisor','HR','Operations','Finance','IT','Assets / Stores','Programme / Project'] as $area) {
            $exit->clearances()->firstOrCreate(['clearance_area'=>$area],['status'=>'pending']);
        }
    }

    public function canComplete(StaffExit $exit): bool
    {
        return ! $exit->clearances()->where('status','!=','cleared')->exists()
            && ! $exit->handoverItems()->where('status','!=','completed')->exists();
    }
}
