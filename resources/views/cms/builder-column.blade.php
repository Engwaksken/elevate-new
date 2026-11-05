@php
    $cpath = $cpath ?? '';
    $cw = $column['width'] ?? '1/1';
    $columnWidths = ['1/1' => 'Full width', '1/2' => '1/2', '1/3' => '1/3', '2/3' => '2/3', '1/4' => '1/4', '3/4' => '3/4', '1/6' => '1/6', '5/6' => '5/6'];
@endphp
<div class="cms-column" data-column>
    <div class="cms-column__head">
        <span class="cms-column__title"><i class="fas fa-columns"></i> Column</span>
        <div class="cms-column__actions">
            <select @if($cpath) name="{{ $cpath }}[width]" @endif data-column-width aria-label="Column width">
                @foreach($columnWidths as $value => $text)<option value="{{ $value }}" @selected($cw === $value)>{{ $text }}</option>@endforeach
            </select>
            <button type="button" class="btn-icon" title="Move up" data-column-move="up"><i class="fas fa-arrow-up"></i></button>
            <button type="button" class="btn-icon" title="Move down" data-column-move="down"><i class="fas fa-arrow-down"></i></button>
            <button type="button" class="btn-icon danger" title="Remove column" data-column-remove><i class="fas fa-trash"></i></button>
        </div>
    </div>
    <div class="cms-column__widgets" data-widgets>
        @foreach($column['widgets'] ?? [] as $w => $widget)
            @include('cms.builder-widget', ['widget' => $widget, 'wpath' => $cpath ? "{$cpath}[widgets][{$w}]" : ''])
        @endforeach
    </div>
    <div class="cms-column__palette">
        <span class="cms-column__palette-label">Add widget:</span>
        @foreach(\App\Services\CmsContentService::WIDGETS as $wt => $wl)
            <button type="button" class="btn btn-outline btn-sm" data-add-widget="{{ $wt }}"><i class="fas fa-plus"></i> {{ $wl }}</button>
        @endforeach
    </div>
</div>
