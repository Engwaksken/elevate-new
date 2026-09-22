<?php
namespace App\Services;

use App\Models\User;
use App\Models\Course;
use App\Models\Job;
use App\Models\LibraryResource;
use App\Models\Workplan;
use App\Models\Activity;
use App\Models\Asset;
use App\Models\PurchaseRequest;

class GlobalSearchService
{
    public function search(string $term): array
    {
        $term=trim($term);
        if($term==='') return [];

        return [
            'users'=>User::where('name','like',"%{$term}%")
                ->orWhere('email','like',"%{$term}%")->limit(10)->get(),
            'courses'=>Course::where('title','like',"%{$term}%")
                ->orWhere('code','like',"%{$term}%")->limit(10)->get(),
            'jobs'=>Job::where('title','like',"%{$term}%")->limit(10)->get(),
            'library'=>LibraryResource::where('title','like',"%{$term}%")
                ->orWhere('author','like',"%{$term}%")->limit(10)->get(),
            'workplans'=>Workplan::where('title','like',"%{$term}%")->limit(10)->get(),
            'activities'=>Activity::where('title','like',"%{$term}%")
                ->orWhere('activity_code','like',"%{$term}%")->limit(10)->get(),
            'assets'=>Asset::where('asset_code','like',"%{$term}%")
                ->orWhere('asset_tag','like',"%{$term}%")
                ->orWhere('serial_number','like',"%{$term}%")
                ->orWhere('description','like',"%{$term}%")->limit(10)->get(),
            'purchase_requests'=>PurchaseRequest::where('request_number','like',"%{$term}%")
                ->orWhere('justification','like',"%{$term}%")->limit(10)->get(),
        ];
    }
}
