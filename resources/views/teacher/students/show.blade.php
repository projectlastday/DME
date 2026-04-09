@extends('layouts.app')

@section('title', data_get($student, 'name', 'Murid'))
@section('hide_shell_header', 'true')
@section('surfaceless_content', 'true')

@section('content')
    @php
        $editNote = $editNote ?? null;
        $isEditingNote = filled($editNote);
        $timelineGroups = count($noteGroups) > 0 ? $noteGroups : (filled($notes) ? ['Semua catatan' => $notes] : []);
        $showComposer = $isEditingNote || $errors->any() || filled(old('body')) || filled(old('note_date'));
        $availableDates = array_values(array_filter(array_keys($noteGroups), fn ($date) => $date !== ''));
    @endphp

    <div class="animate-fade-in pb-20">
        <div class="sticky top-0 z-20 -mx-4 mb-6 flex items-center gap-3 border-b border-slate-200/50 bg-transparent px-4 pb-4 pt-4 sm:-mx-6 sm:px-6">
            <a
                href="{{ route('teacher.students.index') }}"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition-all hover:border-amber-300 hover:text-amber-600"
                aria-label="Kembali"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="flex-1 overflow-hidden">
                <h1 class="truncate font-heading text-xl font-bold text-slate-900 sm:text-2xl">{{ data_get($student, 'name', 'Murid') }}</h1>
            </div>
            <div class="flex items-center gap-1">
                <button
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm transition-all hover:border-amber-300 hover:text-amber-500"
                    type="button"
                    data-teacher-dialog-open="rename-student"
                    aria-label="Ubah nama murid"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                </button>
                <button
                    class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 shadow-sm transition-all hover:border-red-300 hover:text-red-500"
                    type="button"
                    data-teacher-dialog-open="delete-student"
                    aria-label="Hapus murid"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="mb-10">
            <span class="sr-only">Buat catatan</span>
            <div class="flex gap-3 animate-fade-in {{ $showComposer ? 'hidden' : '' }}" data-teacher-composer-shortcuts>
                <button
                    class="group flex flex-1 items-center gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 shadow-sm transition-all hover:border-amber-200 hover:shadow-md active:scale-[0.97]"
                    type="button"
                    data-teacher-composer-open="note"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500 transition-transform group-hover:scale-110">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-800">Catatan</span>
                </button>
                <button
                    class="group flex flex-1 items-center gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 shadow-sm transition-all hover:border-blue-200 hover:shadow-md active:scale-[0.97]"
                    type="button"
                    data-teacher-composer-open="photo"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-500 transition-transform group-hover:scale-110">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-sm font-bold text-slate-800">Gambar</span>
                </button>
            </div>

            <div class="{{ $showComposer ? '' : 'hidden' }}" data-teacher-composer-form>
                <x-teacher.note-form
                    :action="$isEditingNote ? route('teacher.notes.update', data_get($editNote, 'id')) : route('teacher.notes.store', data_get($student, 'id'))"
                    :method="$isEditingNote ? 'PUT' : 'POST'"
                    :note="$editNote"
                    :student-id="data_get($student, 'id')"
                    :submit-label="$isEditingNote ? 'Perbarui' : 'Buat catatan'"
                />
            </div>
        </div>

        @if (count($availableDates) > 0)
            <div class="-mx-4 mb-6 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:px-0">
                @foreach ($availableDates as $index => $noteDate)
                    @php
                        $formattedDate = \Illuminate\Support\Carbon::parse($noteDate)->locale('id')->translatedFormat('j M');
                    @endphp
                    <span @class([
                        'whitespace-nowrap rounded-full px-5 py-2 text-sm font-semibold transition-all',
                        'bg-slate-900 text-white shadow-md shadow-slate-900/10' => $index === 0,
                        'border border-slate-200 bg-white text-slate-600' => $index !== 0,
                    ])>
                        {{ $formattedDate }}
                    </span>
                @endforeach
            </div>
        @endif

        @if (count($timelineGroups) === 0)
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white/50 px-4 py-12 text-center">
                <h3 class="mb-1 font-semibold text-slate-900">Belum ada catatan</h3>
                <p class="text-sm text-slate-500">Tambahkan catatan atau foto pertama untuk murid ini.</p>
            </div>
        @else
            <div class="space-y-6">
                @foreach ($timelineGroups as $groupNotes)
                    @foreach ($groupNotes as $note)
                        <x-teacher.note-card :note="$note" :student-id="data_get($student, 'id')" />
                    @endforeach
                @endforeach
            </div>
        @endif

        <div class="teacher-dialog" data-teacher-dialog="rename-student" hidden>
            <div class="teacher-dialog__backdrop" data-teacher-dialog-close></div>
            <div class="teacher-dialog__panel">
                <h3 class="mb-1 text-center font-heading text-2xl font-bold text-slate-900">Ubah Nama</h3>
                <p class="mb-6 text-center text-sm text-slate-500">Perbarui nama untuk murid ini.</p>
                <form method="POST" action="{{ route('teacher.students.update', data_get($student, 'id')) }}" class="teacher-dialog__form">
                    @csrf
                    @method('PUT')
                    <input
                        class="mb-2 h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-center text-slate-900 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-amber-400"
                        type="text"
                        name="name"
                        value="{{ old('name', data_get($student, 'name')) }}"
                        placeholder="Nama murid"
                        autocomplete="off"
                        required
                    >
                    <div class="mt-4 flex gap-3">
                        <button class="h-12 flex-1 rounded-full bg-slate-100 px-4 font-semibold text-slate-700 transition-colors hover:bg-slate-200" type="button" data-teacher-dialog-close>Batal</button>
                        <button class="h-12 flex-1 rounded-full bg-slate-900 px-4 font-semibold text-white transition-colors hover:bg-slate-800" type="submit">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="teacher-dialog" data-teacher-dialog="delete-student" hidden>
            <div class="teacher-dialog__backdrop" data-teacher-dialog-close></div>
            <div class="teacher-dialog__panel">
                <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="mb-2 text-center font-heading text-2xl font-bold text-slate-900">Hapus murid ini?</h3>
                <p class="mb-6 text-center leading-relaxed text-slate-600">Anda akan menghapus <strong class="text-slate-900">{{ data_get($student, 'name') }}</strong> beserta semua catatan dan foto yang tersimpan. Tindakan ini permanen.</p>
                <form method="POST" action="{{ route('teacher.students.destroy', data_get($student, 'id')) }}" class="teacher-dialog__form">
                    @csrf
                    @method('DELETE')
                    <div class="mt-4 flex gap-3">
                        <button class="h-12 flex-1 rounded-full bg-slate-100 px-4 font-semibold text-slate-700 transition-colors hover:bg-slate-200" type="button" data-teacher-dialog-close>Batal</button>
                        <button class="h-12 flex-1 rounded-full bg-red-600 px-4 font-semibold text-white transition-colors hover:bg-red-700" type="submit">Hapus</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const composerShortcuts = document.querySelector('[data-teacher-composer-shortcuts]');
        const composerForm = document.querySelector('[data-teacher-composer-form]');
        const composerEditor = () => composerForm?.querySelector('[data-rich-editor]');
        const composerEditable = () => composerForm?.querySelector('[data-re-editable]');
        const composerPhotoInput = () => composerForm?.querySelector('#teacher-note-images');
        const composerCount = () => composerForm?.querySelector('[data-teacher-note-count]');
        const composerPreviewList = () => composerForm?.querySelector('[data-teacher-image-preview-list]');
        const composerFormElement = () => composerForm?.querySelector('[data-teacher-note-form]');
        const composerPayloadInputs = () => composerForm?.querySelector('[data-teacher-image-payload-inputs]');
        let composerSelectedFiles = [];
        let composerPreviewUrls = [];
        const syncComposerCount = () => {
            const editable = composerEditable();
            const count = composerCount();

            if (!editable || !count) return;

            count.textContent = `${(editable.textContent || '').length}/5000`;
        };
        const syncComposerFiles = () => {
            const input = composerPhotoInput();
            if (!input) return;

            try {
                const transfer = new DataTransfer();
                composerSelectedFiles.forEach((file) => transfer.items.add(file));
                input.files = transfer.files;
            } catch (error) {
                // Some mobile browsers do not allow programmatic FileList assignment.
            }
        };
        const renderComposerPreviews = () => {
            const previewList = composerPreviewList();
            if (!previewList) return;

            composerPreviewUrls.forEach((url) => URL.revokeObjectURL(url));
            composerPreviewUrls = [];

            if (composerSelectedFiles.length === 0) {
                previewList.innerHTML = '';
                previewList.classList.add('hidden');
                return;
            }

            previewList.classList.remove('hidden');
            previewList.className = 'flex gap-2 overflow-x-auto px-6 pb-4';
            previewList.innerHTML = composerSelectedFiles.map((file, index) => {
                const previewUrl = URL.createObjectURL(file);
                composerPreviewUrls.push(previewUrl);

                return `
                    <div class="relative h-32 w-32 shrink-0 overflow-hidden rounded-[1.75rem] border border-slate-200 bg-slate-100 shadow-sm">
                        <img src="${previewUrl}" alt="${file.name}" class="absolute inset-0 h-full w-full object-cover">
                        <button
                            type="button"
                            class="absolute right-3 top-3 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-white"
                            data-teacher-image-remove="${index}"
                            aria-label="Hapus gambar"
                        >
                            <svg class="h-5 w-5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;
            }).join('');
        };
        const resetComposerFiles = () => {
            composerSelectedFiles = [];
            syncComposerFiles();
            const payloadInputs = composerPayloadInputs();
            if (payloadInputs) payloadInputs.innerHTML = '';
            renderComposerPreviews();
        };
        const readComposerFileAsDataUrl = (file) => new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = () => reject(reader.error);
            reader.readAsDataURL(file);
        });
        const syncComposerPayloadInputs = async () => {
            const payloadInputs = composerPayloadInputs();
            if (!payloadInputs) return;

            payloadInputs.innerHTML = '';

            const payloads = await Promise.all(composerSelectedFiles.map(async (file) => ({
                name: file.name,
                type: file.type,
                data_url: await readComposerFileAsDataUrl(file),
            })));

            payloads.forEach((payload) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'new_image_payloads[]';
                input.value = JSON.stringify(payload);
                payloadInputs.appendChild(input);
            });
        };

        // Rich editor auto-inits via imported module.
        // Hook into its input event for character count.
        const editable = composerEditable();
        if (editable) {
            editable.addEventListener('input', syncComposerCount);
            syncComposerCount();
        }

        composerPhotoInput()?.addEventListener('change', async (event) => {
            const input = event.currentTarget;
            const files = Array.from(input.files ?? []);

            if (files.length === 0) return;

            composerSelectedFiles = [...composerSelectedFiles, ...files];
            syncComposerFiles();
            await syncComposerPayloadInputs();
            renderComposerPreviews();
        });
        composerPreviewList()?.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-teacher-image-remove]');
            if (!button) return;

            const index = Number(button.dataset.teacherImageRemove);
            if (Number.isNaN(index)) return;

            composerSelectedFiles.splice(index, 1);
            syncComposerFiles();
            await syncComposerPayloadInputs();
            renderComposerPreviews();
        });
        composerFormElement()?.addEventListener('submit', (event) => {
            if (composerSelectedFiles.length === 0) {
                return;
            }

            const fileInput = composerPhotoInput();
            if (fileInput) {
                fileInput.value = '';
            }
        });

        document.querySelectorAll('[data-teacher-composer-open]').forEach((button) => {
            button.addEventListener('click', () => {
                if (composerShortcuts) composerShortcuts.classList.add('hidden');
                if (composerForm) composerForm.classList.remove('hidden');

                if (button.dataset.teacherComposerOpen === 'photo') {
                    composerPhotoInput()?.click();
                    return;
                }

                composerEditable()?.focus();
            });
        });

        document.querySelectorAll('[data-teacher-composer-close]').forEach((button) => {
            button.addEventListener('click', () => {
                if (composerForm) composerForm.classList.add('hidden');
                if (composerShortcuts) composerShortcuts.classList.remove('hidden');
                resetComposerFiles();
            });
        });

        document.querySelectorAll('[data-teacher-dialog-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = document.querySelector(`[data-teacher-dialog="${button.dataset.teacherDialogOpen}"]`);
                if (dialog) dialog.hidden = false;
            });
        });

        document.querySelectorAll('[data-teacher-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = button.closest('[data-teacher-dialog]');
                if (dialog) dialog.hidden = true;
            });
        });
    </script>
@endpush

