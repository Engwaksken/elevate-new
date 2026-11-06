<?php

namespace App\Http\Controllers\Admin\MasterLists;

use App\Models\Department;
use App\Models\User;
use App\Models\Branch;
use App\Services\AuditService;
use App\Support\MasterListAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return Department::query()->with(['head:id,name', 'branches:id,name']);
    }

    protected function viewData(): array
    {
        return [
            'staff' => User::where('user_type', 'staff')->where('status', 'active')->orderBy('name')->get(['id', 'name']),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function rules(?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:190', Rule::unique('departments', 'name')->ignore($record?->getKey())],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($record?->getKey())],
            'head_user_id' => ['nullable', Rule::exists('users', 'id')->where('user_type', 'staff')],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', 'distinct', Rule::exists('branches', 'id')->where('is_active', true)],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    public function store(Request $request, AuditService $audit)
    {
        $data = $this->validated($request, null);
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids']);

        $record = DB::transaction(function () use ($data, $branchIds) {
            $department = Department::create($data);
            $department->branches()->sync($branchIds);
            return $department;
        });

        $audit->log($record->getTable(), 'created', $record, [], $record->toArray() + ['branch_ids' => $branchIds]);
        return redirect()->route($this->routePrefix().'.index')->with('success', "Department \"{$record->name}\" added.");
    }

    public function update(Request $request, AuditService $audit)
    {
        $record = $this->record($request);
        $old = $record->toArray() + ['branch_ids' => $record->branches()->pluck('branches.id')->all()];
        $data = $this->validated($request, $record);
        $branchIds = $data['branch_ids'] ?? [];
        unset($data['branch_ids']);

        DB::transaction(function () use ($record, $data, $branchIds, $old) {
            $record->update($data);
            $record->branches()->sync($branchIds);
            $record->renameReferences($old['name'], $record->name);
        });

        $audit->log($record->getTable(), 'updated', $record, $old, $record->fresh()->toArray() + ['branch_ids' => $branchIds]);
        return redirect()->route($this->routePrefix().'.index')->with('success', "Department \"{$record->name}\" updated.");
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
