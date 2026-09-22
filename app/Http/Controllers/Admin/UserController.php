<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles')->latest();

        if ($search = trim((string)$request->get('search'))) {
            $query->where(fn($q) => $q->where('name','like',"%{$search}%")
                ->orWhere('email','like',"%{$search}%")
                ->orWhere('phone','like',"%{$search}%"));
        }

        if ($type = $request->get('user_type')) {
            $query->where('user_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => new User(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'user_type' => $data['user_type'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($data['roles'] ?? []);
        $audit->log('users', 'created', $user, [], $user->load('roles')->toArray());

        return redirect()->route('admin.users.index')->with('success','User created.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user, AuditService $audit)
    {
        $old = $user->load('roles')->toArray();
        $data = $this->validated($request, $user);

        $user->update([
            'name'=>$data['name'],
            'email'=>strtolower($data['email']),
            'phone'=>$data['phone'] ?? null,
            'user_type'=>$data['user_type'],
            'status'=>$data['status'],
        ]);

        if (!empty($data['password'])) {
            $user->update(['password'=>Hash::make($data['password'])]);
        }

        $user->roles()->sync($data['roles'] ?? []);
        $audit->log('users', 'updated', $user, $old, $user->fresh('roles')->toArray());

        return redirect()->route('admin.users.index')->with('success','User updated.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name'=>['required','string','max:190'],
            'email'=>['required','email','max:190', Rule::unique('users','email')->ignore($user?->id)],
            'phone'=>['nullable','string','max:30'],
            'user_type'=>['required','in:participant,staff'],
            'status'=>['required','in:active,inactive,suspended,pending'],
            'password'=>[$user ? 'nullable' : 'required','confirmed',Password::min(8)->mixedCase()->numbers()],
            'roles'=>['nullable','array'],
            'roles.*'=>['integer','exists:roles,id'],
        ]);
    }
}
