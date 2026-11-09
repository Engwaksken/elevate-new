{{-- Category dropdown with an inline "+ New category" option (handled by the script on admin/library). --}}
@php
    $selectedCategory = (string) old('library_category_id', $selected ?? '');
    $current = $current ?? null; // keep a switched-off category selectable on the book that already uses it
@endphp
<div class="form-group"><label>Category</label>
<select name="library_category_id" data-library-category-select>
<option value="">None</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected($selectedCategory===(string)$category->id)>{{ $category->name }}</option>
@endforeach
@if($current && !$categories->contains('id', $current->id))
<option value="{{ $current->id }}" @selected($selectedCategory===(string)$current->id)>{{ $current->name }} (turned off)</option>
@endif
<option value="__new__">+ New category…</option>
</select>
<div data-library-category-new style="display:none;margin-top:8px">
    <div style="display:flex;gap:8px">
        <input type="text" maxlength="120" placeholder="New category name" data-library-category-name aria-label="New category name">
        <button type="button" class="btn btn-primary btn-sm" data-library-category-add>Add</button>
        <button type="button" class="btn btn-outline btn-sm" data-library-category-cancel>Cancel</button>
    </div>
    <small class="form-hint" data-library-category-error style="display:none;color:#b42318"></small>
</div>
</div>
