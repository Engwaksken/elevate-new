<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrolment;
use App\Models\Programme;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard.index', [
            'stats'=>[
                'participants'=>User::where('user_type','participant')->count(),
                'staff'=>User::where('user_type','staff')->count(),
                'programmes'=>Programme::where('status','active')->count(),
                'courses'=>Course::where('status','published')->count(),
                'enrolments'=>Enrolment::count(),
                'completed'=>Enrolment::where('status','completed')->count(),
            ],
        ]);
    }
}
