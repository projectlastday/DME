@php
    $flashMessages = collect([
        ['key' => 'success', 'tone' => 'success'],
        ['key' => 'status', 'tone' => 'success'],
        ['key' => 'warning', 'tone' => 'warning'],
        ['key' => 'error', 'tone' => 'danger'],
    ])->filter(fn (array $message) => session()->has($message['key']));
@endphp

@if ($flashMessages->isNotEmpty())
    <div class="dme-stack">
        @foreach ($flashMessages as $message)
            <div class="dme-alert dme-alert--{{ $message['tone'] }}" role="status">
                <p>{{ session($message['key']) }}</p>
            </div>
        @endforeach
    </div>
@endif
