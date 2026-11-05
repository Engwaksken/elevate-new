{{-- URL field with an upload button. kind: image | video | any --}}
@php($kind = $kind ?? 'image')
@php($mv = (string) ($value ?? ''))
<div class="cms-media" data-media data-media-kind="{{ $kind }}">
    <div class="cms-media__row">
        <input @if(!empty($name)) name="{{ $name }}" @endif value="{{ $mv }}" {{ $attr }}="{{ $field }}" placeholder="{{ $placeholder ?? ($kind === 'video' ? 'Upload, or paste a YouTube / Vimeo / .mp4 link' : 'Upload, or paste https://...') }}" data-media-input>
        <button type="button" class="btn btn-outline btn-sm" data-media-upload><i class="fas fa-upload"></i> Upload</button>
        <input type="file" hidden data-media-file accept="{{ $kind === 'video' ? 'video/mp4,video/webm' : ($kind === 'any' ? 'image/*,video/mp4,video/webm' : 'image/png,image/jpeg,image/webp,image/gif') }}">
    </div>
    <div class="cms-media__preview" data-media-preview></div>
    <small class="cms-media__status" data-media-status></small>
</div>
