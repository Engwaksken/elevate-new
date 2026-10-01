{{--
    Accessible tabs for long forms. Panels stay inside the same <form>, so every field submits together.

    <x-form-tabs id="user-create" label="User details" :tabs="[
        'account' => ['label' => 'Account', 'icon' => 'fa-id-card', 'fields' => ['name', 'email']],
        'roles'   => ['label' => 'Roles & Access', 'fields' => ['roles', 'roles.*']],
    ]">
        <x-form-tab name="account"> ...fields... </x-form-tab>
        <x-form-tab name="roles"> ...fields... </x-form-tab>
    </x-form-tabs>

    `fields` lists the input names in each tab; the first tab holding a Laravel validation error is
    opened automatically. Behaviour (keyboard, URL hash, browser validation) lives in
    partials/form-tabs-assets, which both layouts include.
--}}
@props(['id', 'tabs' => [], 'label' => 'Form sections'])
@php
    $formTabsErrorKeys = [];
    foreach ($tabs as $formTabKey => $formTab) {
        foreach ((array) ($formTab['fields'] ?? []) as $formTabField) {
            if (isset($errors) && $errors->has($formTabField)) {
                $formTabsErrorKeys[] = $formTabKey;
                break;
            }
        }
    }
    $formTabsActive = $formTabsErrorKeys[0] ?? array_key_first($tabs);
@endphp
<div {{ $attributes->merge(['class' => 'eh-tabs form-tabs']) }} id="{{ $id }}" data-form-tabs @if($formTabsErrorKeys) data-error-tab="{{ $formTabsErrorKeys[0] }}" @endif>
    <div class="eh-tab-nav form-tabs__nav" role="tablist" aria-label="{{ $label }}">
        @foreach($tabs as $key => $tab)
            @php($isActive = $key === $formTabsActive)
            @php($hasError = in_array($key, $formTabsErrorKeys, true))
            <button
                type="button"
                class="eh-tab-button form-tabs__tab {{ $isActive ? 'active' : '' }} {{ $hasError ? 'has-error' : '' }}"
                role="tab"
                id="{{ $id }}-{{ $key }}-tab"
                aria-controls="{{ $id }}-{{ $key }}"
                aria-selected="{{ $isActive ? 'true' : 'false' }}"
                tabindex="{{ $isActive ? '0' : '-1' }}"
                data-form-tab="{{ $key }}"
            >
                @if(!empty($tab['icon']))
                    <i class="fas {{ $tab['icon'] }}" aria-hidden="true"></i>
                @endif
                <span>{{ $tab['label'] ?? ucfirst($key) }}</span>
                <span class="form-tabs__error-dot" aria-hidden="true"></span>
                <span class="form-tabs__sr" data-form-tab-error-text>{{ $hasError ? '(has errors)' : '' }}</span>
            </button>
        @endforeach
    </div>
    <div class="eh-tab-content form-tabs__panels">
        {{ $slot }}
    </div>
</div>
