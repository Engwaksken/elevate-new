@extends('layouts.admin')
@section('title','Design certificate template | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Certificates</span>
        <h1>Design: {{ $template->name }}</h1>
        <p>Place each certificate field on the background and choose its font, size, colour and style. Drag a field on the preview or fine-tune the values on the right.</p>
    </div>
    <div class="admin-page-actions">
        <a href="{{ route('admin.elearning.certificates.templates.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> All templates</a>
    </div>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-error">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="{{ route('admin.elearning.certificates.templates.design.update', $template) }}" data-cert-designer>
@csrf @method('PUT')
<div class="cert-designer">
<style>
.cert-designer{display:grid;grid-template-columns:minmax(0,1.35fr) minmax(320px,1fr);gap:22px;align-items:start}
.cert-stage-wrap{position:sticky;top:16px}
.cert-stage{position:relative;width:100%;border:1px solid #eadede;border-radius:12px;overflow:hidden;background:#fafafa}
.cert-stage img{display:block;width:100%;height:auto}
.cert-field{position:absolute;cursor:grab;white-space:pre-wrap;line-height:1.2;box-sizing:border-box;padding:2px 4px;border:1px dashed transparent;border-radius:4px}
.cert-field:hover,.cert-field.active{border-color:#800000;background:rgba(128,0,0,.05)}
.cert-field.disabled{opacity:.35}
.cert-field-controls{background:#fff;border:1px solid #eadede;border-radius:12px;max-height:calc(100vh - 120px);overflow:auto}
.cert-field-item{padding:14px 16px;border-bottom:1px solid #f0e7e7}
.cert-field-item h3{margin:0 0 4px;font-size:1rem;display:flex;align-items:center;gap:8px}
.cert-field-item .cert-sample{font-size:.8rem;color:#98a2b3;margin-bottom:8px}
.cert-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.cert-grid label{font-size:.78rem;font-weight:600;color:#475467;display:flex;flex-direction:column;gap:4px}
.cert-grid input,.cert-grid select{width:100%;min-height:36px;padding:6px 8px;border:1px solid #d0d5dd;border-radius:7px}
.cert-grid .full{grid-column:1 / -1}
.cert-styles{display:flex;flex-wrap:wrap;gap:12px;margin-top:10px;font-size:.83rem}
.cert-styles label{display:flex;align-items:center;gap:5px;font-weight:600;color:#475467}
@media(max-width:1000px){.cert-designer{grid-template-columns:1fr}.cert-stage-wrap{position:static}}
</style>

<div class="cert-stage-wrap">
    <div class="cert-stage" data-cert-stage data-orientation="{{ $template->orientation }}">
        <img src="{{ route('admin.elearning.certificates.templates.preview', $template) }}" alt="{{ $template->name }} background">
        @foreach($definitions as $key => $definition)
            @php $f = $layout[$key]; @endphp
            <div class="cert-field" data-field="{{ $key }}"
                 style="left:{{ (float)$f['x'] - ((float)$f['width']/2) }}%;top:{{ (float)$f['y'] }}%;width:{{ (float)$f['width'] }}%;text-align:{{ $f['align'] }};font-family:'{{ $f['font_family'] }}',sans-serif;font-size:{{ (float)$f['font_size'] }}px;color:{{ $f['color'] }};{{ $f['bold'] ? 'font-weight:bold;' : '' }}{{ $f['italic'] ? 'font-style:italic;' : '' }}{{ $f['underline'] ? 'text-decoration:underline;' : '' }}{{ (float)$f['letter_spacing'] != 0 ? 'letter-spacing:'.(float)$f['letter_spacing'].'px;' : '' }}{{ $f['enabled'] ? '' : 'opacity:.35;' }}">{{ $f['prefix'] }}{{ $definition['sample'] }}{{ $f['suffix'] }}</div>
        @endforeach
    </div>
    <p style="color:#667085;font-size:.82rem;margin-top:8px">Tip: drag a field on the preview to set its position. Percentages are relative to the page, so they scale with any paper size.</p>
</div>

<div class="cert-field-controls">
@foreach($definitions as $key => $definition)
    @php $f = $layout[$key]; @endphp
    <div class="cert-field-item" data-field-item="{{ $key }}">
        <h3><label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="layout[{{ $key }}][enabled]" value="1" data-field-toggle @checked($f['enabled'])> {{ $definition['label'] }}</label></h3>
        <div class="cert-sample">“{{ $definition['sample'] }}”</div>
        <div class="cert-grid">
            <label>X (%)<input type="number" step="0.1" min="0" max="100" name="layout[{{ $key }}][x]" value="{{ (float)$f['x'] }}" data-prop="x"></label>
            <label>Y (%)<input type="number" step="0.1" min="0" max="100" name="layout[{{ $key }}][y]" value="{{ (float)$f['y'] }}" data-prop="y"></label>
            <label>Width (%)<input type="number" step="0.1" min="1" max="100" name="layout[{{ $key }}][width]" value="{{ (float)$f['width'] }}" data-prop="width"></label>
            <label>Align<select name="layout[{{ $key }}][align]" data-prop="align">@foreach(['left','center','right'] as $a)<option value="{{ $a }}" @selected($f['align']===$a)>{{ ucfirst($a) }}</option>@endforeach</select></label>
            <label>Font<select name="layout[{{ $key }}][font_family]" data-prop="font_family">@foreach($fonts as $value=>$label)<option value="{{ $value }}" @selected($f['font_family']===$value)>{{ $label }}</option>@endforeach</select></label>
            <label>Size (px)<input type="number" step="1" min="6" max="120" name="layout[{{ $key }}][font_size]" value="{{ (float)$f['font_size'] }}" data-prop="font_size"></label>
            <label>Colour<input type="color" name="layout[{{ $key }}][color]" value="{{ $f['color'] }}" data-prop="color" style="height:36px;padding:2px"></label>
            <label>Letter spacing (px)<input type="number" step="0.1" min="-5" max="20" name="layout[{{ $key }}][letter_spacing]" value="{{ (float)$f['letter_spacing'] }}" data-prop="letter_spacing"></label>
            <label class="full">Prefix<input type="text" name="layout[{{ $key }}][prefix]" value="{{ $f['prefix'] }}" maxlength="60" data-prop="prefix"></label>
            <label class="full">Suffix<input type="text" name="layout[{{ $key }}][suffix]" value="{{ $f['suffix'] }}" maxlength="60" data-prop="suffix"></label>
        </div>
        <div class="cert-styles">
            <label><input type="checkbox" name="layout[{{ $key }}][bold]" value="1" data-prop="bold" @checked($f['bold'])> Bold</label>
            <label><input type="checkbox" name="layout[{{ $key }}][italic]" value="1" data-prop="italic" @checked($f['italic'])> Italic</label>
            <label><input type="checkbox" name="layout[{{ $key }}][underline]" value="1" data-prop="underline" @checked($f['underline'])> Underline</label>
            <label><input type="checkbox" name="layout[{{ $key }}][uppercase]" value="1" data-prop="uppercase" @checked($f['uppercase'])> UPPERCASE</label>
        </div>
    </div>
@endforeach
</div>
</div>
<div class="admin-page-actions" style="justify-content:flex-end;margin-top:18px">
    <a href="{{ route('admin.elearning.certificates.templates.index') }}" class="btn btn-outline">Cancel</a>
    <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save layout</button>
</div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var stage = document.querySelector('[data-cert-stage]');
    if (!stage) return;

    function refresh(key) {
        var item = document.querySelector('[data-field-item="' + key + '"]');
        var field = document.querySelector('[data-field="' + key + '"]');
        if (!item || !field) return;
        var val = function (prop) {
            var el = item.querySelector('[data-prop="' + prop + '"]');
            if (!el) return '';
            return el.type === 'checkbox' ? el.checked : el.value;
        };
        var x = parseFloat(val('x')) || 0;
        var width = parseFloat(val('width')) || 1;
        field.style.left = (x - width / 2) + '%';
        field.style.top = (parseFloat(val('y')) || 0) + '%';
        field.style.width = width + '%';
        field.style.textAlign = val('align');
        field.style.fontFamily = "'" + val('font_family') + "',sans-serif";
        field.style.fontSize = (parseFloat(val('font_size')) || 16) + 'px';
        field.style.color = val('color');
        field.style.fontWeight = val('bold') ? 'bold' : 'normal';
        field.style.fontStyle = val('italic') ? 'italic' : 'normal';
        field.style.textDecoration = val('underline') ? 'underline' : 'none';
        field.style.letterSpacing = (parseFloat(val('letter_spacing')) || 0) + 'px';
        var toggle = item.querySelector('[data-field-toggle]');
        field.classList.toggle('disabled', !(toggle && toggle.checked));
        var sample = item.querySelector('.cert-sample').textContent.replace(/[“”]/g, '');
        field.textContent = val('prefix') + sample + val('suffix');
    }

    document.querySelectorAll('[data-field-item]').forEach(function (item) {
        var key = item.dataset.fieldItem;
        item.addEventListener('input', function () { refresh(key); });
        item.addEventListener('change', function () { refresh(key); });
    });

    // Drag a field on the preview to set its position.
    document.querySelectorAll('[data-field]').forEach(function (field) {
        field.addEventListener('mousedown', function (event) {
            event.preventDefault();
            var key = field.dataset.field;
            var item = document.querySelector('[data-field-item="' + key + '"]');
            var rect = stage.getBoundingClientRect();
            var fieldRect = field.getBoundingClientRect();
            var offsetX = event.clientX - fieldRect.left;
            var offsetY = event.clientY - fieldRect.top;
            field.classList.add('active');

            function move(moveEvent) {
                var width = parseFloat(item.querySelector('[data-prop="width"]').value) || 1;
                var leftPct = ((moveEvent.clientX - offsetX - rect.left) / rect.width) * 100;
                var x = leftPct + width / 2;
                var y = ((moveEvent.clientY - offsetY - rect.top) / rect.height) * 100;
                x = Math.max(0, Math.min(100, x));
                y = Math.max(0, Math.min(100, y));
                item.querySelector('[data-prop="x"]').value = x.toFixed(1);
                item.querySelector('[data-prop="y"]').value = y.toFixed(1);
                refresh(key);
            }

            function up() {
                document.removeEventListener('mousemove', move);
                document.removeEventListener('mouseup', up);
                field.classList.remove('active');
            }

            document.addEventListener('mousemove', move);
            document.addEventListener('mouseup', up);
        });
    });
});
</script>
@endsection
