@php($editing=isset($resource) && $resource)
<div class="modal-grid">
<div class="form-group full"><label>Title *</label><input name="title" value="{{ old('title',$editing ? $resource->title : '') }}" required></div>

<div class="form-group"><label>Author</label><input name="author" value="{{ old('author',$editing ? $resource->author : '') }}"></div>

<div class="form-group"><label>Category</label>
<select name="library_category_id">
<option value="">None</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" @selected((string)old('library_category_id',$editing ? $resource->library_category_id : '')===(string)$category->id)>{{ $category->name }}</option>
@endforeach
</select>
</div>

<div class="form-group"><label>Language *</label><input name="language" value="{{ old('language',$editing ? $resource->language : 'English') }}" required></div>

<div class="form-group"><label>Publication Date</label><input type="date" name="publication_date" value="{{ old('publication_date',$editing ? optional($resource->publication_date)->format('Y-m-d') : '') }}"></div>

<div class="form-group"><label>Access *</label>
<select name="access_level" required>
@foreach(['public'=>'Public','authenticated'=>'Authenticated','staff'=>'Staff'] as $value=>$label)
<option value="{{ $value }}" @selected(old('access_level',$editing ? $resource->access_level : 'authenticated')===$value)>{{ $label }}</option>
@endforeach
</select>
</div>

<div class="form-group"><label class="modal-check"><input type="checkbox" name="is_active" value="1" @checked((bool)old('is_active',$editing ? $resource->is_active : true))><span>Active</span></label></div>

<div class="form-group full"><label>Description</label><textarea name="description" rows="5">{{ old('description',$editing ? $resource->description : '') }}</textarea></div>

<div class="form-group full"><label>Tags</label><input name="tags_text" value="{{ old('tags_text',$editing ? implode(', ',$resource->tags ?? []) : '') }}" placeholder="career, entrepreneurship, digital skills"></div>

<div class="form-group full"><label>External URL</label><input type="url" name="external_url" value="{{ old('external_url',$editing ? $resource->external_url : '') }}" placeholder="https://..."></div>

<div class="form-group full"><label>{{ $editing ? 'Replace Resource File' : 'Resource File' }}</label><input type="file" name="file" accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.jpg,.jpeg,.png,.mp4,.mp3,.zip">@if($editing && $resource->file_path)<small class="form-hint">A file is currently attached. Leave blank to keep it.</small>@endif</div>

<div class="form-group full"><label>{{ $editing ? 'Replace Cover Image' : 'Cover Image' }}</label><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">@if($editing && $resource->cover_image_path)<small class="form-hint">A cover image is currently attached. Leave blank to keep it.</small>@endif</div>
</div>
