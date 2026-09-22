<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Services\JobRecommendationService;

class JobRecommendationController extends Controller
{
    public function index(JobRecommendationService $service)
    {
        return view('jobs.recommendations', [
            'jobs'=>$service->recommend(auth()->user()),
        ]);
    }
}
