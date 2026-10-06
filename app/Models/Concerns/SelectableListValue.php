<?php

namespace App\Models\Concerns;

use App\Rules\ActiveListValue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A managed pick-list (departments, funding sources). Other records store
 * the chosen *name* as text, so old free-text values keep displaying even
 * when they are not on the list.
 *
 * Using models define textReferences(): [table => column] holding the name,
 * and may override idReferences(): [table => column] holding the id.
 */
trait SelectableListValue
{
    /** @return array<string, string> */
    abstract public static function textReferences(): array;

    /** @return array<string, string> */
    public static function idReferences(): array
    {
        return [];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Active names for a select, alphabetically. */
    public static function activeNames(): Collection
    {
        return static::query()->active()->orderBy('name')->pluck('name');
    }

    /**
     * Validation for a field that stores the name: only active list values,
     * except the record's own current value ($keep) so older records that
     * hold a value no longer on the list can still be saved unchanged.
     */
    public static function nameRules(?string $keep = null): array
    {
        return ['nullable', 'string', 'max:190', new ActiveListValue(static::class, $keep)];
    }

    /** How many records point at this value. */
    public function usageCount(): int
    {
        $count = 0;
        foreach (static::textReferences() as $table => $column) {
            $count += DB::table($table)->where($column, $this->name)->count();
        }
        foreach (static::idReferences() as $table => $column) {
            $count += DB::table($table)->where($column, $this->getKey())->count();
        }

        return $count;
    }

    public function isInUse(): bool
    {
        return $this->usageCount() > 0;
    }

    /** Keep stored text in step when the name changes. */
    public function renameReferences(string $from, string $to): void
    {
        if ($from === $to) {
            return;
        }
        foreach (static::textReferences() as $table => $column) {
            DB::table($table)->where($column, $from)->update([$column => $to]);
        }
    }
}
