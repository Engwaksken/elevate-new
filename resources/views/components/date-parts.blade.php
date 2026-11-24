@props([
    'name',
    'value' => null,          // Y-m-d, a Carbon date, or null
    'label' => 'Date',
    'minYear' => now()->year - 100,
    'maxYear' => now()->year,
])

{{--
    Three dropdowns (day, month, year) instead of a calendar picker, so a date
    many years back is quick to choose. App\Support\DateParts::merge() joins
    them into "{name}" before validation.
--}}
@php
    $date = $value instanceof \DateTimeInterface
        ? $value
        : (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}/', $value) ? \Illuminate\Support\Carbon::parse(substr($value, 0, 10)) : null);

    $day = old($name.'_day', $date?->format('j'));
    $month = old($name.'_month', $date?->format('n'));
    $year = old($name.'_year', $date?->format('Y'));
    $id = $attributes->get('id', $name);
@endphp

<fieldset {{ $attributes->except('id')->merge(['class' => 'eh-date-parts']) }}>
    <legend class="sr-only">{{ $label }}</legend>
    <label for="{{ $id }}" class="sr-only">Day</label>
    <select id="{{ $id }}" name="{{ $name }}_day" autocomplete="bday-day">
        <option value="">Day</option>
        @for($d = 1; $d <= 31; $d++)
            <option value="{{ $d }}" @selected((string) $day === (string) $d)>{{ $d }}</option>
        @endfor
    </select>

    <label for="{{ $id }}_month" class="sr-only">Month</label>
    <select id="{{ $id }}_month" name="{{ $name }}_month" autocomplete="bday-month">
        <option value="">Month</option>
        @foreach(range(1, 12) as $m)
            <option value="{{ $m }}" @selected((string) $month === (string) $m)>{{ \Illuminate\Support\Carbon::create(2000, $m, 1)->format('F') }}</option>
        @endforeach
    </select>

    <label for="{{ $id }}_year" class="sr-only">Year</label>
    <select id="{{ $id }}_year" name="{{ $name }}_year" autocomplete="bday-year">
        <option value="">Year</option>
        @for($y = $maxYear; $y >= $minYear; $y--)
            <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}</option>
        @endfor
    </select>
</fieldset>
