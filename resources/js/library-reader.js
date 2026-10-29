// Page-by-page PDF reader for the digital library. Opens at the saved page and saves progress as pages turn.
// Legacy build: supports older phone browsers that many participants use.
import * as pdfjsLib from 'pdfjs-dist/legacy/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/legacy/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

const stage = document.querySelector('[data-library-reader]');

if (stage) {
    const canvas = stage.querySelector('[data-reader-canvas]');
    const status = stage.querySelector('[data-reader-status]');
    const controls = document.querySelector('[data-reader-controls]');
    const pageInput = document.querySelector('[data-reader-page]');
    const totalLabel = document.querySelector('[data-reader-total]');
    const progressBar = document.querySelector('[data-reader-progress]');
    const doneNote = document.querySelector('[data-reader-done]');

    let pdf = null;
    let page = Math.max(1, parseInt(stage.dataset.startPage, 10) || 1);
    let zoom = 1;
    let rendering = null;
    let saveTimer = null;

    const save = () => {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => {
            fetch(stage.dataset.progressUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': stage.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ page, total_pages: pdf.numPages }),
                keepalive: true,
            }).catch(() => {});
        }, 800);
    };

    const render = async () => {
        if (!pdf) return;
        if (rendering) {
            rendering.cancel();
        }
        const pdfPage = await pdf.getPage(page);
        // Fit the page to the reading area, then apply the reader's zoom.
        const available = Math.max(280, stage.clientWidth - 32);
        const base = pdfPage.getViewport({ scale: 1 });
        const viewport = pdfPage.getViewport({ scale: (available / base.width) * zoom });
        const ratio = window.devicePixelRatio || 1;

        canvas.width = Math.floor(viewport.width * ratio);
        canvas.height = Math.floor(viewport.height * ratio);
        canvas.style.width = Math.floor(viewport.width) + 'px';
        canvas.style.height = Math.floor(viewport.height) + 'px';

        rendering = pdfPage.render({ canvasContext: canvas.getContext('2d'), viewport, transform: ratio !== 1 ? [ratio, 0, 0, ratio, 0, 0] : null });
        try {
            await rendering.promise;
        } catch (error) {
            if (error?.name !== 'RenderingCancelledException') throw error;
        }

        pageInput.value = page;
        progressBar.style.width = (page / pdf.numPages * 100) + '%';
        doneNote.hidden = page < pdf.numPages;
    };

    const goTo = (target) => {
        if (!pdf) return;
        const next = Math.min(pdf.numPages, Math.max(1, target));
        if (next === page) return;
        page = next;
        render();
        save();
        stage.scrollIntoView({ block: 'start', behavior: 'smooth' });
    };

    document.querySelector('[data-reader-prev]').addEventListener('click', () => goTo(page - 1));
    document.querySelector('[data-reader-next]').addEventListener('click', () => goTo(page + 1));
    pageInput.addEventListener('change', () => goTo(parseInt(pageInput.value, 10) || page));
    document.querySelectorAll('[data-reader-zoom]').forEach((button) => button.addEventListener('click', () => {
        zoom = Math.min(3, Math.max(0.5, zoom + Number(button.dataset.readerZoom) * 0.25));
        render();
    }));
    document.addEventListener('keydown', (event) => {
        if (event.target.closest('input, textarea, select')) return;
        if (event.key === 'ArrowRight' || event.key === 'PageDown') goTo(page + 1);
        if (event.key === 'ArrowLeft' || event.key === 'PageUp') goTo(page - 1);
    });
    let resizeTimer = null;
    window.addEventListener('resize', () => { clearTimeout(resizeTimer); resizeTimer = setTimeout(render, 200); });

    // The server sends the PDF base64-encoded as text, because some antivirus/web-protection software
    // blanks application/pdf responses in the browser. Decode it and hand PDF.js the bytes.
    const toBytes = (base64) => {
        if (typeof Uint8Array.fromBase64 === 'function') return Uint8Array.fromBase64(base64);
        const binary = atob(base64);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
        return bytes;
    };

    fetch(stage.dataset.src, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then((response) => {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.text();
        })
        .then((text) => pdfjsLib.getDocument({ data: toBytes(text.trim()), isEvalSupported: false }).promise)
        .then(async (doc) => {
            pdf = doc;
            page = Math.min(page, pdf.numPages);
            totalLabel.textContent = pdf.numPages;
            pageInput.max = pdf.numPages;
            status.hidden = true;
            canvas.hidden = false;
            controls.hidden = false;
            await render();
            save();
        })
        .catch((error) => {
            console.error('Library reader could not open the book:', error?.name, error?.message);
            status.innerHTML = 'This book could not be opened in the reader. Please go back and try "Read online" again, or contact the library team.';
        });
}
