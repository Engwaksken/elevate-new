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
     * Human-readable field changes: [['field' => 'Status', 'old' => 'Draft', 'new' => 'Active'], ...].
     */
    public function changeRows(): array
    {
        $old = $this->normaliseValues($this->old_values);
        $new = $this->normaliseValues($this->new_values);
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $rows = [];
        foreach ($keys as $key) {
            // updated_at changes on every save and adds noise to update events.
            if ($key === 'updated_at' && $this->action === 'updated') {
                continue;
            }

            $rows[] = [
                'field' => $this->fieldLabel((string) $key),
                'old' => array_key_exists($key, $old) ? $this->formatValue($old[$key]) : null,
                'new' => array_key_exists($key, $new) ? $this->formatValue($new[$key]) : null,
            ];
        }

        return $rows;
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
