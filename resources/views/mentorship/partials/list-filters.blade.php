{{-- Search + period filter for one tab of the mentorship dashboard. Expects $prefix, $tab, $placeholder; optional $periodLabel and $statuses (value => label). --}}
@php($period = request($prefix.'period'))
<form method="GET" action="{{ route('mentorship.dashboard') }}#{{ $tab }}" class="admin-toolbar" data-period-form>
    {{-- Keep the other tabs' filters when this one changes. --}}
    @foreach(request()->except([$prefix.'q', $prefix.'period', $prefix.'from', $prefix.'to', $prefix.'status', $prefix.'page']) as $name => $value)
        @if(is_scalar($value))<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endif
    @endforeach
    <input type="search" name="{{ $prefix }}q" value="{{ request($prefix.'q') }}" placeholder="{{ $placeholder }}" aria-label="Search">
    <select name="{{ $prefix }}period" aria-label="{{ $periodLabel ?? 'Period' }}" data-period-select>
        @foreach(\App\Support\PeriodFilter::OPTIONS as $value => $label)
            <option value="{{ $value }}" @selected($period === $value)>{{ $value === '' ? 'All time' : ($periodLabel ?? '').($periodLabel ? ': ' : '').$label }}</option>
        @endforeach
    </select>
    <input type="date" name="{{ $prefix }}from" value="{{ request($prefix.'from') }}" aria-label="From date" data-period-custom @unless($period === 'custom') hidden @endunless>
    <input type="date" name="{{ $prefix }}to" value="{{ request($prefix.'to') }}" aria-label="To date" data-period-custom @unless($period === 'custom') hidden @endunless>
    @isset($statuses)
        <select name="{{ $prefix }}status" aria-label="Status">
            @foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(request($prefix.'status', '') === $value)>{{ $label }}</option>@endforeach
        </select>
    @endisset
    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
    <a class="btn btn-outline btn-sm" href="{{ route('mentorship.dashboard', request()->except([$prefix.'q', $prefix.'period', $prefix.'from', $prefix.'to', $prefix.'status', $prefix.'page'])) }}#{{ $tab }}">Reset</a>
</form>
