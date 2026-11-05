{{-- Repeatable navigation links. $links, optional $columns (footer column titles). --}}
@php($links = is_array($links ?? null) ? array_values($links) : [])
@php($columns = $columns ?? null)
<div class="cms-links" data-links>
    <div class="cms-links__rows" data-link-rows>
        @foreach($links as $i => $link)
            @include('admin.cms.partials.link-row', ['i' => $i, 'link' => $link, 'columns' => $columns])
        @endforeach
    </div>
    <button type="button" class="btn btn-outline btn-sm" data-link-add><i class="fas fa-plus"></i> Add link</button>
    <template data-link-template>@include('admin.cms.partials.link-row', ['i' => '__I__', 'link' => [], 'columns' => $columns])</template>
    <small class="cms-hint">Use a site path like <code>/learning</code>, a section anchor like <code>#about</code>, or a full <code>https://</code> address.</small>
</div>
