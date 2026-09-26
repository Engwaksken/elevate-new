<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query=UserNotification::where('user_id',auth()->id())->latest();

        if($status=$request->get('status')){
            if($status==='unread') $query->whereNull('read_at');
            if($status==='read') $query->whereNotNull('read_at');
        }

        return view('notifications.index',[
            'notifications'=>$query->paginate(20)->withQueryString(),
            'unreadCount'=>UserNotification::where('user_id',auth()->id())
                ->whereNull('read_at')->count(),
        ]);
    }

    public function markRead(UserNotification $notification)
    {
        abort_unless((int)$notification->user_id === (int)auth()->id(),403);

        $notification->markAsRead();

        if($notification->action_url){
            return redirect()->to($notification->action_url);
        }

        return back()->with('success','Notification marked as read.');
    }

    public function markAllRead()
    {
        UserNotification::where('user_id',auth()->id())
            ->whereNull('read_at')
            ->update(['read_at'=>now()]);

        return back()->with('success','All notifications marked as read.');
    }
}
