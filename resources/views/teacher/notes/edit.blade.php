@extends('layouts.app')

@section('title', 'Ubah Catatan')
@section('hide_shell_header', 'true')
@section('surfaceless_content', 'true')

@section('content')
    <div class="animate-fade-in pb-20">
        <div class="sticky top-0 z-20 -mx-4 mb-6 flex items-center gap-3 border-b border-slate-200/50 bg-transparent px-4 pb-4 pt-4 backdrop-blur-xl sm:-mx-6 sm:px-6">
            <a
                href="{{ route('teacher.students.show', data_get($note, 'student_id', data_get($student, 'id'))) }}"
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
            <x-teacher.note-form
                :action="route('teacher.notes.update', data_get($note, 'id'))"
                method="PUT"
                :note="$note"
                :student-id="data_get($note, 'student_id', data_get($student, 'id'))"
                submit-label="Simpan perubahan"
            />
        </div>

    </div>
@endsection
