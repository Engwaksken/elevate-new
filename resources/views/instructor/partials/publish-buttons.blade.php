{{-- Publish / draft actions for an assessment form. Expects $label ("assignment") and optional $published (null when creating). --}}
@php($published = $published ?? null)
<div class="full icm-publish-actions">
    @if($published)
        <button class="btn btn-primary" name="publish" value="1"><i class="fas fa-floppy-disk"></i> Save changes</button>
        <button class="btn btn-outline" name="publish" value="0"><i class="fas fa-eye-slash"></i> Unpublish (move to drafts)</button>
        <small class="icm-muted">Published: participants can see this {{ $label }}.</small>
    @else
        <button class="btn btn-primary" name="publish" value="1"><i class="fas fa-paper-plane"></i> Publish now</button>
        <button class="btn btn-outline" name="publish" value="0"><i class="fas fa-file-pen"></i> Save draft</button>
        <small class="icm-muted">{{ $published === false ? 'Draft: participants cannot see this '.$label.' yet.' : 'Drafts stay hidden from participants until you publish them.' }}</small>
    @endif
</div>
