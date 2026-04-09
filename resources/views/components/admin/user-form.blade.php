@props([
    'action',
    'method' => 'POST',
    'submitLabel',
    'roleLabel',
    'user' => null,
    'showPasswordFields' => false,
])

<section class="dme-section-card">
    <form method="POST" action="{{ $action }}" class="dme-form-stack">
        @csrf
        @if ($method !== 'POST')
            @method($method)
        @endif

        <div class="dme-field">
            <label for="name" class="dme-field__label">Nama {{ $roleLabel }}</label>
            <input
                class="dme-field__control"
                id="name"
                name="name"
                type="text"
                value="{{ old('name', $user?->name) }}"
                required
            >
        </div>

        @if ($showPasswordFields)
            <div class="dme-field">
                <label for="password" class="dme-field__label">Kata sandi awal</label>
                <input class="dme-field__control" id="password" name="password" type="text" required>
            </div>
        @endif

        <div class="dme-action-row">
            <button type="submit" class="dme-button">{{ $submitLabel }}</button>
        </div>
    </form>
</section>
