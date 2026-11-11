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
    $maxMb = (int) round(((int) config('elearning.max_file_kb', 51200)) / 1024);
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

        Array.prototype.forEach.call(input.files, function (file, index) {
            var li = document.createElement('li');
            var label = document.createElement('span');
            label.textContent = file.name + ' (' + sizeLabel(file.size) + ')';
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

        if (error) error.textContent = input.files.length > max ? (max === 1 ? 'Only one file can be uploaded.' : 'You can upload up to ' + max + ' files at a time.') : '';
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
    <input type="file" id="{{ $inputId }}" name="{{ $name }}[]" @if($max > 1) multiple @endif accept="{{ $acceptAttr }}" data-multi-file-input data-max-files="{{ $max }}" @required($required)>
    <small class="form-hint" style="display:block">{{ $hint ?? ($max === 1 ? "One file, up to {$maxMb} MB." : "Up to {$max} files, {$maxMb} MB each. You can remove a file from the list before saving.") }}</small>
    <ul class="mfi-list" id="{{ $inputId }}-list" aria-live="polite"></ul>
    <div class="mfi-error" id="{{ $inputId }}-error" role="alert"></div>
</div>
