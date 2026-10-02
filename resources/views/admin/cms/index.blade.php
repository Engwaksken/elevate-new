@extends('layouts.admin')
@section('title', 'Frontend CMS | Administration')
@section('content')
<div class="admin-page-header"><div><h1>Frontend CMS</h1><p>Manage public page content, header, footer, signup forms and custom pages. Save drafts, preview, then publish.</p></div></div>
<div class="admin-panel"><h2>Catalogue content</h2><div class="action-group">
@foreach(['admin.elearning.courses.index'=>'Courses','admin.jobs.index'=>'Jobs','admin.library.index'=>'Library','admin.events.index'=>'Events','admin.mentorship.mentors.index'=>'Mentors','admin.jobs.employers.index'=>'Employers'] as $route=>$label)@if(Route::has($route))<a class="btn btn-outline" href="{{ route($route) }}">Manage {{ $label }}</a>@endif @endforeach
</div></div>
<div class="admin-panel"><table class="admin-table"><thead><tr><th>Page / section</th><th>Publication</th><th>Actions</th></tr></thead><tbody>
@foreach($pages as $page)<tr><td>{{ $page->title }}<small>{{ $page->slug }}</small></td><td>{{ $page->published_at ? 'Published '.$page->published_at->format('d M Y H:i') : 'Draft / built-in content' }}</td><td><a class="btn btn-outline btn-sm" href="{{ route('admin.cms.edit', $page) }}">Edit</a> <a href="{{ route('admin.cms.preview', $page) }}">Preview draft</a>@if($url = app(\App\Services\CmsContentService::class)->publicUrl($page)) · <a href="{{ $url }}" target="_blank" rel="noopener">View page</a>@endif</td></tr>@endforeach
</tbody></table></div>
<div class="admin-panel"><h2>Add a custom page</h2><form method="POST" action="{{ route('admin.cms.store') }}">@csrf<div class="form-group"><label>Title</label><input name="title" required maxlength="190"></div><div class="form-group"><label>Slug (URL: /pages/your-slug)</label><input name="slug" required pattern="[a-z][a-z0-9-]*" maxlength="100"></div><button class="btn btn-primary">Create draft</button></form></div>
@endsection
