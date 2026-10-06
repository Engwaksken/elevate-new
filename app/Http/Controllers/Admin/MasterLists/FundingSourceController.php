<?php

namespace App\Http\Controllers\Admin\MasterLists;

use App\Models\FundingSource;
use App\Models\User;
use App\Support\MasterListAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Procurement, finance and administrators manage the funding source list. */
class FundingSourceController extends MasterListController
{
    protected static function model(): string
    {
        return FundingSource::class;
    }

    protected static function canManage(?User $user): bool
    {
        return MasterListAccess::canManageFundingSources($user);
    }

    protected function routePrefix(): string
    {
        return 'admin.funding-sources';
    }

    protected function view(): string
    {
        return 'admin.master-lists.funding-sources';
    }

    protected function label(): string
    {
        return 'Funding source';
    }

    protected function searchColumns(): array
    {
        return ['description'];
    }

    protected function rules(?Model $record): array
    {
        return [
            'name' => ['required', 'string', 'max:190', Rule::unique('funding_sources', 'name')->ignore($record?->getKey())],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('funding_sources', 'code')->ignore($record?->getKey())],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function exportColumns(): array
    {
        return [
            'Funding Source' => 'name',
            'Code' => 'code',
            'Description' => 'description',
            'Status' => fn ($f) => $f->is_active ? 'Active' : 'Inactive',
            'Created' => 'created_at',
        ];
    }
}
