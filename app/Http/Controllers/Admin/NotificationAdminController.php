<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationAdminController extends Controller
{
    public function index(Request $request)
    {
        $query=UserNotification::with('user')->latest();

        if($search=trim((string)$request->get('search'))){
            $query->where(function($q) use($search){
                $q->where('title','like',"%{$search}%")
                    ->orWhere('message','like',"%{$search}%")
                    ->orWhere('type','like',"%{$search}%")
                    ->orWhereHas('user',fn($u)=>$u
                        ->where('name','like',"%{$search}%")
                        ->orWhere('email','like',"%{$search}%"));
            });
        }

        if($type=$request->get('type')) $query->where('type',$type);

        if($read=$request->get('read_status')){
            if($read==='unread') $query->whereNull('read_at');
            if($read==='read') $query->whereNotNull('read_at');
        }

        $perPage=in_array((int)$request->get('per_page'),[10,25,50,100],true)
            ? (int)$request->get('per_page') : 25;

        return view('admin.notifications.index',[
            'notifications'=>$query->paginate($perPage)->withQueryString(),
            'types'=>UserNotification::query()
                ->select('type')->distinct()->orderBy('type')->pluck('type'),
            'stats'=>[
                'total'=>UserNotification::count(),
                'unread'=>UserNotification::whereNull('read_at')->count(),
                'read'=>UserNotification::whereNotNull('read_at')->count(),
                'today'=>UserNotification::whereDate('created_at',today())->count(),
            ],
        ]);
    }
}
