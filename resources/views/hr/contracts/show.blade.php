@extends(auth()->user()?->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Review Contract | ElevateHer360')
@section('content')
@include('hr.contracts.partials.styles')
@php($method = old('signature_method','drawn'))

<div class="admin-page-header"><div><span class="admin-eyebrow">My Contracts</span><h1>{{ $contract->contract_type ?: 'Employment contract' }}</h1><p>{{ optional($contract->start_date)->format('d M Y') }} – {{ optional($contract->end_date)->format('d M Y') ?: 'Open-ended' }}</p></div><div class="admin-page-actions"><a href="{{ route('staff.contracts.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> My Contracts</a></div></div>

<div class="admin-panel ct-card">
<div class="admin-panel-head"><div><h2>Contract document</h2><p>{{ $contract->documentName() }}</p></div><span class="status-chip {{ $contract->signatureChip() }}">{{ $contract->signatureLabel() }}</span></div>

@if($contract->document_path)
<div class="ct-actions" style="margin-bottom:12px">
<a href="{{ route('staff.contracts.document',[$contract,'preview'=>1]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener" data-file-preview data-file-preview-title="{{ $contract->documentName() }}"><i class="fas fa-expand"></i> Open full screen</a>
<a href="{{ route('staff.contracts.document',$contract) }}" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Download</a>
</div>
<iframe class="ct-viewer" src="{{ route('staff.contracts.document',[$contract,'preview'=>1,'embed'=>1]) }}" title="Contract preview" loading="lazy"></iframe>
@else
<div class="admin-empty">The contract file is not available. Please contact HR.</div>
@endif

<dl class="ct-meta" style="margin-top:16px">
<dt>Contract status</dt><dd><span class="status-chip {{ $contract->status }}">{{ ucfirst($contract->status) }}</span></dd>
@if($contract->gross_salary !== null)<dt>Gross salary</dt><dd>{{ $contract->currency }} {{ number_format((float)$contract->gross_salary,2) }}</dd>@endif
<dt>Shared with you</dt><dd>{{ $contract->sent_for_signature_at?->format('d M Y H:i') ?: '—' }}@if($contract->sender) <span class="ct-muted">by {{ $contract->sender->name }}</span>@endif</dd>
</dl>
</div>

@if($contract->isSigned())
<div class="admin-panel ct-card">
<div class="admin-panel-head"><div><h2>Your signature</h2><p>Signed on {{ $contract->signed_at?->format('d M Y \a\t H:i:s') }} and returned to HR.</p></div></div>
<div class="ct-grid">
<dl class="ct-meta">
<dt>Method</dt><dd>{{ $contract->signature_method === 'drawn' ? 'Drawn on screen' : 'Uploaded signature image' }}</dd>
@if($contract->signer_comment)<dt>Your comment</dt><dd>{{ $contract->signer_comment }}</dd>@endif
<dt>Signature certificate</dt><dd><span class="ct-actions"><a href="{{ route('staff.contracts.certificate',[$contract,'preview'=>1]) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener" data-file-preview data-file-preview-title="Signature certificate"><i class="fas fa-eye"></i> Preview</a> <a href="{{ route('staff.contracts.certificate',$contract) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-pdf"></i> Download PDF</a></span></dd>
</dl>
<div class="ct-signature-box"><img src="{{ route('staff.contracts.signature',$contract) }}" alt="Your signature"><small>{{ $contract->signed_at?->format('d M Y H:i') }}</small></div>
</div>
</div>
@elseif($contract->isAwaitingSignature() && $contract->document_path)
<div class="admin-panel ct-card">
<div class="admin-panel-head"><div><h2>Sign and send back</h2><p>Draw your signature with your finger or mouse, or upload an image of it.</p></div></div>

<form method="POST" action="{{ route('staff.contracts.sign',$contract) }}" enctype="multipart/form-data" class="ct-sign-form" data-sign-form>@csrf
<div class="ct-methods" role="radiogroup" aria-label="Signature method">
<label class="ct-method"><input type="radio" name="signature_method" value="drawn" @checked($method === 'drawn')> <i class="fas fa-pen-nib"></i> Draw signature</label>
<label class="ct-method"><input type="radio" name="signature_method" value="uploaded" @checked($method === 'uploaded')> <i class="fas fa-image"></i> Upload image</label>
</div>

<div class="form-group" data-sign-panel="drawn" @if($method !== 'drawn') hidden @endif>
<div class="ct-pad-wrap"><canvas class="ct-pad" data-signature-pad aria-label="Signature drawing area"></canvas><span class="ct-pad-line"></span><span class="ct-pad-hint" data-signature-hint>Sign here</span></div>
<div class="ct-pad-tools"><small class="ct-muted">Use your finger, stylus or mouse.</small><button type="button" class="btn btn-outline btn-sm" data-signature-clear><i class="fas fa-eraser"></i> Clear</button></div>
<input type="hidden" name="signature_data" data-signature-data>
</div>

<div class="form-group" data-sign-panel="uploaded" @if($method !== 'uploaded') hidden @endif>
<label class="ct-label" for="signatureFile">Signature image</label>
<input type="file" id="signatureFile" name="signature_file" accept="image/png,image/jpeg,.png,.jpg,.jpeg" data-signature-file>
<small class="ct-muted">PNG or JPG, up to 2 MB. A clear photo or scan of your signature on white paper works best.</small>
<img class="ct-upload-preview" alt="Selected signature" data-signature-file-preview>
</div>

@error('signature')<p class="ct-error">{{ $message }}</p>@enderror
@error('signature_data')<p class="ct-error">{{ $message }}</p>@enderror
@error('signature_file')<p class="ct-error">{{ $message }}</p>@enderror

<div class="form-group"><label class="ct-label" for="signerComment">Comment for HR (optional)</label><textarea id="signerComment" name="signer_comment" rows="3" maxlength="1000">{{ old('signer_comment') }}</textarea></div>

<div class="form-group"><label><input type="checkbox" name="agree" value="1" required @checked(old('agree'))> I have read this contract and agree that my electronic signature is legally binding.</label>@error('agree')<p class="ct-error">{{ $message }}</p>@enderror</div>

<p class="ct-error" data-signature-error hidden></p>
<button class="btn btn-primary"><i class="fas fa-paper-plane"></i> Sign &amp; send back to HR</button>
</form>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var form = document.querySelector('[data-sign-form]');
    if (!form) return;

    var canvas = form.querySelector('[data-signature-pad]');
    var dataInput = form.querySelector('[data-signature-data]');
    var hint = form.querySelector('[data-signature-hint]');
    var errorBox = form.querySelector('[data-signature-error]');
    var fileInput = form.querySelector('[data-signature-file]');
    var filePreview = form.querySelector('[data-signature-file-preview]');
    var ctx = canvas.getContext('2d');
    var drawing = false, hasInk = false, last = null;

    function method() {
        var checked = form.querySelector('input[name="signature_method"]:checked');
        return checked ? checked.value : 'drawn';
    }

    function showPanels() {
        form.querySelectorAll('[data-sign-panel]').forEach(function (panel) {
            panel.hidden = panel.getAttribute('data-sign-panel') !== method();
        });
        if (method() === 'drawn') resize();
    }

    function setInk(value) {
        hasInk = value;
        canvas.classList.toggle('has-ink', value);
        if (hint) hint.style.display = value ? 'none' : '';
    }

    function resize() {
        var rect = canvas.getBoundingClientRect();
        if (!rect.width) return;
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var width = Math.round(rect.width * ratio), height = Math.round(rect.height * ratio);
        if (canvas.width === width && canvas.height === height) return;

        var previous = hasInk ? canvas.toDataURL('image/png') : null;
        canvas.width = width;
        canvas.height = height;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#111827';

        if (previous) {
            var image = new Image();
            image.onload = function () { ctx.drawImage(image, 0, 0, rect.width, rect.height); };
            image.src = previous;
        }
    }

    function point(event) {
        var rect = canvas.getBoundingClientRect();
        return { x: event.clientX - rect.left, y: event.clientY - rect.top };
    }

    function stroke(from, to) {
        ctx.beginPath();
        ctx.moveTo(from.x, from.y);
        ctx.lineTo(to.x, to.y);
        ctx.stroke();
    }

    canvas.addEventListener('pointerdown', function (event) {
        if (event.button !== undefined && event.button > 0) return;
        event.preventDefault();
        resize();
        drawing = true;
        last = point(event);
        if (canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
        stroke(last, { x: last.x + 0.1, y: last.y + 0.1 });
        setInk(true);
    });

    canvas.addEventListener('pointermove', function (event) {
        if (!drawing) return;
        event.preventDefault();
        var events = event.getCoalescedEvents ? event.getCoalescedEvents() : [event];
        (events.length ? events : [event]).forEach(function (e) {
            var next = point(e);
            stroke(last, next);
            last = next;
        });
    });

    ['pointerup', 'pointercancel', 'pointerleave'].forEach(function (name) {
        canvas.addEventListener(name, function () { drawing = false; last = null; });
    });

    form.querySelector('[data-signature-clear]').addEventListener('click', function () {
        ctx.save();
        ctx.setTransform(1, 0, 0, 1, 0, 0);
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.restore();
        dataInput.value = '';
        setInk(false);
    });

    form.querySelectorAll('input[name="signature_method"]').forEach(function (radio) {
        radio.addEventListener('change', showPanels);
    });

    if (fileInput && filePreview) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) { filePreview.style.display = 'none'; return; }
            filePreview.src = URL.createObjectURL(file);
            filePreview.style.display = 'block';
        });
    }

    function fail(message, event) {
        event.preventDefault();
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    form.addEventListener('submit', function (event) {
        errorBox.hidden = true;
        if (method() === 'drawn') {
            if (!hasInk) return fail('Please draw your signature before submitting.', event);
            dataInput.value = canvas.toDataURL('image/png');
            if (fileInput) fileInput.value = '';
        } else {
            dataInput.value = '';
            var file = fileInput && fileInput.files && fileInput.files[0];
            if (!file) return fail('Please choose a signature image to upload.', event);
            if (file.size > 2 * 1024 * 1024) return fail('The signature image may not be larger than 2 MB.', event);
        }
    });

    window.addEventListener('resize', resize);
    showPanels();
})();
</script>
@endpush
