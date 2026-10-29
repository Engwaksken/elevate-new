<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Applies a "period" filter (today / week / month / year / custom from-to) to a date column,
 * reading prefixed query parameters so several filtered lists can share one page.
 */
class PeriodFilter
{
    public const OPTIONS = ['' => 'All time', 'today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'year' => 'This year', 'custom' => 'Custom range'];

    public static function apply(Builder $query, string $column, Request $request, string $prefix = ''): Builder
    {
        $now = now();
        [$from, $to] = match ($request->query($prefix.'period')) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'custom' => [self::date($request->query($prefix.'from'))?->startOfDay(), self::date($request->query($prefix.'to'))?->endOfDay()],
            default => [null, null],
        };

        return $query
            ->when($from, fn ($q) => $q->where($column, '>=', $from))
            ->when($to, fn ($q) => $q->where($column, '<=', $to));
    }

    private static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }
    }
}
