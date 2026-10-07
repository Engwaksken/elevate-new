<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\NotificationDispatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationAdminController extends Controller
{
    use ExportsTables;
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

        if($format=$this->exportFormat($request)){
            return $this->exportTable($format,'Notification History',$query,[
                'Recipient'=>'user.name',
                'Recipient email'=>'user.email',
                'Type'=>'type',
                'Title'=>'title',
                'Message'=>'message',
                'Created'=>'created_at',
                'Read at'=>'read_at',
            ]);
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
            'canSend' => $request->user()->hasPermission('notifications.send'),
            'recipientUsers' => $request->user()->hasPermission('notifications.send')
                ? User::where('status', 'active')->orderBy('name')->limit(1000)->get(['id','name','email','user_type'])
                : collect(),
            'recipientRoles' => $request->user()->hasPermission('notifications.send')
                ? Role::orderBy('name')->get(['id','name','slug'])
                : collect(),
        ]);
    }

    public function send(Request $request, NotificationDispatcher $dispatcher)
    {
        $data = $request->validate([
            'title' => ['required','string','max:190'],
            'message' => ['required','string','max:5000'],
            'action_url' => ['nullable','url','max:2048'],
            'user_ids' => ['nullable','array','max:200'],
            'user_ids.*' => ['integer','distinct','exists:users,id'],
            'role_ids' => ['nullable','array','max:100'],
            'role_ids.*' => ['integer','distinct','exists:roles,id'],
        ]);

        $userIds = $data['user_ids'] ?? [];
        $roleIds = $data['role_ids'] ?? [];
        if ($userIds === [] && $roleIds === []) {
            throw ValidationException::withMessages([
                'recipients' => 'Select one or more users or roles. Notifications are never sent to everyone by default.',
            ]);
        }

        $directUsers = User::where('status', 'active')->whereKey($userIds)->get();
        $roleUsers = $roleIds === []
            ? collect()
            : User::where('status', 'active')
                ->whereHas('roles', fn ($query) => $query->whereIn('roles.id', $roleIds))
                ->get();
        $recipients = $directUsers->merge($roleUsers)->unique('id')->values();

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipients' => 'None of the selected users or roles has an active recipient.',
            ]);
        }

        $count = $dispatcher->notifyMany(
            $recipients,
            'account_admin_notice',
            $data['title'],
            $data['message'],
            $data['action_url'] ?? null,
            [
                'sent_by' => $request->user()->id,
                'recipient_user_ids' => $recipients->pluck('id')->all(),
                'recipient_role_ids' => $roleIds,
            ]
        );

        return redirect()->route('admin.notifications.index')
            ->with('success', "Notification sent to {$count} selected user(s).");
    }
}
