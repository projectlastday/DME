@props([
    'heading',
    'description' => null,
])

<header class="dme-section-header">
    <div class="dme-section-header__content">
        <div>
            <h2 class="dme-section-title">{{ $heading }}</h2>
            @if ($description)
                <p class="dme-section-copy">{{ $description }}</p>
            @endif
        </div>
    </div>

    @if (trim($slot) !== '')
        <nav class="dme-action-row" aria-label="Aksi {{ $heading }}">
            <div class="dme-action-row">
                {{ $slot }}
            </div>
        </nav>
    @endif
</header>
