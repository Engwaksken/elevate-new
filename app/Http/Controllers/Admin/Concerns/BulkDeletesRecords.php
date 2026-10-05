<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Adds a generic "delete selected records" bulk action to an admin controller.
 *
 * The controller must implement bulkDeleteModel() (returning the model class)
 * and may override bulkDeleteExcludedIds() to protect specific records.
 */
trait BulkDeletesRecords
{
    /**
     * @return class-string<Model>
     */
    protected function bulkDeleteModel(): string
    {
        throw new \LogicException(static::class.' must implement bulkDeleteModel().');
    }

    /**
     * IDs that must never be removed in bulk (for example the current user).
     *
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    protected function bulkDeleteExcludedIds(array $ids): array
    {
        return [];
    }

    public function bulkDestroy(Request $request, AuditService $audit)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $class = $this->bulkDeleteModel();

        $ids = array_values(array_diff(
            array_map('intval', $data['ids']),
            $this->bulkDeleteExcludedIds($data['ids'])
        ));

        $records = $class::query()->whereKey($ids)->get();

        $count = 0;
        foreach ($records as $record) {
            $old = $record->toArray();
            $record->delete();
            $audit->log((new $class)->getTable(), 'deleted', null, $old, []);
            $count++;
        }

        return back()->with(
            $count > 0 ? 'success' : 'error',
            $count > 0 ? $count.' record(s) deleted.' : 'No records were deleted.'
        );
    }
}
