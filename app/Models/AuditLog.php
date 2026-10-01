<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps=false;

    protected $fillable=[
        'user_id',
        'module',
        'action',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'occurred_at',
    ];

    protected $casts=[
        'old_values'=>'array',
        'new_values'=>'array',
        'occurred_at'=>'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Human-readable field changes.
     *
     * Scalar fields: ['type' => 'value', 'field' => 'Status', 'old' => 'Draft', 'new' => 'Active'].
     * Record lists (e.g. a role's permissions): ['type' => 'list', 'field' => 'Permissions',
     * 'added' => [...], 'removed' => [...], 'unchanged' => [...]] where each item is
     * ['label' => 'View Reports', 'group' => 'Reports'].
     */
    public function changeRows(): array
    {
        $old = $this->normaliseValues($this->old_values);
        $new = $this->normaliseValues($this->new_values);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $comparing = $old !== [] && $new !== [];

        $rows = [];
        foreach ($keys as $key) {
            // Timestamps change on every save and add noise to update events.
            if (in_array($key, ['updated_at', 'created_at'], true) && $comparing) {
                continue;
            }

            $hasOld = array_key_exists($key, $old);
            $hasNew = array_key_exists($key, $new);

            // Snapshot-style logs (before/after toArray()) repeat untouched fields.
            if ($comparing && $hasOld && $hasNew && $old[$key] == $new[$key] && ! $this->isRecordList($old[$key])) {
                continue;
            }

            if ($this->isRecordList($old[$key] ?? null) || $this->isRecordList($new[$key] ?? null)) {
                $row = $this->listRow((string) $key, $hasOld ? $old[$key] : null, $hasNew ? $new[$key] : null);

                if ($comparing && $row['added'] === [] && $row['removed'] === []) {
                    continue;
                }

                $rows[] = $row;

                continue;
            }

            $rows[] = [
                'type' => 'value',
                'field' => $this->fieldLabel((string) $key),
                'old' => $hasOld ? $this->formatValue($old[$key]) : null,
                'new' => $hasNew ? $this->formatValue($new[$key]) : null,
            ];
        }

        return $rows;
    }

    /**
     * A non-empty list of related records, e.g. [['id' => 1, 'name' => 'View Reports', ...], ...].
     */
    private function isRecordList(mixed $value): bool
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            return false;
        }

        foreach ($value as $item) {
            if (! is_array($item) || $this->recordLabel($item) === null) {
                return false;
            }
        }

        return true;
    }

    private function listRow(string $key, mixed $old, mixed $new): array
    {
        $oldItems = $this->listItems($old);
        $newItems = $this->listItems($new);

        $sort = fn (array $items) => collect($items)
            ->sortBy(fn ($item) => ($item['group'] ?? '').'|'.$item['label'], SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return [
            'type' => 'list',
            'field' => $this->fieldLabel($key),
            'added' => $sort(array_diff_key($newItems, $oldItems)),
            'removed' => $sort(array_diff_key($oldItems, $newItems)),
            'unchanged' => $sort(array_intersect_key($newItems, $oldItems)),
        ];
    }

    /**
     * @return array<string, array{label: string, group: ?string}> keyed by record identity
     */
    private function listItems(mixed $value): array
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $items = [];
        foreach (is_array($value) ? $value : [] as $item) {
            $label = is_array($item) ? $this->recordLabel($item) : null;
            if ($label === null) {
                continue;
            }

            $identity = (string) ($item['id'] ?? $item['slug'] ?? $label);
            $group = $item['module'] ?? $item['group'] ?? $item['category'] ?? null;

            $items[$identity] = [
                'label' => $label,
                'group' => is_scalar($group) && $group !== '' ? ucwords(str_replace(['_', '-', '.'], ' ', (string) $group)) : null,
            ];
        }

        return $items;
    }

    private function recordLabel(array $record): ?string
    {
        foreach (['name', 'title', 'label', 'slug'] as $field) {
            if (isset($record[$field]) && is_scalar($record[$field]) && $record[$field] !== '') {
                return (string) $record[$field];
            }
        }

        return null;
    }

    private function normaliseValues(mixed $values): array
    {
        if (is_string($values)) {
            $decoded = json_decode($values, true);
            $values = is_array($decoded) ? $decoded : ['value' => $values];
        }

        return is_array($values) ? $values : [];
    }

    private function fieldLabel(string $key): string
    {
        $key = preg_replace('/_(id|at)$/', '', $key) ?: $key;

        return ucwords(str_replace(['_', '.'], ' ', $key));
    }

    private function formatValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            return collect($value)
                ->map(fn ($item, $key) => (is_int($key) ? '' : $this->fieldLabel((string) $key).': ')
                    .(is_scalar($item) || $item === null ? $this->formatValue($item) : json_encode($item, JSON_UNESCAPED_SLASHES)))
                ->implode(', ');
        }

        $string = (string) $value;

        if ($string === '[redacted]') {
            return 'Hidden';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2}(:\d{2})?)?/', $string)) {
            try {
                $date = \Illuminate\Support\Carbon::parse($string);

                return strlen($string) <= 10 ? $date->format('d M Y') : $date->format('d M Y H:i');
            } catch (\Throwable) {
                // Not a date after all; fall through.
            }
        }

        if (preg_match('/^[a-z]+(_[a-z]+)+$/', $string)) {
            return ucwords(str_replace('_', ' ', $string));
        }

        return \Illuminate\Support\Str::limit($string, 300);
    }
}
