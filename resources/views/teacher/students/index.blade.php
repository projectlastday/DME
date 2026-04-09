@extends('layouts.app')

@section('title', 'Daftar Murid')
@section('hide_shell_header', 'true')
@section('surfaceless_content', 'true')

@section('content')
    <div class="teacher-roster-exact animate-fade-in">
        <header class="teacher-roster-exact__header">
            <h1 class="teacher-roster-exact__title">Daftar Murid</h1>
            <button
                class="teacher-roster-exact__add-button"
                type="button"
                aria-label="Tambah murid"
                data-teacher-dialog-open="add-student"
            >
                <svg class="teacher-roster-exact__add-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="teacher-roster-exact__add-label">Tambah</span>
            </button>
        </header>

        <form method="GET" action="{{ route('teacher.students.index') }}" class="teacher-roster-exact__search-wrap teacher-roster-exact__search-form">
            <svg class="teacher-roster-exact__search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            <input
                class="teacher-roster-exact__search-input"
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Cari murid..."
                autocomplete="off"
            >
            <button type="submit" class="admin-user-toolbar__search-button">Cari</button>
        </form>

        @if (count($students) === 0)
            @if ($search !== '')
                <section class="px-4 py-16 text-center" role="status" aria-live="polite">
                    <h3 class="mb-1 text-lg font-bold text-slate-900">Tidak ditemukan</h3>
                    <p class="text-slate-500">Tidak ada yang cocok dengan "{{ $search }}".</p>
                </section>
            @else
                <section class="px-4 py-16 text-center" role="status" aria-live="polite">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100">
                        <svg class="h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <h3 class="mb-1 text-lg font-bold text-slate-900">Belum ada murid</h3>
                    <p class="text-slate-500">Tambahkan murid pertama Anda.</p>
                </section>
            @endif
        @else
            <section class="grid grid-cols-3 gap-4" aria-label="Daftar murid">
                @foreach ($students as $student)
                    <a
                        href="{{ route('teacher.students.show', data_get($student, 'id')) }}"
                        class="group relative flex h-20 flex-col items-center justify-center rounded-2xl border border-slate-100 bg-white p-4 shadow-sm transition-all hover:border-amber-200 hover:shadow-md active:scale-95 sm:h-24"
                        aria-label="Buka murid {{ data_get($student, 'name') }}"
                    >
                        <span class="pointer-events-none relative z-10 w-full break-words px-2 text-center text-base font-bold text-slate-900 select-none sm:text-lg">
                            {{ data_get($student, 'name') }}
                        </span>
                    </a>
                @endforeach
            </section>
        @endif

        <div class="teacher-dialog" data-teacher-dialog="add-student" hidden>
            <div class="teacher-dialog__backdrop" data-teacher-dialog-close></div>
            <div class="teacher-dialog__panel">
                <h3 class="mb-1 text-center font-heading text-2xl font-bold text-slate-900">Murid Baru</h3>
                <p class="mb-6 text-center text-sm text-slate-500">Tambahkan nama murid untuk memulai catatan.</p>
                <form method="POST" action="{{ route('teacher.students.store') }}" class="teacher-dialog__form">
                    @csrf
                    <input
                        class="mb-2 h-14 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-center text-slate-900 outline-none transition-all focus:bg-white focus:ring-2 focus:ring-amber-400"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Masukkan nama murid"
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
    </div>
@endsection

@push('scripts')
    <script>
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
