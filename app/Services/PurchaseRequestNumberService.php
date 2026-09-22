<?php
namespace App\Services;

use App\Models\PurchaseRequest;

class PurchaseRequestNumberService
{
    public function next(): string
    {
        $prefix='PR-'.now()->format('Ym').'-';
        $last=PurchaseRequest::where('request_number','like',$prefix.'%')
            ->orderByDesc('id')->value('request_number');

        $next=$last ? ((int)substr($last,-5)+1) : 1;

        return $prefix.str_pad((string)$next,5,'0',STR_PAD_LEFT);
    }
}
