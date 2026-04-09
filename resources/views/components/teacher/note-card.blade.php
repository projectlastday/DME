@props([
    'note',
    'studentId',
])

@php
    $noteId = data_get($note, 'id');
    $images = array_values(array_filter(
        (array) data_get($note, 'images', []),
        static fn (mixed $image): bool => filled(data_get($image, 'display_url')) || filled(data_get($image, 'url'))
    ));

    if ($images === [] && filled($noteId)) {
        $images = \Illuminate\Support\Facades\DB::table('note_images')
            ->where('note_id', $noteId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (object $image): array => [
                'id' => (int) $image->id,
                'display_url' => "/note-images/{$image->id}",
            ])
            ->all();
    }

    $isEditableTeacherNote = (string) data_get($note, 'author_id') === (string) auth()->id()
        && \App\Models\User::roleMatches(data_get($note, 'author_role_snapshot', data_get($note, 'author_role')), \App\Models\User::ROLE_TEACHER);
    $createdAt = data_get($note, 'created_at');
    $formattedCreatedAt = filled($createdAt)
        ? \Illuminate\Support\Carbon::parse($createdAt)->locale('id')->translatedFormat('j M Y, H.i')
        : null;

    // Render body: sanitize HTML, or fall back to nl2br for legacy plain-text notes
    $rawBody = (string) data_get($note, 'body', '');
    $isHtml = $rawBody !== strip_tags($rawBody);
    $renderedBody = $isHtml
        ? \App\Support\HtmlSanitizer::sanitize($rawBody)
        : ($rawBody !== '' ? nl2br(e($rawBody)) : null);
@endphp

<article class="rounded-3xl border border-slate-100 bg-white p-5 shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-6">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div class="flex flex-col gap-1">
            <h3 class="font-heading text-base font-bold text-slate-900 leading-none">
                {{ data_get($note, 'author_name_snapshot', 'Bapak/Ibu Guru') }}
            </h3>
            <p class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ $formattedCreatedAt ?? $createdAt }}
            </p>
        </div>

        @if ($isEditableTeacherNote)
            <div class="flex shrink-0 items-center gap-5 text-slate-400">
                <a href="{{ route('teacher.notes.edit', data_get($note, 'id')) }}" class="rounded-full p-1 text-slate-400 transition-colors hover:text-amber-500" aria-label="Ubah">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </a>

                <button
                    type="button"
                    class="rounded-full p-1 text-slate-400 transition-colors hover:text-red-500"
                    aria-label="Hapus"
                    data-teacher-dialog-open="delete-note-{{ $noteId }}"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        @endif
    </div>

    @if (filled($renderedBody))
        <div class="note-rich-body mb-4 break-words leading-relaxed text-slate-800">{!! $renderedBody !!}</div>
    @endif

    @if ($images !== [])
        <div class="mt-4 flex gap-3 overflow-x-auto">
            @foreach ($images as $image)
                @php
                    $imageId = data_get($image, 'id', data_get($image, 'image_id'));
                    $previewUrl = data_get($image, 'display_url', data_get($image, 'url'));
                @endphp

                <button
                    type="button"
                    class="relative block shrink-0 overflow-hidden rounded-[1.75rem] border border-slate-100 bg-slate-100"
                    style="width: 13rem; height: 13rem;"
                    data-image-lightbox-open="note-image-{{ $imageId }}"
                    aria-label="Lihat gambar {{ $imageId }}"
                >
                    <img
                        src="{{ $previewUrl }}"
                        alt="Gambar catatan {{ $imageId }}"
                        class="absolute inset-0 h-full w-full object-cover"
                    >
                </button>
            @endforeach
        </div>
    @endif
</article>

@if ($images !== [])
    @foreach ($images as $image)
        @php
            $imageId = data_get($image, 'id', data_get($image, 'image_id'));
            $previewUrl = data_get($image, 'display_url', data_get($image, 'url'));
        @endphp

        <div class="teacher-dialog dme-image-lightbox" data-image-lightbox="note-image-{{ $imageId }}" hidden>
            <div class="teacher-dialog__backdrop" data-image-lightbox-close></div>
            <div class="teacher-dialog__panel dme-image-lightbox__panel">
                <button type="button" class="dme-image-lightbox__close" data-image-lightbox-close aria-label="Tutup gambar">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <img src="{{ $previewUrl }}" alt="Gambar catatan {{ $imageId }}" class="dme-image-lightbox__image">
            </div>
        </div>
    @endforeach
@endif

@if ($isEditableTeacherNote && filled($noteId))
    <div class="teacher-dialog" data-teacher-dialog="delete-note-{{ $noteId }}" hidden>
        <div class="teacher-dialog__backdrop" data-teacher-dialog-close></div>
        <div class="teacher-dialog__panel">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="mb-2 text-center font-heading text-2xl font-bold text-slate-900">Hapus catatan ini?</h3>
            <p class="mb-6 text-center leading-relaxed text-slate-600">catatan dan foto ini akan dihapus secara permanen.</p>
            <form method="POST" action="{{ route('teacher.notes.destroy', $noteId) }}" class="teacher-dialog__form">
                @csrf
                @method('DELETE')
                <div class="mt-4 flex gap-3">
                    <button class="h-12 flex-1 rounded-full bg-slate-100 px-4 font-semibold text-slate-700 transition-colors hover:bg-slate-200" type="button" data-teacher-dialog-close>Batal</button>
                    <button class="h-12 flex-1 rounded-full bg-red-600 px-4 font-semibold text-white transition-colors hover:bg-red-700" type="submit">Hapus</button>
                </div>
            </form>
        </div>
    </div>
@endif

<script>
    if (! window.__dmeImageLightboxInit) {
        window.__dmeImageLightboxInit = true;
        document.querySelectorAll('[data-image-lightbox]').forEach((dialog) => {
            if (dialog.parentElement !== document.body) {
                document.body.appendChild(dialog);
            }
        });

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-image-lightbox-open]');
            if (openButton) {
                const dialog = document.querySelector(`[data-image-lightbox="${openButton.dataset.imageLightboxOpen}"]`);
                if (dialog) {
                    dialog.hidden = false;
                    document.body.style.overflow = 'hidden';
                    document.body.classList.add('dme-lightbox-open');
                }
                return;
            }

            const closeButton = event.target.closest('[data-image-lightbox-close]');
            if (closeButton) {
                const dialog = closeButton.closest('[data-image-lightbox]');
                if (dialog) {
                    dialog.hidden = true;
                    document.body.style.overflow = '';
                    document.body.classList.remove('dme-lightbox-open');
                }
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-image-lightbox]').forEach((dialog) => {
                dialog.hidden = true;
            });
            document.body.style.overflow = '';
            document.body.classList.remove('dme-lightbox-open');
        });
    }
</script>
