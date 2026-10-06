<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value must be the name of an active entry in a managed list
 * (App\Models\Department, App\Models\FundingSource). $keep is a value the
 * record already holds and may keep even if it is no longer on the list.
 */
class ActiveListValue implements ValidationRule
{
    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    public function __construct(private string $model, private ?string $keep = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if ($this->keep !== null && $this->keep !== '' && (string) $value === $this->keep) {
            return;
        }

        $exists = $this->model::query()
            ->where('name', (string) $value)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            $fail('Choose a :attribute from the list.');
        }
    }
}
