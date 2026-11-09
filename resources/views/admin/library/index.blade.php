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
    <x-export-buttons />
    <a class="btn btn-outline" href="{{ route('admin.library.requests.index') }}"><i class="fas fa-inbox"></i> Download requests @if($pendingDownloadRequests ?? 0)<span class="status-chip pending">{{ $pendingDownloadRequests }}</span>@endif</a>
    <button type="button" class="btn btn-primary" data-modal-open="createLibraryResource">
        <i class="fas fa-plus"></i> Add Resource
    </button>
    <button type="button" class="btn btn-outline" data-modal-open="bulkLibraryResources">
        <i class="fas fa-layer-group"></i> Add Multiple
    </button>
    <button type="button" class="btn btn-outline" data-modal-open="manageLibraryCategories">
        <i class="fas fa-tags"></i> Categories
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

<select name="course_id">
<option value="">All courses</option>
@foreach($courses as $course)
<option value="{{ $course->id }}" @selected((int) request('course_id')===$course->id)>{{ $course->title }}</option>
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

@php
$bulkRoute = route('admin.library.bulk-destroy');
$bulkTableId = 'libraryTable';
@endphp
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])

<div class="admin-table-wrap">
<table class="admin-table" id="{{ $bulkTableId }}">
<thead>
<tr>
<th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Resource</th><th>Courses</th><th>Category</th><th>Language</th><th>Access</th><th>Views</th><th>Downloads</th><th>Status</th><th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($resources as $resource)
<tr>
<td><input type="checkbox" data-row-select value="{{ $resource->id }}" aria-label="Select {{ $resource->title }}"></td>
<td>
<strong>{{ $resource->title }}</strong>
<small class="admin-cell-hint">{{ $resource->author ?: 'No author' }}</small>
</td>
<td>@if($resource->courses->isNotEmpty()){{ $resource->courses->pluck('title')->implode(', ') }}@else<small class="admin-cell-hint">All participants</small>@endif</td>
<td>{{ $resource->category?->name ?: '-' }}</td>
<td>{{ $resource->language }}</td>
<td>{{ ucfirst($resource->access_level) }}@if($resource->requiresDownloadApproval())<small class="admin-cell-hint">Download on request</small>@endif</td>
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
<tr><td colspan="10"><div class="admin-empty">No library resources found.</div></td></tr>
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

@php($bulkMax=\App\Http\Controllers\Admin\Library\LibraryResourceController::BULK_MAX_FILES)
<div class="eh-modal" id="bulkLibraryResources" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
<div><h2>Add Multiple Resources</h2><p>Each file becomes its own resource. The settings below apply to all of them.</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<form method="POST" enctype="multipart/form-data" action="{{ route('admin.library.bulk-store') }}">
@csrf
<input type="hidden" name="_library_modal" value="bulk">
<div class="eh-modal-body">
<div class="modal-grid">
<div class="form-group full">
    <label>Files * <small class="form-hint" style="display:inline">(up to {{ $bulkMax }}, 50 MB each)</small></label>
    <input type="file" name="files[]" multiple required data-library-bulk-files data-max="{{ $bulkMax }}" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4,.mp3,.zip">
    <small class="form-hint" data-library-bulk-warning style="display:none;color:#b42318"></small>
</div>

<div class="form-group full" data-library-bulk-list style="display:none">
    <label>Titles <small class="form-hint" style="display:inline">(taken from file names; edit as needed)</small></label>
    <div data-library-bulk-rows style="display:grid;gap:8px"></div>
</div>

@include('admin.library.category-select', ['selected' => ''])

<div class="form-group"><label>Language *</label><input name="language" value="{{ old('language','English') }}" required></div>

<div class="form-group"><label>Author</label><input name="author" value="{{ old('author') }}"></div>

<div class="form-group"><label>Publication Date</label><input type="date" name="publication_date" value="{{ old('publication_date') }}"></div>

<div class="form-group full"><label>Tags</label><input name="tags_text" value="{{ old('tags_text') }}" placeholder="career, entrepreneurship, digital skills"></div>

@include('admin.library.course-picker', ['selected' => []])

<div class="form-group"><label>Access *</label>
<select name="access_level" required>
@foreach(['public'=>'Public','authenticated'=>'Authenticated','staff'=>'Staff'] as $value=>$label)
<option value="{{ $value }}" @selected(old('access_level','authenticated')===$value)>{{ $label }}</option>
@endforeach
</select>
</div>

<div class="form-group"><label>Downloading *</label>
<select name="download_policy" required>
<option value="free" @selected(old('download_policy','free')==='free')>Free to download</option>
<option value="request" @selected(old('download_policy')==='request')>Download on request (staff approve)</option>
</select>
</div>

<div class="form-group"><label class="modal-check"><input type="checkbox" name="is_active" value="1" @checked((bool)old('is_active',true))><span>Active</span></label></div>
</div>
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
<button class="btn btn-primary" data-library-bulk-submit>Create Resources</button>
</div>
</form>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const input=document.querySelector('[data-library-bulk-files]');
    if(!input) return;
    const form=input.closest('form');
    const list=form.querySelector('[data-library-bulk-list]');
    const rows=form.querySelector('[data-library-bulk-rows]');
    const warning=form.querySelector('[data-library-bulk-warning]');
    const submit=form.querySelector('[data-library-bulk-submit]');
    const max=parseInt(input.dataset.max,10)||20;
    const maxBytes=50*1024*1024;

    const titleFrom=name=>{
        const t=name.replace(/\.[^.]+$/,'').replace(/[_-]+/g,' ').replace(/\s+/g,' ').trim();
        return t ? t.charAt(0).toUpperCase()+t.slice(1) : 'Untitled resource';
    };
    const size=b=>b>=1048576 ? (b/1048576).toFixed(1)+' MB' : Math.max(1,Math.round(b/1024))+' KB';

    input.addEventListener('change',function(){
        rows.innerHTML='';
        const files=Array.from(input.files||[]);
        const problems=[];
        if(files.length>max) problems.push(`You selected ${files.length} files; the limit is ${max} per upload.`);
        files.forEach((file,i)=>{
            if(file.size>maxBytes) problems.push(`${file.name} is larger than 50 MB.`);
            const row=document.createElement('div');
            row.style.cssText='display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px;align-items:center';
            const title=document.createElement('input');
            title.name=`titles[${i}]`;
            title.maxLength=190;
            title.value=titleFrom(file.name);
            const meta=document.createElement('small');
            meta.className='form-hint';
            meta.style.whiteSpace='nowrap';
            meta.textContent=size(file.size);
            meta.title=file.name;
            row.append(title,meta);
            rows.append(row);
        });
        list.style.display=files.length ? '' : 'none';
        warning.textContent=problems.join(' ');
        warning.style.display=problems.length ? '' : 'none';
        submit.disabled=problems.length>0;
        submit.textContent=files.length>1 ? `Create ${files.length} Resources` : 'Create Resources';
    });
});
</script>

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

<div class="eh-modal" id="manageLibraryCategories" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
<div><h2>Library Categories</h2><p>Categories group books in the library. Turned-off categories are hidden from the forms and the participant filters.</p></div>
<button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>
<div class="eh-modal-body">
@if((old('_library_modal') ?: session('open_library_modal'))==='categories' && ($errors->has('name') || $errors->has('category') || $errors->has('description')))
<div class="alert alert-error">{{ $errors->first('category') ?: ($errors->first('name') ?: $errors->first('description')) }}</div>
@endif
<form method="POST" action="{{ route('admin.library.categories.store') }}" class="modal-grid" style="align-items:end;margin-bottom:18px">
@csrf
<input type="hidden" name="_library_modal" value="categories">
<div class="form-group"><label>New category *</label><input name="name" maxlength="120" required placeholder="e.g. Entrepreneurship"></div>
<div class="form-group"><label>Description</label><input name="description" maxlength="1000"></div>
<div class="form-group full"><button class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Add Category</button></div>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Name</th><th>Description</th><th>Books</th><th>Active</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($allCategories as $cat)
<tr>
<td><input form="libraryCategory{{ $cat->id }}" name="name" value="{{ $cat->name }}" maxlength="120" required aria-label="Category name" style="width:100%;min-width:140px"></td>
<td><input form="libraryCategory{{ $cat->id }}" name="description" value="{{ $cat->description }}" maxlength="1000" aria-label="Description" style="width:100%;min-width:140px"></td>
<td>{{ number_format($cat->resources_count) }}</td>
<td><input form="libraryCategory{{ $cat->id }}" type="checkbox" name="is_active" value="1" @checked($cat->is_active) aria-label="Active"></td>
<td class="table-actions">
<div class="action-group">
<form id="libraryCategory{{ $cat->id }}" method="POST" action="{{ route('admin.library.categories.update',$cat) }}">@csrf @method('PUT')<input type="hidden" name="_library_modal" value="categories"><button class="btn-icon" title="Save"><i class="fas fa-floppy-disk"></i></button></form>
@if($cat->resources_count===0)
<form method="POST" action="{{ route('admin.library.categories.destroy',$cat) }}">@csrf @method('DELETE')<button class="btn-icon danger" title="Delete"><i class="fas fa-trash"></i></button></form>
@endif
</div>
</td>
</tr>
@empty
<tr><td colspan="5"><div class="admin-empty">No categories yet. Add the first one above.</div></td></tr>
@endforelse
</tbody>
</table>
</div>
</div>
<div class="eh-modal-footer">
<button type="button" class="btn btn-outline" data-modal-close>Close</button>
</div>
</div>
</div>

<script>
// "+ New category" inside any Category dropdown: create it without leaving the form.
document.addEventListener('DOMContentLoaded',function(){
    const url=@json(route('admin.library.categories.store'));
    const token=@json(csrf_token());
    const selects=()=>document.querySelectorAll('[data-library-category-select]');

    selects().forEach(function(select){
        const group=select.closest('.form-group');
        const box=group.querySelector('[data-library-category-new]');
        const input=group.querySelector('[data-library-category-name]');
        const error=group.querySelector('[data-library-category-error]');
        const addButton=group.querySelector('[data-library-category-add]');
        let previous=select.value==='__new__' ? '' : select.value;

        const close=()=>{ box.style.display='none'; error.style.display='none'; input.value=''; };
        const fail=msg=>{ error.textContent=msg; error.style.display=''; };

        select.addEventListener('change',function(){
            if(select.value!=='__new__'){ previous=select.value; close(); return; }
            select.value=previous;
            box.style.display='';
            input.focus();
        });

        group.querySelector('[data-library-category-cancel]').addEventListener('click',close);
        input.addEventListener('keydown',e=>{ if(e.key==='Enter'){ e.preventDefault(); addButton.click(); } });

        addButton.addEventListener('click',async function(){
            const name=input.value.trim();
            if(!name){ fail('Enter a category name.'); return; }
            addButton.disabled=true;
            try{
                const response=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},body:JSON.stringify({name})});
                const body=await response.json().catch(()=>({}));
                if(!response.ok){ fail(body?.errors?.name?.[0] || body?.message || 'The category could not be added.'); return; }
                // Add it to every Category dropdown on the page, then select it here.
                selects().forEach(function(other){
                    const option=new Option(body.name,body.id);
                    const sentinel=other.querySelector('option[value="__new__"]');
                    other.insertBefore(option,sentinel);
                });
                select.value=String(body.id);
                previous=select.value;
                close();
            }catch(e){
                fail('The category could not be added. Check your connection and try again.');
            }finally{
                addButton.disabled=false;
            }
        });

        // Never submit the "+ New category" placeholder.
        select.form?.addEventListener('submit',()=>{ if(select.value==='__new__') select.value=previous; });
    });
});
</script>

@php($libraryModal=old('_library_modal') ?: session('open_library_modal'))
@if($libraryModal)
<script>
document.addEventListener('DOMContentLoaded',function(){
    const value=@json($libraryModal);
    const id=value==='create' ? 'createLibraryResource' : value==='bulk' ? 'bulkLibraryResources' : value==='categories' ? 'manageLibraryCategories' :(value.startsWith('edit-') ? 'editLibrary'+value.substring(5) : null);
    if(id) document.querySelector(`[data-modal-open="${id}"]`)?.click();
});
</script>
@endif

@endsection
