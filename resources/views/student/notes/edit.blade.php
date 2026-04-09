@extends('layouts.app')

@section('title', 'Ubah Catatan')
@section('hide_shell_header', 'true')
@section('surfaceless_content', 'true')

@section('content')
    <div class="animate-fade-in pb-20">
        <div class="sticky top-0 z-20 -mx-4 mb-6 flex items-center gap-3 border-b border-slate-200/50 bg-transparent px-4 pb-4 pt-4 backdrop-blur-xl sm:-mx-6 sm:px-6">
            <a
                href="{{ route('student.notes.index', ['tab' => 'mine']) }}"
                class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 shadow-sm transition-all hover:border-amber-300 hover:text-amber-600"
                aria-label="Kembali"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="flex-1 overflow-hidden">
                <h1 class="truncate font-heading text-xl font-bold text-slate-900 sm:text-2xl">Ubah catatan</h1>
            </div>
        </div>

        <div class="mb-10">
            <x-student.note-form
                :action="route('student.notes.update', data_get($note, 'id'))"
                method="PUT"
                :note="$note"
                submit-label="Simpan perubahan"
            />
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        const composerForm = document.querySelector('[data-student-note-form]');
        const composerEditable = () => composerForm?.querySelector('[data-re-editable]');
        const composerPhotoInput = () => composerForm?.querySelector('#student-note-images');
        const composerCount = () => composerForm?.querySelector('[data-student-note-count]');
        const composerPreviewList = () => composerForm?.querySelector('[data-student-image-preview-list]');
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
            } catch (error) {}
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
                    <div class="relative h-24 w-24 shrink-0 overflow-hidden rounded-[1.5rem] border border-slate-200 bg-slate-100 shadow-sm">
                        <img src="${previewUrl}" alt="${file.name}" class="absolute inset-0 h-full w-full object-cover">
                        <button
                            type="button"
                            class="absolute right-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-slate-700 shadow-sm transition-colors hover:bg-white"
                            data-student-image-remove="${index}"
                            aria-label="Hapus gambar"
                        >
                            <svg class="h-3.5 w-3.5 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;
            }).join('');
        };

        const editable = composerEditable();
        if (editable) {
            editable.addEventListener('input', syncComposerCount);
            syncComposerCount();
        }

        composerPhotoInput()?.addEventListener('change', (event) => {
            const files = Array.from(event.currentTarget.files ?? []);
            if (files.length === 0) return;
            composerSelectedFiles = [...composerSelectedFiles, ...files];
            syncComposerFiles();
            renderComposerPreviews();
        });

        composerPreviewList()?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-student-image-remove]');
            if (!button) return;
            const index = Number(button.dataset.studentImageRemove);
            composerSelectedFiles.splice(index, 1);
            syncComposerFiles();
            renderComposerPreviews();
        });
    </script>
@endpush

