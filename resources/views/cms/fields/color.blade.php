{{-- Hex colour text field with a native picker; empty means "use the default". --}}
@php($fv = (string) ($value ?? ''))
<div class="cms-color" data-color>
    <input type="color" value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', $fv) ? $fv : ($fallback ?? '#800000') }}" data-color-picker aria-label="Pick colour">
    <input type="text" @if(!empty($name)) name="{{ $name }}" @endif value="{{ $fv }}" {{ $attr }}="{{ $field }}" placeholder="{{ $placeholder ?? 'Default' }}" maxlength="7" data-color-text>
    <button type="button" class="btn-icon" title="Clear" data-color-clear><i class="fas fa-xmark"></i></button>
</div>
