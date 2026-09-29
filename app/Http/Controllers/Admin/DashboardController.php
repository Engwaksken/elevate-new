<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Services\StaffDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
final class DashboardController extends Controller {
 public function __invoke(Request $request, StaffDashboardService $dashboard): View {
   $user=$request->user(); abort_unless($user,401);
   return view('admin.dashboard',$dashboard->for($user));
 }
}
