<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ExecutiveDashboardService;

class ExecutiveDashboardController extends Controller
{
    public function index(ExecutiveDashboardService $service)
    {
        return view('admin.reports.executive-dashboard',[
            'stats'=>$service->summary()
        ]);
    }
}
