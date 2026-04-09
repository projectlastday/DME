@props([
    'title',
    'subtitle' => null,
    'badge' => null,
])

<div class="teacher-header">
    <div style="display: flex; justify-content: space-between; align-items: start; gap: 16px; flex-wrap: wrap;">
        <div style="display: grid; gap: 8px;">
            <h1 style="margin: 0; font-size: 1.45rem;">{{ $title }}</h1>
            @if ($subtitle)
                <p style="margin: 0; color: var(--muted); line-height: 1.6;">{{ $subtitle }}</p>
            @endif
        </div>

        @if ($badge)
            <span style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; background: rgba(180,83,9,0.12); color: var(--accent-strong); font-size: 0.95rem; font-weight: 700;">
                {{ $badge }}
            </span>
        @endif
    </div>
</div>
