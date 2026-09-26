@extends('layouts.admin')
@section('title','Library | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Programme Delivery</span>
    <h1>Digital Library</h1>
    <p>Manage library resources, files, categories, visibility and access levels.</p>
</div>
<div class="admin-page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="createLibraryResource">
        <i class="fas fa-plus"></i> Add Resource
    </button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Resources','fa-book-open'],
['active','Active','fa-circle-check'],
['public','Public','fa-globe'],
['downloads','Downloads','fa-download']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search title, author or description..."></div>

<select name="category">
<option value="">All categories</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected((string)request('category')===(string)$category->id)>{{ $category->name }}</option>
@endforeach
</select>

<select name="access_level">
<option value="">All access levels</option>
@foreach(['public'=>'Public','authenticated'=>'Authenticated','staff'=>'Staff'] as $value=>$label)
<option value="{{ $value }}" @selected(request('access_level')===$value)>{{ $label }}</option>
@endforeach
</select>

<select name="language">
<option value="">All languages</option>
@foreach($languages as $language)
<option value="{{ $language }}" @selected(request('language')===$language)>{{ $language }}</option>
@endforeach
</select>

<select name="status">
<option value="">All statuses</option>
<option value="active" @selected(request('status')==='active')>Active</option>
<option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
</select>

<select name="per_page">
@foreach([10,20,25,50,100] as $n)
<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>
@endforeach
</select>

<button class="btn btn-primary btn-sm">Apply</button>
<a href="{{ route('admin.library.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
<th>Resource</th><th>Category</th><th>Language</th><th>Access</th><th>Views</th><th>Downloads</th><th>Status</th><th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($resources as $resource)
<tr>
<td>
<strong>{{ $resource->title }}</strong>
<small class="admin-cell-hint">{{ $resource->author ?: 'No author' }}</small>
</td>
<td>{{ $resource->category?->name ?: '—' }}</td>
<td>{{ $resource->language }}</td>
<td>{{ ucfirst($resource->access_level) }}</td>
<td>{{ number_format($resource->views_count) }}</td>
<td>{{ number_format($resource->downloads_count) }}</td>
<td><span class="status-chip {{ $resource->is_active ? 'active' : 'inactive' }}">{{ $resource->is_active ? 'Active' : 'Inactive' }}</span></td>
<td class="table-actions">
<div class="action-group">
<button type="button" class="btn-icon" title="Edit" data-modal-open="editLibrary{{ $resource->id }}"><i class="fas fa-pen"></i></button>
<a class="btn-icon" title="View" href="{{ route('library.show',$resource) }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i></a>
<button type="button" class="btn-icon danger" title="Archive" data-modal-open="archiveLibrary{{ $resource->id }}"><i class="fas fa-box-archive"></i></button>
</div>
</td>
</tr>
@empty
<tr><td colspan="8"><div class="admin-empty">No library resources found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $resources->links() }}</div>
</div>

<div class="eh-modal" id="createLibraryResource" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
<div><h2>Add Library Resource</h2><p>Upload a file, link an external resource, or provide both.</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.library.store') }}">
@csrf
<input type="hidden" name="_library_modal" value="create">
<div class="eh-modal-body">
@include('admin.library.resource-fields',['resource'=>null])
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<button class="btn btn-primary">Create Resource</button>
</div>
</form>
</div>
</div>

@foreach($resources as $resource)
<div class="eh-modal" id="editLibrary{{ $resource->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
<div><h2>Edit Library Resource</h2><p>{{ $resource->title }}</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.library.update',$resource) }}">
@csrf @method('PUT')
<input type="hidden" name="_library_modal" value="edit-{{ $resource->id }}">
<div class="eh-modal-body">
@include('admin.library.resource-fields',['resource'=>$resource])
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<button class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>

<div class="eh-modal" id="archiveLibrary{{ $resource->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header">
<div><h2>Archive Resource?</h2><p>{{ $resource->title }}</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body">
<p>The resource will be removed from the active library. Its stored file is retained for audit/history purposes.</p>
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<form method="POST" action="{{ route('admin.library.destroy',$resource) }}">@csrf @method('DELETE')<button class="btn btn-danger">Archive Resource</button></form>
</div>
</div>
</div>
@endforeach

@php($libraryModal=old('_library_modal'))
@if($libraryModal)
<script>
document.addEventListener('DOMContentLoaded',function(){
    const value=@json($libraryModal);
    const id=value==='create' ? 'createLibraryResource' : (value.startsWith('edit-') ? 'editLibrary'+value.substring(5) : null);
    if(id) document.querySelector(`[data-modal-open="${id}"]`)?.click();
});
</script>
@endif

@endsection
