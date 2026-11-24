<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Day / month / year dropdowns (see <x-date-parts>) post three fields:
 * {name}_day, {name}_month and {name}_year. This joins them into {name}
 * as Y-m-d before validation, so the usual 'date' rules still apply.
 */
class DateParts
{
    public static function merge(Request $request, string $name): void
    {
        $keys = [$name.'_day', $name.'_month', $name.'_year'];

        // Form without the dropdowns (e.g. the mobile app sends {name} directly).
        if (! collect($keys)->contains(fn ($key) => $request->has($key))) {
            return;
        }

        [$day, $month, $year] = array_map(fn ($key) => (int) $request->input($key), $keys);

        if (! $day && ! $month && ! $year) {
            $request->merge([$name => null]);

            return;
        }

        // A partly chosen or impossible date (e.g. 31 February) fails the 'date' rule.
        $request->merge([
            $name => ($day && $month && $year && checkdate($month, $day, $year))
                ? sprintf('%04d-%02d-%02d', $year, $month, $day)
                : 'incomplete',
        ]);
    }
}
