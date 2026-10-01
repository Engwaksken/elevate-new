{{-- In-page file preview. Any link with data-file-preview opens its preview in this dialog;
     without JavaScript the link still opens in a new tab. --}}
<style>
    .fp-modal { position:fixed; inset:0; z-index:2000; display:none; align-items:center; justify-content:center; padding:16px; background:rgba(16,24,40,.6); }
    .fp-modal.is-open { display:flex; }
    .fp-dialog { display:flex; flex-direction:column; width:min(1200px,100%); height:min(92vh,100%); background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 24px 48px rgba(16,24,40,.3); }
    .fp-head { display:flex; align-items:center; gap:8px; padding:10px 14px; border-bottom:1px solid #e4e7ec; }
    .fp-title { flex:1; min-width:0; margin:0; font-size:.95rem; font-weight:600; color:#1d2433; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .fp-action { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border:1px solid #d0d5dd; border-radius:8px; background:#fff; color:#344054; font-size:.85rem; font-weight:600; text-decoration:none; cursor:pointer; }
    .fp-action:hover { background:#f9fafb; }
    .fp-close { border:0; background:transparent; font-size:1.4rem; line-height:1; padding:4px 8px; color:#667085; cursor:pointer; }
    .fp-body { position:relative; flex:1; background:#f5f6fa; }
    .fp-body iframe { width:100%; height:100%; border:0; }
    .fp-loading { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; color:#667085; font-size:.9rem; }
    body.fp-locked { overflow:hidden; }
    @media (max-width: 600px) { .fp-modal { padding:0; } .fp-dialog { height:100%; border-radius:0; } .fp-action span { display:none; } }
</style>

<div class="fp-modal" id="filePreviewModal" role="dialog" aria-modal="true" aria-labelledby="filePreviewTitle" aria-hidden="true">
    <div class="fp-dialog">
        <div class="fp-head">
            <h2 class="fp-title" id="filePreviewTitle">Preview</h2>
            <a class="fp-action" id="filePreviewDownload" href="#"><i class="fas fa-download"></i><span>Download</span></a>
            <a class="fp-action" id="filePreviewNewTab" href="#" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i><span>New tab</span></a>
            <button type="button" class="fp-close" data-file-preview-close aria-label="Close preview">&times;</button>
        </div>
        <div class="fp-body">
            <div class="fp-loading" id="filePreviewLoading">Loading preview…</div>
            <iframe id="filePreviewFrame" title="File preview"></iframe>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('filePreviewModal');
    if (!modal || modal.dataset.ready) return;
    modal.dataset.ready = '1';

    var frame = document.getElementById('filePreviewFrame');
    var title = document.getElementById('filePreviewTitle');
    var download = document.getElementById('filePreviewDownload');
    var newTab = document.getElementById('filePreviewNewTab');
    var loading = document.getElementById('filePreviewLoading');
    var opener = null;

    function withParams(href, params) {
        var url = new URL(href, window.location.href);
        Object.keys(params).forEach(function (key) {
            if (params[key] === null) url.searchParams.delete(key); else url.searchParams.set(key, params[key]);
        });
        return url.toString();
    }

    function open(link) {
        opener = link;
        title.textContent = link.dataset.filePreviewTitle || link.getAttribute('title') || 'Preview';
        download.href = withParams(link.href, { preview: null, embed: null });
        newTab.href = withParams(link.href, { preview: '1', embed: null });
        loading.hidden = false;
        frame.src = withParams(link.href, { preview: '1', embed: '1' });
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('fp-locked');
        modal.querySelector('[data-file-preview-close]').focus();
    }

    function close() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('fp-locked');
        frame.src = 'about:blank';
        if (opener) opener.focus();
    }

    frame.addEventListener('load', function () { loading.hidden = true; });

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[data-file-preview]');
        if (link && !event.ctrlKey && !event.metaKey && !event.shiftKey && event.button === 0) {
            event.preventDefault();
            open(link);
            return;
        }
        if (event.target === modal || event.target.closest('[data-file-preview-close]')) close();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
})();
</script>
