<?php
namespace App\Services;

use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use Carbon\CarbonPeriod;

class LeaveService
{
    public function workingDays(string $start, string $end): float
    {
        $count = 0;
        foreach (CarbonPeriod::create($start,$end) as $date) {
            if (! $date->isWeekend()) {
                $count++;
            }
        }
        return (float)$count;
    }

    public function approveFinal(LeaveRequest $request): LeaveRequest
    {
        $balance = LeaveBalance::where('employee_id',$request->employee_id)
            ->where('leave_type_id',$request->leave_type_id)
            ->where('year',$request->start_date->format('Y'))
            ->first();

        if ($balance && (float)$balance->remaining < (float)$request->days_requested) {
            throw new \RuntimeException('Insufficient leave balance.');
        }

        $request->update([
            'status'=>'hr_approved',
            'hr_approved_by'=>auth()->id(),
            'hr_approved_at'=>now(),
        ]);

        if ($balance) {
            $used = (float)$balance->used + (float)$request->days_requested;
            $remaining = (float)$balance->opening_balance + (float)$balance->accrued + (float)$balance->adjustments - $used;
            $balance->update(['used'=>$used,'remaining'=>$remaining]);
        }

        return $request->fresh();
    }
}
