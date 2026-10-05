<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class LearningReportResource extends JsonResource
{
    public function toArray($request): array
    {
        return $this->resource;
    }
}
