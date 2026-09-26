<?php

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AuditableObserver
{
    public function created(Model $model): void
    {
        $this->write($model,'created',[], $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes=$model->getChanges();

        if ($changes === []) {
            return;
        }

        $old=[];
        foreach(array_keys($changes) as $key){
            $old[$key]=$model->getOriginal($key);
        }

        $this->write($model,'updated',$old,$changes);
    }

    public function deleted(Model $model): void
    {
        $this->write($model,'deleted',$model->getAttributes(),[]);
    }

    private function write(Model $model,string $action,array $old,array $new): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $request=request();

        AuditLog::create([
            'user_id'=>auth()->id(),
            'module'=>$this->moduleName($model),
            'action'=>$action,
            'auditable_type'=>$model::class,
            'auditable_id'=>$model->getKey(),
            'old_values'=>$this->sanitise($old),
            'new_values'=>$this->sanitise($new),
            'ip_address'=>$request?->ip(),
            'user_agent'=>$request?->userAgent(),
            'occurred_at'=>now(),
        ]);
    }

    private function moduleName(Model $model): string
    {
        return match(class_basename($model)) {
            'Task','Deliverable','Workplan','Activity','Milestone' => 'Planning & Delivery',
            'PurchaseRequest','PurchaseOrder','Supplier' => 'Procurement',
            'Asset','AssetAssignment','AssetMaintenance','AssetDisposal' => 'Assets',
            'Employee','LeaveRequest','Appraisal','StaffExit' => 'Human Resources',
            'Indicator','IndicatorResult','ResultsFramework','Result' => 'MEAL',
            'Certificate','Course','Enrolment' => 'Learning',
            default => class_basename($model),
        };
    }

    private function sanitise(array $values): array
    {
        foreach($values as $key=>$value){
            if(in_array(strtolower((string)$key),[
                'password','remember_token','two_factor_secret','two_factor_recovery_codes',
                'bank_account_number_encrypted','api_key','secret','token'
            ],true)){
                $values[$key]='[redacted]';
            }
        }

        return $values;
    }
}
