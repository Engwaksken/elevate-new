<?php
namespace App\Services;

use App\Models\AssetAssignment;
use App\Models\StaffExit;

class AssetExitClearanceService
{
    public function outstandingAssets(StaffExit $exit)
    {
        return AssetAssignment::with('asset')
            ->where('assigned_to_user_id',$exit->employee->user_id)
            ->where('status','active')
            ->get();
    }

    public function syncClearance(StaffExit $exit): void
    {
        $clearance=$exit->clearances()->where('clearance_area','Assets / Stores')->first();

        if(! $clearance) return;

        $count=$this->outstandingAssets($exit)->count();

        if($count > 0){
            $clearance->update([
                'status'=>'blocked',
                'remarks'=>"{$count} assigned asset(s) must be returned before exit clearance."
            ]);
        }
    }
}
