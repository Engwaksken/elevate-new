<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\DB;

/**
 * Creates purchase requests (header + line items) for both the procurement
 * admin page and the staff self-service page, so the rules live in one place.
 */
class PurchaseRequestService
{
    public function __construct(private PurchaseRequestNumberService $numbers) {}

    /** Validation rules shared by every create form. */
    public static function rules(): array
    {
        return [
            'procurement_plan_id'=>['nullable','exists:procurement_plans,id'],
            'programme_id'=>['nullable','exists:programmes,id'],
            'project_id'=>['nullable','exists:projects,id'],
            'workplan_id'=>['nullable','exists:workplans,id'],
            'activity_id'=>['nullable','exists:activities,id'],
            'department'=>['nullable','string','max:190'],
            'required_date'=>['nullable','date'],
            'funding_source'=>['nullable','string','max:190'],
            'justification'=>['nullable','string'],
            'currency'=>['nullable','string','size:3'],
            'items'=>['required','array','min:1'],
            'items.*.item_name'=>['required','string','max:190'],
            'items.*.specification'=>['nullable','string'],
            'items.*.quantity'=>['required','numeric','min:0.01'],
            'items.*.unit'=>['nullable','string','max:50'],
            'items.*.estimated_unit_cost'=>['nullable','numeric','min:0'],
            'items.*.is_asset'=>['nullable','boolean'],
        ];
    }

    public function create(array $data, int $requesterId, string $status = 'draft'): PurchaseRequest
    {
        return DB::transaction(function () use ($data, $requesterId, $status) {
            $purchaseRequest=PurchaseRequest::create([
                'request_number'=>$this->numbers->next(),
                'procurement_plan_id'=>$data['procurement_plan_id'] ?? null,
                'programme_id'=>$data['programme_id'] ?? null,
                'project_id'=>$data['project_id'] ?? null,
                'workplan_id'=>$data['workplan_id'] ?? null,
                'activity_id'=>$data['activity_id'] ?? null,
                'requester_user_id'=>$requesterId,
                'department'=>$data['department'] ?? null,
                'required_date'=>$data['required_date'] ?? null,
                'funding_source'=>$data['funding_source'] ?? null,
                'justification'=>$data['justification'] ?? null,
                'currency'=>strtoupper($data['currency'] ?? '') ?: 'UGX',
                'status'=>$status,
            ]);

            $total=0;
            foreach($data['items'] as $item){
                $line=(float)$item['quantity']*(float)($item['estimated_unit_cost'] ?? 0);
                $purchaseRequest->items()->create([
                    ...$item,
                    'estimated_total'=>$line,
                    'is_asset'=>(bool)($item['is_asset'] ?? false),
                ]);
                $total+=$line;
            }

            $purchaseRequest->update(['estimated_total'=>$total]);

            return $purchaseRequest;
        });
    }
}
