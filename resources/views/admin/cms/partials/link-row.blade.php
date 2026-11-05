<div class="cms-link-row" data-link-row>
    <span class="cms-link-row__grip"><i class="fas fa-grip-vertical"></i></span>
    <input name="links[{{ $i }}][label]" value="{{ $link['label'] ?? '' }}" placeholder="Label" data-link-field="label" maxlength="100" aria-label="Link label">
    <input name="links[{{ $i }}][url]" value="{{ $link['url'] ?? '' }}" placeholder="/path or https://" data-link-field="url" aria-label="Link address">
    @if($columns)
        <select name="links[{{ $i }}][column]" data-link-field="column" aria-label="Footer column">
            @foreach([1, 2, 3] as $c)<option value="{{ $c }}" @selected((int) ($link['column'] ?? 1) === $c)>{{ $columns[$c - 1] ?: 'Column '.$c }}</option>@endforeach
        </select>
    @endif
    <label class="cms-link-row__tab" title="Open in a new tab"><input type="checkbox" name="links[{{ $i }}][new_tab]" value="1" data-link-field="new_tab" @checked(!empty($link['new_tab']))> New tab</label>
    <span class="cms-link-row__actions">
        <button type="button" class="btn-icon" title="Move up" data-link-move="up"><i class="fas fa-arrow-up"></i></button>
        <button type="button" class="btn-icon" title="Move down" data-link-move="down"><i class="fas fa-arrow-down"></i></button>
        <button type="button" class="btn-icon danger" title="Remove" data-link-remove><i class="fas fa-trash"></i></button>
    </span>
</div>
