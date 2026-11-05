{{-- Sticky save / publish bar shared by the CMS editors. --}}
<div class="cms-savebar">
    <div class="cms-savebar__status">
        @if($page->published_at)<span class="status-chip active">Published {{ $page->published_at->format('d M Y H:i') }}</span>@else<span class="status-chip">Not published - built-in content is live</span>@endif
        <small>Drafts are private until you publish.</small>
    </div>
    <div class="action-group">
        @if($previewUrl ?? null)<a class="btn btn-outline" href="{{ $previewUrl }}" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview saved draft</a>@endif
        <button class="btn btn-outline" name="action" value="save"><i class="fas fa-floppy-disk"></i> Save draft</button>
        @if($page->published_at)<button class="btn btn-outline" name="action" value="unpublish" onclick="return confirm('Unpublish? The built-in content will be shown instead.')">Unpublish</button>@endif
        <button class="btn btn-primary" name="action" value="publish"><i class="fas fa-rocket"></i> Publish</button>
    </div>
</div>
