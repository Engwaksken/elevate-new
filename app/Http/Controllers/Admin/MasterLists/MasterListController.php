<?php

namespace App\Http\Controllers\Admin\MasterLists;

use App\Http\Controllers\Admin\Concerns\BulkDeletesRecords;
use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

/**
 * Shared add / edit / (de)activate / delete-when-unused screen for the
 * managed pick-lists (departments, funding sources). Records elsewhere
 * store the chosen name as text, so renames are copied onto them and
 * values that are in use cannot be deleted (deactivate them instead).
 */
abstract class MasterListController extends Controller implements HasMiddleware
{
    use BulkDeletesRecords;
    use ExportsTables;

    /** @return class-string<Model> */
    abstract protected static function model(): string;

    abstract protected static function canManage(?User $user): bool;

    /** Route name prefix, e.g. "admin.departments". */
    abstract protected function routePrefix(): string;

    abstract protected function view(): string;

    /** Singular label, e.g. "Department". */
    abstract protected function label(): string;

    abstract protected function rules(?Model $record): array;

    /** @return array<string, string|Closure> */
    abstract protected function exportColumns(): array;

    /** @return array<int, string> extra columns searched besides name/code */
    protected function searchColumns(): array
    {
        return [];
    }

    protected function viewData(): array
    {
        return [];
    }

    protected function indexQuery(): Builder
    {
        return static::model()::query();
    }

    public static function middleware(): array
    {
        return [
            new Middleware(function (Request $request, Closure $next) {
                abort_unless(static::canManage($request->user()), 403, 'You do not have permission to access this page.');

                return $next($request);
            }),
        ];
    }

    protected function bulkDeleteModel(): string
    {
        return static::model();
    }

    /** Values in use are never removed in bulk. */
    protected function bulkDeleteExcludedIds(array $ids): array
    {
        return static::model()::query()->whereKey($ids)->get()
            ->filter(fn (Model $record) => $record->isInUse())
            ->modelKeys();
    }

    public function index(Request $request)
    {
        $model = static::model();
        $query = $this->indexQuery();

        if ($search = trim((string) $request->get('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
                foreach ($this->searchColumns() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if (in_array($status = $request->get('status'), ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }

        $query->orderBy('name');

        if ($format = $this->exportFormat($request)) {
            return $this->exportTable($format, str($this->label())->plural()->toString(), $query, $this->exportColumns());
        }

        $perPage = in_array((int) $request->get('per_page'), [10, 20, 25, 50, 100], true) ? (int) $request->get('per_page') : 25;
        $records = $query->paginate($perPage)->withQueryString();
        $records->getCollection()->each(fn (Model $record) => $record->setAttribute('usage_count', $record->usageCount()));

        return view($this->view(), [
            'records' => $records,
            'stats' => [
                'total' => $model::count(),
                'active' => $model::where('is_active', true)->count(),
                'inactive' => $model::where('is_active', false)->count(),
            ],
        ] + $this->viewData());
    }

    public function store(Request $request, AuditService $audit)
    {
        $record = static::model()::create($this->validated($request, null));
        $audit->log($record->getTable(), 'created', $record, [], $record->toArray());

        return redirect()->route($this->routePrefix().'.index')->with('success', "{$this->label()} \"{$record->name}\" added.");
    }

    public function update(Request $request, AuditService $audit)
    {
        $record = $this->record($request);
        $old = $record->toArray();
        $data = $this->validated($request, $record);

        DB::transaction(function () use ($record, $data, $old) {
            $record->update($data);
            $record->renameReferences($old['name'], $record->name);
        });

        $audit->log($record->getTable(), 'updated', $record, $old, $record->fresh()->toArray());

        return redirect()->route($this->routePrefix().'.index')->with('success', "{$this->label()} \"{$record->name}\" updated.");
    }

    public function toggle(Request $request, AuditService $audit)
    {
        $record = $this->record($request);
        $old = $record->toArray();
        $record->update(['is_active' => ! $record->is_active]);
        $audit->log($record->getTable(), 'updated', $record, $old, $record->fresh()->toArray());

        return back()->with('success', "{$this->label()} \"{$record->name}\" ".($record->is_active ? 'activated.' : 'deactivated — it no longer appears in forms.'));
    }

    public function destroy(Request $request, AuditService $audit)
    {
        $record = $this->record($request);

        if ($uses = $record->usageCount()) {
            return back()->with('error', "\"{$record->name}\" is used by {$uses} record(s) and cannot be deleted. Deactivate it instead.");
        }

        $old = $record->toArray();
        $record->delete();
        $audit->log($record->getTable(), 'deleted', null, $old, []);

        return back()->with('success', "{$this->label()} \"{$old['name']}\" deleted.");
    }

    /** The bound route model, whatever the parameter is called. */
    protected function record(Request $request): Model
    {
        $model = static::model();
        $value = collect($request->route()->parameters())->first();

        return $value instanceof $model ? $value : $model::findOrFail($value);
    }

    protected function validated(Request $request, ?Model $record): array
    {
        $request->merge([
            'name' => trim((string) $request->input('name')),
            'code' => ($code = trim((string) $request->input('code'))) === '' ? null : $code,
        ]);

        return $request->validate($this->rules($record)) + ['is_active' => $request->boolean('is_active')];
    }
}
