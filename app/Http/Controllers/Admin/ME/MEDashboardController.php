<?php

namespace App\Http\Controllers\Admin\ME;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrolment;
use App\Models\Indicator;
use App\Models\IndicatorResult;
use App\Models\MentorMatch;
use App\Models\ParticipantOutcome;
use App\Models\Result;
use App\Models\ResultsFramework;

class MEDashboardController extends Controller
{
    public function index()
    {
        return view('admin.me.dashboard',[
            'stats'=>[
                'active_indicators'=>Indicator::where('status','active')->count(),
                'pending_results'=>IndicatorResult::where('verification_status','submitted')->count(),
                'verified_outcomes'=>ParticipantOutcome::where('verification_status','verified')->count(),
                'completed_learners'=>Enrolment::where('status','completed')->count(),
                'certificates'=>Certificate::count(),
                'active_mentorships'=>MentorMatch::where('status','active')->count(),
                'results_frameworks'=>ResultsFramework::count(),
                'framework_results'=>Result::count(),
            ],
        ]);
    }
}
