@props([
    'action',
    'method' => 'POST',
    'note' => null,
    'submitLabel' => 'Simpan',
])

@php
    $images = array_values(array_filter(
        (array) data_get($note, 'images', []),
        static fn (mixed $image): bool => filled(data_get($image, 'display_url')) || filled(data_get($image, 'url'))
    ));
    $noteId = data_get($note, 'id');

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

    $retainedImageIds = old('retained_image_ids', collect($images)->pluck('id')->all());
    $bodyValue = old('body', data_get($note, 'body', ''));
    $noteDateValue = old('note_date', data_get($note, 'note_date', now()->toDateString()));
    $isEditing = $note !== null;
@endphp

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" data-student-note-form>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    <input type="hidden" name="note_date" value="{{ $noteDateValue }}">
    <div data-student-image-payload-inputs></div>

    <div class="animate-slide-up rounded-[2rem] border border-amber-200 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] ring-2 ring-amber-100">
        <x-shared.rich-editor
            name="body"
            :value="$bodyValue"
            :placeholder="$isEditing ? 'Ubah catatan...' : 'Tulis catatan baru...'"
            id="student-note-editor"
        />

        <div class="hidden px-6 pb-4" data-student-image-preview-list></div>

        @if ($isEditing && $images !== [])
            <div class="flex gap-3 overflow-x-auto px-6 pb-4">
                @foreach ($images as $image)
                    @php
                        $imageId = (int) data_get($image, 'id');
                    @endphp
                    <input type="hidden" name="retained_image_ids[]" value="{{ $imageId }}">
                    <div class="relative block h-24 w-24 shrink-0 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-100 shadow-sm">
                        <img
                            src="{{ data_get($image, 'display_url', data_get($image, 'url')) }}"
                            alt="Gambar catatan {{ $imageId }}"
                            class="h-full w-full object-cover"
                        >
                    </div>
                @endforeach
            </div>
        @endif

        <div class="border-t border-slate-100 px-4 py-4">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <label class="relative flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-amber-50 text-amber-600 transition-colors hover:bg-amber-100" title="Tambah Foto">
                        <input
                            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            id="student-note-images"
                            name="images[]"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            multiple
                        >
                        <svg class="h-5 w-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </label>
                    <span class="text-xs font-semibold tracking-[0.18em] text-slate-400" data-student-note-count>0/5000</span>
                </div>

                <div class="flex items-center gap-2">
                    @if ($isEditing)
                        <a href="{{ route('student.notes.index', ['tab' => 'mine']) }}" class="inline-flex h-10 items-center rounded-full px-4 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-100">Batal</a>
                    @else
                        <button type="button" class="inline-flex h-10 items-center rounded-full px-4 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-100" data-student-composer-close>Batal</button>
                    @endif
                    <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-full bg-slate-900 px-6 text-sm font-semibold text-white transition-transform hover:bg-slate-800 active:scale-95">{{ $submitLabel }}</button>
                </div>
            </div>
        </div>
    </div>
</form>
