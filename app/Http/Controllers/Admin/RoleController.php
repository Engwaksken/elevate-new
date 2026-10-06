<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
{
    use ExportsTables;
    public function index(Request $request)
    {
        $query = Role::with(['permissions'])
            ->withCount(['users','permissions'])
            ->orderBy('name');

        if ($search = trim((string) $request->get('search'))) {
            $query->where('name','like',"%{$search}%");
        }

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, 'Roles & Permissions', $query, [
                'Role' => 'name',
                'Type' => fn ($r) => $r->is_system ? 'System' : 'Custom',
                'Users' => 'users_count',
                'Permissions' => 'permissions_count',
                'Permission list' => fn ($r) => $r->permissions->pluck('name')->implode(', '),
            ]);
        }

        $perPage = in_array(
            (int) $request->get('per_page'),
            [10,20,25,50,100],
            true
        )
            ? (int) $request->get('per_page')
            : 20;

        return view('admin.roles.index', [
            'roles' => $query->paginate($perPage)->withQueryString(),
            'permissions' => Permission::orderBy('module')
                ->orderBy('name')
                ->get()
                ->groupBy('module'),
            'stats' => [
                'roles' => Role::count(),
                'system_roles' => Role::where('is_system',true)->count(),
                'custom_roles' => Role::where('is_system',false)->count(),
                'permissions' => Permission::count(),
            ],
        ]);
    }

    public function edit(Role $role)
    {
        return redirect()
            ->route('admin.roles.index')
            ->with('open_role_modal','edit-'.$role->id);
    }

    public function update(Request $request, Role $role, AuditService $audit)
    {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'permissions' => ['nullable','array'],
            'permissions.*' => ['integer','exists:permissions,id'],
        ]);

        $old = $role->load('permissions')->toArray();

        if (! $role->is_system) {
            $role->update([
                'name' => $data['name'],
            ]);
        }

        if (in_array($role->slug, ['super-administrator','super-admin'], true)) {
            $allPermissionIds = Permission::pluck('id')->all();

            if (
                array_diff($allPermissionIds, $data['permissions'] ?? [])
                || array_diff($data['permissions'] ?? [], $allPermissionIds)
            ) {
                throw ValidationException::withMessages([
                    'permissions' => 'Super Administrator roles must retain all system permissions.',
                ]);
            }
        }

        $role->permissions()->sync($data['permissions'] ?? []);

        // Let everyone holding this role know their access may have changed.
        $holders = $role->users()->get()->reject(fn ($user) => (int) $user->id === (int) auth()->id());
        app(\App\Services\NotificationDispatcher::class)->notifyMany(
            $holders,
            'role_permissions',
            'Your role permissions were updated',
            "An administrator updated the permissions for the '{$role->name}' role. Review what you can access.",
            '/notifications',
            ['role_id' => $role->id]
        );

        $audit->log(
            'roles',
            'permissions_updated',
            $role,
            $old,
            $role->fresh('permissions')->toArray()
        );

        return back()->with('success','Role permissions updated.');
    }
}
