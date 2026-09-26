<?php
namespace App\Http\Middleware;
use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
class DynamicMaintenanceMode {
    public function handle(Request $request,Closure $next){
        $settings=app(SettingsService::class);
        if(!$settings->get('maintenance.enabled',false)) return $next($request);
        $user=$request->user();
        if($user && method_exists($user,'isSuperAdmin') && $user->isSuperAdmin()) return $next($request);
        if($request->is('admin/*') || $request->is('login') || $request->is('admin/login')) return $next($request);
        return response()->view('maintenance',[
            'title'=>$settings->get('maintenance.title','Scheduled Maintenance'),
            'message'=>$settings->get('maintenance.message','We will be back shortly.'),
            'returnAt'=>$settings->get('maintenance.return_at')
        ],503);
    }
}