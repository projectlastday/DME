@props([
    'name'        => 'body',
    'value'       => '',
    'placeholder' => 'Tulis catatan baru...',
    'maxChars'    => 5000,
    'id'          => 'rich-editor-' . uniqid(),
])

@php
    $bodyValue = old($name, $value);
@endphp

<div class="dme-re" data-rich-editor data-re-max-chars="{{ $maxChars }}" id="{{ $id }}">
    {{-- Toolbar (populated by JS) --}}
    <div data-re-toolbar></div>

    {{-- Editable area --}}
    <div
        class="dme-re-editable"
        data-re-editable
        contenteditable="true"
        role="textbox"
        aria-multiline="true"
        aria-label="{{ $placeholder }}"
        data-placeholder="{{ $placeholder }}"
    >{!! $bodyValue !!}</div>

    {{-- Hidden input for form submission --}}
    <input type="hidden" name="{{ $name }}" value="{{ $bodyValue }}" data-re-input>
</div>
