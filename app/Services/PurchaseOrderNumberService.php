<?php
namespace App\Services;

use App\Models\PurchaseOrder;

class PurchaseOrderNumberService
{
    public function next(): string
    {
        $prefix='PO-'.now()->format('Ym').'-';
        $last=PurchaseOrder::where('po_number','like',$prefix.'%')
            ->orderByDesc('id')->value('po_number');

        $next=$last ? ((int)substr($last,-5)+1) : 1;

        return $prefix.str_pad((string)$next,5,'0',STR_PAD_LEFT);
    }
}
