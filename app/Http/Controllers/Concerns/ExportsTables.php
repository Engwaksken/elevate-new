<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Export\ExportFilterLabels;
use App\Support\Export\TableExport;
use App\Support\Export\TableExporter;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets a list action answer `?export=csv|pdf` with the same filtered query
 * it displays (pagination ignored). Typical use inside index():
 *
 *     if ($format = $this->exportFormat($request)) {
 *         return $this->exportTable($format, 'Branches', $query, [
 *             'Name' => 'name',
 *             'Status' => fn ($b) => $b->is_active ? 'Active' : 'Inactive',
 *         ]);
 *     }
 */
trait ExportsTables
{
    protected function exportFormat(Request $request): ?string
    {
        $format = strtolower((string) $request->query('export', ''));

        return TableExporter::isSupported($format) ? $format : null;
    }

    /**
     * @param  array<string, string|Closure>  $columns
     * @param  array<string, mixed>|null  $filters  label => value; null derives them from the query string
     * @param  array<string, string>  $filterLabels  query key => friendly label (used when deriving)
     */
    protected function exportTable(
        string $format,
        string $title,
        mixed $source,
        array $columns,
        ?array $filters = null,
        array $filterLabels = [],
        ?Closure $configure = null,
    ): Response {
        $export = TableExport::make($title)
            ->columns($columns)
            ->source($source)
            ->filters($filters ?? $this->exportFiltersFromRequest(request(), $filterLabels));

        if ($configure) {
            $configure($export);
        }

        return app(TableExporter::class)->download($export, $format);
    }

    /**
     * Turn the current query string into "Label => value" pairs for the export header.
     *
     * @param  array<string, string>  $labels
     * @return array<string, mixed>
     */
    protected function exportFiltersFromRequest(Request $request, array $labels = []): array
    {
        $ignored = ['export', 'page', 'per_page', '_token'];
        $filters = [];

        foreach ($request->query() as $key => $value) {
            if (in_array($key, $ignored, true) || str_ends_with((string) $key, '_page')) {
                continue;
            }
            if ($value === null || $value === '' || $value === []) {
                continue;
            }
            $key = (string) $key;
            $filters[$labels[$key] ?? ExportFilterLabels::label($key)] = ExportFilterLabels::value($key, $value);
        }

        return $filters;
    }
}
