<?php

namespace App\Support\Export;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder as EloquentBuilderContract;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Describes one tabular export: a title, the columns (label => accessor),
 * the rows source (query or iterable), and the filters that were applied.
 *
 * Accessors may be a dot-notation attribute path ("course.title") or a
 * closure receiving the row (and its zero-based index).
 */
class TableExport
{
    public const DEFAULT_PDF_LIMIT = 2000;

    /** @var array<string, string|Closure> */
    protected array $columns = [];

    /** @var array<string, string> */
    protected array $filters = [];

    protected mixed $source = [];

    protected ?string $filename = null;

    protected ?string $subtitle = null;

    protected int $pdfLimit = self::DEFAULT_PDF_LIMIT;

    protected int $chunkSize = 500;

    public function __construct(protected string $title)
    {
    }

    public static function make(string $title): static
    {
        return new static($title);
    }

    /**
     * @param  array<string, string|Closure>  $columns
     */
    public function columns(array $columns): static
    {
        foreach ($columns as $label => $accessor) {
            if (! is_string($accessor) && ! $accessor instanceof Closure) {
                throw new InvalidArgumentException("Column [{$label}] must use a string path or a closure.");
            }
        }

        $this->columns = $columns;

        return $this;
    }

    /**
     * @param  EloquentBuilder|QueryBuilder|Relation|iterable<mixed>  $source
     */
    public function source(mixed $source): static
    {
        if (! is_iterable($source)
            && ! $source instanceof EloquentBuilderContract
            && ! $source instanceof QueryBuilder) {
            throw new InvalidArgumentException('Export source must be a query builder, relation or iterable.');
        }

        $this->source = $source;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $filters  label => value (empty values are dropped)
     */
    public function filters(array $filters): static
    {
        $this->filters = [];

        foreach ($filters as $label => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_filter(array_map('strval', $value), fn ($v) => $v !== ''));
            }
            $value = trim((string) ($value instanceof \BackedEnum ? $value->value : $value));
            if ($value !== '') {
                $this->filters[(string) $label] = $value;
            }
        }

        return $this;
    }

    public function filename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function subtitle(?string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function pdfLimit(int $limit): static
    {
        $this->pdfLimit = max(1, $limit);

        return $this;
    }

    public function chunkSize(int $size): static
    {
        $this->chunkSize = max(1, $size);

        return $this;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    /** @return array<int, string> */
    public function headings(): array
    {
        return array_map('strval', array_keys($this->columns));
    }

    /** @return array<string, string> */
    public function appliedFilters(): array
    {
        return $this->filters;
    }

    public function getPdfLimit(): int
    {
        return $this->pdfLimit;
    }

    public function baseFilename(): string
    {
        $base = $this->filename ?: Str::slug($this->title) ?: 'export';

        return Str::slug(preg_replace('/\.(csv|pdf)$/i', '', $base)) ?: 'export';
    }

    public function filenameFor(string $extension): string
    {
        return $this->baseFilename().'-'.now()->format('Ymd_His').'.'.$extension;
    }

    /**
     * Lazily yield source rows. Queries are chunked so large result sets never
     * need to be loaded into memory at once (eager loads are preserved).
     *
     * @return iterable<int, mixed>
     */
    public function rows(): iterable
    {
        $source = $this->source;

        if ($source instanceof Relation) {
            $source = $source->getQuery();
        }

        if ($source instanceof EloquentBuilder) {
            return $source->lazy($this->chunkSize);
        }

        if ($source instanceof QueryBuilder) {
            return empty($source->orders) && empty($source->unionOrders)
                ? $source->cursor()
                : $source->lazy($this->chunkSize);
        }

        return $source;
    }

    /**
     * Resolve a single row into formatted cell strings, in column order.
     *
     * @return array<int, string>
     */
    public function mapRow(mixed $row, int $index = 0): array
    {
        $cells = [];

        foreach ($this->columns as $accessor) {
            $value = $accessor instanceof Closure
                ? $accessor($row, $index)
                : data_get($row, $accessor);

            $cells[] = static::format($value);
        }

        return $cells;
    }

    public static function format(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'Yes' : 'No',
            $value instanceof \DateTimeInterface => $value->format(
                $value->format('H:i:s') === '00:00:00' ? 'Y-m-d' : 'Y-m-d H:i'
            ),
            $value instanceof \BackedEnum => (string) $value->value,
            $value instanceof \UnitEnum => $value->name,
            is_array($value) => implode(', ', array_map(fn ($v) => static::format($v), $value)),
            $value instanceof \Illuminate\Support\Collection => implode(', ', $value->map(fn ($v) => static::format($v))->all()),
            is_object($value) && ! method_exists($value, '__toString') => json_encode($value) ?: '',
            default => trim((string) $value),
        };
    }
}
