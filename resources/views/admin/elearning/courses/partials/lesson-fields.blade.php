@php($editing=isset($lesson) && $lesson)
<div class="modal-grid">
    <div class="form-group full"><label>Lesson Title *</label><input name="title" value="{{ $editing ? $lesson->title : '' }}" required></div>
    <div class="form-group"><label>Content Type *</label><select name="content_type" required>@foreach(['text'=>'Text','video'=>'Video','file'=>'File','link'=>'Link','mixed'=>'Mixed'] as $v=>$l)<option value="{{ $v }}" @selected(($editing ? $lesson->content_type : 'text')===$v)>{{ $l }}</option>@endforeach</select></div>
    <div class="form-group"><label>Position</label><input type="number" min="1" name="position" value="{{ $editing ? $lesson->position : max(1,$module->lessons->count()+1) }}"></div>
    <div class="form-group"><label>Estimated Minutes</label><input type="number" min="1" name="estimated_minutes" value="{{ $editing ? $lesson->estimated_minutes : '' }}"></div>
    <div class="form-group"><label class="modal-check"><input type="checkbox" name="is_published" value="1" @checked($editing && $lesson->is_published)><span>Published</span></label></div>
    <div class="form-group full"><label>Video URL</label><input type="url" name="video_url" value="{{ $editing ? $lesson->video_url : '' }}" placeholder="https://..."></div>
    <div class="form-group full"><label>External URL</label><input type="url" name="external_url" value="{{ $editing ? $lesson->external_url : '' }}" placeholder="https://..."></div>
    <div class="form-group full"><label>File Path / Reference</label><input name="file_path" value="{{ $editing ? $lesson->file_path : '' }}" placeholder="Existing file reference or managed learning file path"></div>
    <div class="form-group full"><label>Lesson Content</label><textarea name="content" rows="8">{{ $editing ? $lesson->content : '' }}</textarea></div>
</div>
