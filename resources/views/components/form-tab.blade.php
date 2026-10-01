{{-- One panel of <x-form-tabs>. See components/form-tabs.blade.php. --}}
@aware(['id', 'tabs' => []])
@props(['name'])
@php
    $formTabActive = array_key_first($tabs);
    foreach ($tabs as $formTabKey => $formTab) {
        foreach ((array) ($formTab['fields'] ?? []) as $formTabField) {
            if (isset($errors) && $errors->has($formTabField)) {
                $formTabActive = $formTabKey;
                break 2;
            }
        }
    }
    $formTabIsActive = $name === $formTabActive;
@endphp
<section
    {{ $attributes->merge(['class' => 'eh-tab-pane form-tabs__panel'.($formTabIsActive ? ' active' : '')]) }}
    role="tabpanel"
    id="{{ $id }}-{{ $name }}"
    aria-labelledby="{{ $id }}-{{ $name }}-tab"
    data-form-tab-panel="{{ $name }}"
    @unless($formTabIsActive) hidden @endunless
>
    {{ $slot }}
</section>
