<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Asset;
use App\Models\Course;
use App\Models\Job;
use App\Models\LibraryResource;
use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\Workplan;

class GlobalSearchService
{
    public function search(string $term,string $category='all'): array
    {
        $term=trim($term);
        if($term==='') return [];

        $queries=[
            'users'=>fn()=>User::where(function($q) use($term){
                $q->where('name','like',"%{$term}%")
                    ->orWhere('email','like',"%{$term}%")
                    ->orWhere('phone','like',"%{$term}%");
            })->limit(20)->get(),

            'courses'=>fn()=>Course::where(function($q) use($term){
                $q->where('title','like',"%{$term}%")
                    ->orWhere('code','like',"%{$term}%")
                    ->orWhere('summary','like',"%{$term}%");
            })->limit(20)->get(),

            'jobs'=>fn()=>Job::with('employer')->where(function($q) use($term){
                $q->where('title','like',"%{$term}%")
                    ->orWhere('industry','like',"%{$term}%")
                    ->orWhere('location','like',"%{$term}%");
            })->limit(20)->get(),

            'library'=>fn()=>LibraryResource::where(function($q) use($term){
                $q->where('title','like',"%{$term}%")
                    ->orWhere('author','like',"%{$term}%")
                    ->orWhere('description','like',"%{$term}%");
            })->limit(20)->get(),

            'workplans'=>fn()=>Workplan::where(function($q) use($term){
                $q->where('title','like',"%{$term}%")
                    ->orWhere('description','like',"%{$term}%");
            })->limit(20)->get(),

            'activities'=>fn()=>Activity::where(function($q) use($term){
                $q->where('title','like',"%{$term}%")
                    ->orWhere('activity_code','like',"%{$term}%");
            })->limit(20)->get(),

            'assets'=>fn()=>Asset::where(function($q) use($term){
                $q->where('asset_code','like',"%{$term}%")
                    ->orWhere('asset_tag','like',"%{$term}%")
                    ->orWhere('serial_number','like',"%{$term}%")
                    ->orWhere('description','like',"%{$term}%");
            })->limit(20)->get(),

            'purchase_requests'=>fn()=>PurchaseRequest::where(function($q) use($term){
                $q->where('request_number','like',"%{$term}%")
                    ->orWhere('justification','like',"%{$term}%");
            })->limit(20)->get(),
        ];

        if($category!=='all' && isset($queries[$category])){
            return [$category=>$queries[$category]()];
        }

        return collect($queries)->map(fn($callback)=>$callback())->all();
    }
}
