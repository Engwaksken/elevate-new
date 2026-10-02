@extends('layouts.admin')
@section('title', 'Edit '.$page->title.' | Frontend CMS')
@section('content')
<div class="admin-page-header"><div><h1>Edit {{ $page->slug }}</h1><p>Content uses Markdown: headings (#), lists, links and emphasis. Raw HTML and unsafe links are removed.</p></div><a href="{{ route('admin.cms.index') }}" class="btn btn-outline">All pages</a></div>
@if(in_array($page->slug, ['home','privacy-policy','terms'], true))<div class="warning-box">Publishing this page replaces its built-in content. Add the complete page text before publishing.</div>@endif
<div class="admin-panel"><form method="POST" action="{{ route('admin.cms.update', $page) }}" enctype="multipart/form-data">@csrf @method('PUT')
<div class="form-group"><label>Title / brand name</label><input name="title" value="{{ old('title', $page->title) }}" required maxlength="190"></div>
<div class="form-group"><label>Summary / tagline / meta description</label><textarea name="summary" rows="3">{{ old('summary', $page->summary) }}</textarea></div>
@php($sections = old('sections', data_get($page->settings, 'sections', [])))
@include('cms.builder', ['sections' => $sections])
<div class="form-group"><label>Page content (Markdown) — optional when using sections</label><textarea name="body" rows="14">{{ old('body', $page->body) }}</textarea><small>Raw HTML and unsafe links are removed. Sections above are rendered first, followed by this Markdown content.</small></div>
<div class="form-group"><label>Image (PNG, JPG, WEBP, up to 5 MB)</label><input type="file" name="image" accept=".png,.jpg,.jpeg,.webp">@if($page->image_path)<label><input type="checkbox" name="remove_image" value="1"> Remove image</label>@endif</div>
<div class="form-group"><label>{{ in_array($page->slug, ['site-header','site-footer']) ? 'Navigation links' : 'Page action links' }} — one per line: Label | /path</label><textarea name="links_text" rows="6">{{ old('links_text', collect(data_get($page->settings, 'links', []))->map(fn($link) => $link['label'].' | '.$link['url'])->join("\n")) }}</textarea><small>Example: FAQs | /faqs · Become a Mentor | /mentors/signup · Employers | /employers/signup</small></div>
@if(in_array($page->slug, ['mentor-signup','employer-signup'], true))
<div class="form-group"><label>Submit button text</label><input name="button_label" value="{{ old('button_label', data_get($page->settings, 'button_label', 'Submit registration')) }}"></div>
<label><input type="checkbox" name="signup_open" value="1" @checked(old('signup_open', data_get($page->settings, 'signup_open', true)))> Accept new registrations</label>
<div class="form-group"><label>Form field labels — one per line: field_name | Label</label><textarea name="field_labels_text" rows="6">{{ old('field_labels_text', collect(data_get($page->settings, 'field_labels', []))->map(fn($label, $key) => $key.' | '.$label)->join("\n")) }}</textarea><small>Fields: name, email, phone, country, organisation, job_title, industry, years_experience, linkedin_url, skills, languages, mentoring_areas, professional_bio, company_name, company_type, website, contact_person, location, description.</small></div>
@endif
<div class="action-group"><button class="btn btn-outline" name="action" value="save">Save draft</button><button class="btn btn-primary" name="action" value="publish">Publish</button><button class="btn btn-outline" name="action" value="unpublish">Unpublish</button><a href="{{ route('admin.cms.preview', $page) }}" target="_blank" rel="noopener">Preview saved draft</a></div>
</form>
@unless(array_key_exists($page->slug, \App\Services\CmsContentService::PAGES))<form method="POST" action="{{ route('admin.cms.destroy', $page) }}" onsubmit="return confirm('Delete this page?')">@csrf @method('DELETE')<button class="btn btn-danger">Delete page</button></form>@endunless
</div>
@endsection
