@extends('layouts.app')

@php
    use Illuminate\Support\Carbon;
@endphp

@section('title', 'Catatan Guru')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Catatan Guru')

@section('content')
    <div class="animate-fade-in pb-20">
        <div class="mb-8">
            <h1 class="font-heading text-3xl font-bold text-slate-900">Catatan</h1>
            <p class="mt-2 text-slate-500 text-sm">Lihat catatan guru atau buat catatan pribadi Anda.</p>
        </div>

        <div class="mb-8">
            <div class="flex border-b border-slate-200">
                <a
                    href="{{ route('student.notes.index', ['tab' => 'teacher']) }}"
                    @class([
                        'px-6 py-3 text-sm font-bold transition-all border-b-2',
                        'border-amber-500 text-amber-600' => $activeTab === 'teacher',
                        'border-transparent text-slate-400 hover:text-slate-600' => $activeTab !== 'teacher',
                    ])
                >
                    Catatan Guru
                </a>
                <a
                    href="{{ route('student.notes.index', ['tab' => 'mine']) }}"
                    @class([
                        'px-6 py-3 text-sm font-bold transition-all border-b-2',
                        'border-amber-500 text-amber-600' => $activeTab === 'mine',
                        'border-transparent text-slate-400 hover:text-slate-600' => $activeTab !== 'mine',
                    ])
                >
                    Catatan Saya
                </a>
            </div>
        </div>

        @if ($activeTab === 'mine')
            <div class="mb-10">
                @php($showComposer = $errors->any() || filled(old('body')) || filled(old('note_date')))
                <div @class(['animate-fade-in', 'hidden' => $showComposer]) data-student-composer-shortcuts>
                    <button
                        class="group flex w-full items-center gap-3 rounded-2xl border border-slate-100 bg-white px-4 py-3 shadow-sm transition-all hover:border-amber-200 hover:shadow-md active:scale-[0.98]"
                        type="button"
                        data-student-composer-open
                    >
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-500 transition-transform group-hover:scale-110">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <span class="text-sm font-bold text-slate-800">Tulis catatan Anda di sini...</span>
                    </button>
                </div>

                <div @class(['animate-fade-in', 'hidden' => ! $showComposer]) data-student-composer-form>
                    <x-student.note-form
                        :action="route('student.notes.store')"
                        method="POST"
                        submit-label="Simpan catatan"
                    />
                </div>
            </div>
        @endif

        @forelse ($noteGroups as $group)
            <div class="mb-10 last:mb-0">
                <div class="mb-6">
                    <h2 class="font-heading text-lg font-bold text-slate-900 leading-none">
                        {{ Carbon::parse($group['note_date'])->locale('id')->translatedFormat('d F Y') }}
                    </h2>
                    <div class="mt-2 h-1 w-8 rounded-full bg-amber-200"></div>
                </div>

                <div class="space-y-6">
                    @foreach ($group['notes'] as $note)
                        <x-student.note-card :note="$note" />
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-200 bg-white/50 px-4 py-12 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-50 text-slate-400">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h3 class="mb-1 font-semibold text-slate-900">Belum ada catatan</h3>
                <p class="text-sm text-slate-500">
                    {{ $activeTab === 'teacher' ? 'Belum ada catatan guru untuk Anda.' : 'Anda belum menulis catatan pribadi.' }}
                </p>
            </div>
        @endforelse
    </div>
@endsection

@push('scripts')
    <script>
        const composerShortcuts = document.querySelector('[data-student-composer-shortcuts]');
        const composerForm = document.querySelector('[data-student-composer-form]');
        const composerEditor = () => composerForm?.querySelector('[data-rich-editor]');
        const composerEditable = () => composerForm?.querySelector('[data-re-editable]');
        const composerPhotoInput = () => composerForm?.querySelector('#student-note-images');
        const composerCount = () => composerForm?.querySelector('[data-student-note-count]');
        const composerPreviewList = () => composerForm?.querySelector('[data-student-image-preview-list]');
        const composerFormElement = () => composerForm?.querySelector('[data-student-note-form]');
        const composerPayloadInputs = () => composerForm?.querySelector('[data-student-image-payload-inputs]');
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

        const resetComposerFiles = () => {
            composerSelectedFiles = [];
            syncComposerFiles();
            renderComposerPreviews();
        };

        // Rich editor auto-inits on DOMContentLoaded via imported module.
        // Hook into its input event for character count.
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

        document.querySelector('[data-student-composer-open]')?.addEventListener('click', () => {
            composerShortcuts?.classList.add('hidden');
            composerForm?.classList.remove('hidden');
            composerEditable()?.focus();
        });

        document.querySelectorAll('[data-student-composer-close]').forEach((button) => {
            button.addEventListener('click', () => {
                composerForm?.classList.add('hidden');
                composerShortcuts?.classList.remove('hidden');
                resetComposerFiles();
            });
        });
    </script>
@endpush

