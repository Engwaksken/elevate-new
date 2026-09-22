<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.roles.index', [
            'roles' => Role::withCount(['users','permissions'])->orderBy('name')->paginate(20),
        ]);
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('module')->orderBy('name')->get()->groupBy('module'),
        ]);
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
            $role->update(['name' => $data['name']]);
        }

        $role->permissions()->sync($data['permissions'] ?? []);
        $audit->log('roles', 'permissions_updated', $role, $old, $role->fresh('permissions')->toArray());

        return back()->with('success', 'Role permissions updated.');
    }
}
