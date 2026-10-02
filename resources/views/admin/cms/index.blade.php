@extends('layouts.admin')
@section('title', 'Frontend CMS | Administration')
@section('content')
<div class="admin-page-header"><div><h1>Frontend CMS</h1><p>Manage public page content, header, footer, signup forms and custom pages. Save drafts, preview, then publish.</p></div><div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createPageModal"><i class="fas fa-plus"></i> Add Custom Page</button></div></div>
<div class="admin-panel"><h2>Catalogue content</h2><div class="action-group">
@foreach(['admin.elearning.courses.index'=>'Courses','admin.jobs.index'=>'Jobs','admin.library.index'=>'Library','admin.events.index'=>'Events','admin.mentorship.mentors.index'=>'Mentors','admin.jobs.employers.index'=>'Employers'] as $route=>$label)@if(Route::has($route))<a class="btn btn-outline" href="{{ route($route) }}">Manage {{ $label }}</a>@endif @endforeach
</div></div>
<div class="admin-panel"><table class="admin-table"><thead><tr><th>Page / section</th><th>Publication</th><th>Actions</th></tr></thead><tbody>
@foreach($pages as $page)<tr><td>{{ $page->title }}<small>{{ $page->slug }}</small></td><td>{{ $page->published_at ? 'Published '.$page->published_at->format('d M Y H:i') : 'Draft / built-in content' }}</td><td><a class="btn btn-outline btn-sm" href="{{ route('admin.cms.edit', $page) }}">Edit</a> <a href="{{ route('admin.cms.preview', $page) }}">Preview draft</a>@if($url = app(\App\Services\CmsContentService::class)->publicUrl($page)) · <a href="{{ $url }}" target="_blank" rel="noopener">View page</a>@endif</td></tr>@endforeach
</tbody></table></div>
<div class="admin-panel"><h2>Add a custom page</h2><p>Create a draft page, then edit its content, preview and publish from the page list above.</p></div>

<div class="eh-modal" id="createPageModal" aria-hidden="true" @if($errors->any()) data-modal-autoopen @endif><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Add Custom Page</h2><p>Creates a draft page you can edit and publish.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.cms.store') }}">@csrf
<input type="hidden" name="_cms_page" value="1">
<div class="eh-modal-body">
<div class="modal-grid">
<div class="form-group"><label>Title</label><input name="title" value="{{ old('title') }}" required maxlength="190"></div>
<div class="form-group"><label>Slug (URL: /pages/your-slug)</label><input name="slug" value="{{ old('slug') }}" required pattern="[a-z][a-z0-9-]*" maxlength="100"></div>
</div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create draft</button></div>
</form></div></div>
@endsection
