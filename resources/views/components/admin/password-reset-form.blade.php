@props([
    'action',
    'heading',
    'submitLabel',
])

<section class="dme-section-card dme-section-stack">
    <div class="dme-section-header__content">
        <h2 class="dme-section-title">{{ $heading }}</h2>
    </div>

    <form method="POST" action="{{ $action }}" class="dme-form-stack">
        @csrf

        <div class="dme-field">
            <label for="reset_password" class="dme-field__label">Kata sandi baru</label>
            <input class="dme-field__control" id="reset_password" name="password" type="text" required>
        </div>

        <div class="dme-action-row">
            <button type="submit" class="dme-button--danger">{{ $submitLabel }}</button>
        </div>
    </form>
</section>
