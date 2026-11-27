@props([
    'name' => 'files',
    'label' => 'Files',
    'mimes' => 'lesson_mimes',
    'hint' => null,
    'max' => null,
    'id' => null,
    // Explicit allowed extensions (overrides the config list named by $mimes).
    'extensions' => null,
    'required' => false,
])
@php
    $max = $max ?? (int) config('elearning.max_files_per_upload', 10);
    $inputId = $id ?? 'mfi-'.\Illuminate\Support\Str::random(8);
    $extensionList = $extensions ?? explode(',', (string) config('elearning.'.$mimes));
    $acceptAttr = collect($extensionList)
        ->map(fn ($extension) => '.'.trim($extension))
        ->join(',');
    $fileService = app(\App\Services\Learning\LearningFileService::class);
    $maxMb = $fileService->maxFileMb();
    $maxFileBytes = $fileService->maxFileKb() * 1024;
    // Leave ~1 MB of the form limit for the other fields.
    $maxTotalBytes = $fileService->maxRequestKb() > 0 ? max(0, ($fileService->maxRequestKb() - 1024) * 1024) : 0;
@endphp

@once
<style>
    .mfi-list { display:flex; flex-direction:column; gap:4px; margin:6px 0 0; padding:0; list-style:none; font-size:.85rem; }
    .mfi-list li { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:4px 8px; border:1px dashed var(--border-color, #d0d5dd); border-radius:8px; }
    .mfi-list li span { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .mfi-list button { border:0; background:transparent; cursor:pointer; color:inherit; padding:2px 6px; font-size:1rem; }
    .mfi-error { color:#b42318; font-size:.8rem; }
</style>
<script>
(function () {
    if (window.__mfiReady) return;
    window.__mfiReady = true;

    function sizeLabel(bytes) {
        return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
    }

    function render(input) {
        var list = document.getElementById(input.id + '-list');
        var error = document.getElementById(input.id + '-error');
        if (!list) return;
        list.innerHTML = '';
        var max = parseInt(input.dataset.maxFiles || '10', 10);
        var maxBytes = parseInt(input.dataset.maxBytes || '0', 10);
        var maxTotal = parseInt(input.dataset.maxTotalBytes || '0', 10);
        var total = 0, tooBig = [];

        Array.prototype.forEach.call(input.files, function (file, index) {
            var li = document.createElement('li');
            var label = document.createElement('span');
            total += file.size;
            label.textContent = file.name + ' (' + sizeLabel(file.size) + ')';
            if (maxBytes && file.size > maxBytes) { tooBig.push(file.name); li.style.borderColor = '#b42318'; label.textContent += ' - too large'; }
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.setAttribute('aria-label', 'Remove ' + file.name);
            remove.innerHTML = '&times;';
            remove.addEventListener('click', function () {
                if (typeof DataTransfer === 'undefined') { input.value = ''; input._mfiPrevious = null; render(input); return; }
                var dt = new DataTransfer();
                Array.prototype.forEach.call(input.files, function (f, i) { if (i !== index) dt.items.add(f); });
                input.files = dt.files;
                input._mfiPrevious = input.files;
                render(input);
            });
            li.appendChild(label);
            li.appendChild(remove);
            list.appendChild(li);
        });

        var message = '';
        if (input.files.length > max) message = max === 1 ? 'Only one file can be uploaded.' : 'You can upload up to ' + max + ' files at a time.';
        else if (tooBig.length) message = tooBig.join(', ') + (tooBig.length === 1 ? ' is' : ' are') + ' larger than ' + sizeLabel(maxBytes) + '. Remove ' + (tooBig.length === 1 ? 'it' : 'them') + ' or use a link instead.';
        else if (maxTotal && total > maxTotal) message = 'These files add up to ' + sizeLabel(total) + '; the server accepts up to ' + sizeLabel(maxTotal) + ' per save. Upload them in smaller batches.';
        if (error) error.textContent = message;
        // Block the save instead of letting the server silently drop the files.
        input.setCustomValidity(message);
    }

    document.addEventListener('change', function (event) {
        var input = event.target;
        if (!input.matches || !input.matches('input[type=file][data-multi-file-input]')) return;

        // Picking again adds to the earlier selection instead of replacing it.
        if (typeof DataTransfer !== 'undefined' && input._mfiPrevious && input._mfiPrevious.length) {
            var dt = new DataTransfer();
            Array.prototype.forEach.call(input._mfiPrevious, function (f) { dt.items.add(f); });
            Array.prototype.forEach.call(input.files, function (f) { dt.items.add(f); });
            input.files = dt.files;
        }

        input._mfiPrevious = input.files;
        render(input);
    });

    // A browser that kept a selection across a page restore still gets checked.
    document.addEventListener('submit', function (event) {
        var inputs = event.target.querySelectorAll ? event.target.querySelectorAll('input[type=file][data-multi-file-input]') : [];
        Array.prototype.forEach.call(inputs, function (input) { render(input); });
        for (var i = 0; i < inputs.length; i++) {
            if (inputs[i].validationMessage) { event.preventDefault(); inputs[i].reportValidity(); return; }
        }
    }, true);

    document.addEventListener('reset', function (event) {
        event.target.querySelectorAll && event.target.querySelectorAll('input[type=file][data-multi-file-input]').forEach(function (input) {
            input._mfiPrevious = null;
            setTimeout(function () { render(input); }, 0);
        });
    });
})();
</script>
@endonce

<div {{ $attributes->merge(['class' => 'mfi']) }}>
    <label for="{{ $inputId }}">{{ $label }}</label>
    <input type="file" id="{{ $inputId }}" name="{{ $name }}[]" @if($max > 1) multiple @endif accept="{{ $acceptAttr }}" data-multi-file-input data-max-files="{{ $max }}" data-max-bytes="{{ $maxFileBytes }}" data-max-total-bytes="{{ $maxTotalBytes }}" @required($required)>
    <small class="form-hint" style="display:block">{{ $hint ?? ($max === 1 ? "One file, up to {$maxMb} MB." : "Up to {$max} files, {$maxMb} MB each. You can remove a file from the list before saving.") }}</small>
    <ul class="mfi-list" id="{{ $inputId }}-list" aria-live="polite"></ul>
    <div class="mfi-error" id="{{ $inputId }}-error" role="alert"></div>
</div>
