@props([
    'bag' => 'default',
    'title' => '',
])

@php
    $viewErrors = $errors ?? new \Illuminate\Support\ViewErrorBag();
    $errorsBag = $bag === 'default' ? $viewErrors->getBag('default') : $viewErrors->getBag($bag);
@endphp

@if ($errorsBag->any())
    <div class="dme-alert dme-alert--danger" role="alert" aria-live="assertive">
        @if ($title !== '')
            <p class="dme-alert__title">{{ $title }}</p>
        @endif

        <ul class="dme-alert__list">
            @foreach ($errorsBag->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
