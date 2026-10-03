<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Generates human-readable, unique codes such as PRG-2026-001 / PRJ-2026-001.
 */
class CodeGenerator
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function next(string $prefix, string $modelClass, string $column = 'code'): string
    {
        $base = strtoupper($prefix).'-'.now()->year.'-';

        $last = $modelClass::query()
            ->where($column, 'like', $base.'%')
            ->orderByDesc($column)
            ->value($column);

        $sequence = $last ? ((int) substr($last, strlen($base))) + 1 : 1;

        do {
            $code = $base.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            $sequence++;
        } while ($modelClass::query()->where($column, $code)->exists());

        return $code;
    }
}
