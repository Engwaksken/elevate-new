<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query=AuditLog::with('user')->latest('occurred_at');

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('module','like',"%{$search}%")
                    ->orWhere('action','like',"%{$search}%")
                    ->orWhere('auditable_type','like',"%{$search}%")
                    ->orWhere('ip_address','like',"%{$search}%")
                    ->orWhereHas('user',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($module=$request->get('module')) $query->where('module',$module);
        if($action=$request->get('action')) $query->where('action',$action);

        if($request->filled('from')){
            $query->whereDate('occurred_at','>=',$request->date('from'));
        }

        if($request->filled('to')){
            $query->whereDate('occurred_at','<=',$request->date('to'));
        }

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.audit-logs.index',[
            'logs'=>$query->paginate($perPage)->withQueryString(),
            'modules'=>AuditLog::query()
                ->whereNotNull('module')
                ->select('module')->distinct()->orderBy('module')->pluck('module'),
            'actions'=>AuditLog::query()
                ->select('action')->distinct()->orderBy('action')->pluck('action'),
            'stats'=>[
                'total'=>AuditLog::count(),
                'today'=>AuditLog::whereDate('occurred_at',today())->count(),
                'users'=>AuditLog::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
                'modules'=>AuditLog::whereNotNull('module')->distinct('module')->count('module'),
            ],
        ]);
    }
}
