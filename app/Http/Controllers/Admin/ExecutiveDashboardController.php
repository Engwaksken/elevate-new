<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request, ExecutiveDashboardService $service)
    {
        return view('admin.reports.executive-dashboard',[
            'stats'=>$service->summary(),
            'period'=>$request->get('period','all'),
        ]);
    }
}
