<?php

namespace App\Http\Controllers\Admin\MasterLists;

use App\Models\Department;
use App\Models\User;
use App\Support\MasterListAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** HR / administrators manage the department list staff pick from. */
class DepartmentController extends MasterListController
{
    protected static function model(): string
    {
        return Department::class;
    }

    protected static function canManage(?User $user): bool
    {
        return MasterListAccess::canManageDepartments($user);
    }

    protected function routePrefix(): string
    {
        return 'admin.departments';
    }

    protected function view(): string
    {
        return 'admin.master-lists.departments';
    }

    protected function label(): string
    {
        return 'Department';
    }

    protected function indexQuery(): Builder
    {
        return Department::query()->with('head:id,name');
    }

    protected function viewData(): array
    {
        return [
            'staff' => User::where('user_type', 'staff')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function rules(?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:190', Rule::unique('departments', 'name')->ignore($record?->getKey())],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($record?->getKey())],
            'head_user_id' => ['nullable', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function exportColumns(): array
    {
        return [
            'Department' => 'name',
            'Code' => 'code',
            'Head' => 'head.name',
            'Status' => fn ($d) => $d->is_active ? 'Active' : 'Inactive',
            'Created' => 'created_at',
        ];
    }
}
