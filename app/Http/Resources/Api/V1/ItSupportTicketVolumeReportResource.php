<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItSupportTicketVolumeReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource['status'],
            'category' => $this->resource['category'],
            'priority' => $this->resource['priority'],
        ];
    }
}
