@props([
    // Optional base URL to export from; defaults to the current page so filters/search carry over.
    'url' => null,
    // Extra query parameters to merge (e.g. ['tab' => 'participants']).
    'params' => [],
    // Which formats to offer.
    'formats' => ['csv', 'pdf'],
    'size' => 'sm',
])
@php
    $exportBase = $url ?? request()->url();
    $exportQuery = array_merge(
        $url ? [] : \Illuminate\Support\Arr::except(request()->query(), ['page', 'export']),
        $params,
    );
    $exportHref = function (string $format) use ($exportBase, $exportQuery) {
        $query = http_build_query(array_merge($exportQuery, ['export' => $format]));
        return $exportBase.(str_contains($exportBase, '?') ? '&' : '?').$query;
    };
    $exportBtnClass = 'btn btn-outline'.($size === 'sm' ? ' btn-sm' : '');
@endphp
<div {{ $attributes->merge(['class' => 'eh-export-buttons', 'style' => 'display:inline-flex;gap:6px;flex-wrap:wrap;align-items:center']) }} role="group" aria-label="Export">
    @if(in_array('csv', $formats, true))
        <a href="{{ $exportHref('csv') }}" class="{{ $exportBtnClass }}" rel="nofollow" title="Download the current view as CSV"><i class="fas fa-file-csv"></i> CSV</a>
    @endif
    @if(in_array('pdf', $formats, true))
        <a href="{{ $exportHref('pdf') }}" class="{{ $exportBtnClass }}" rel="nofollow" title="Download the current view as PDF"><i class="fas fa-file-pdf"></i> PDF</a>
    @endif
</div>
