<?php

namespace App\Http\Controllers\Admin\Mentorship;

use App\Http\Controllers\Controller;
use App\Models\MenteeProfile;
use App\Services\MentorRecommendationService;

class MentorRecommendationController extends Controller
{
    public function show(MenteeProfile $mentee, MentorRecommendationService $service)
    {
        return view('admin.mentorship.recommendations', [
            'mentee'=>$mentee->load('user'),
            'recommendations'=>$service->recommend($mentee),
        ]);
    }
}
