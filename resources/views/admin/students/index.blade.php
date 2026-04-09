@extends('layouts.app')

@section('hide_shell_header', 'true')
@section('eyebrow', '')

@section('content')
    <div class="dme-page-stack">
        <div class="admin-user-toolbar">
            <form method="GET" action="{{ route('admin.students.index') }}" class="admin-user-toolbar__search">
                <label for="admin-student-search" class="sr-only">Cari murid</label>
                <span class="admin-user-toolbar__search-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="M20 20l-3.5-3.5"></path>
                    </svg>
                </span>
                <input
                    id="admin-student-search"
                    class="admin-user-toolbar__search-input"
                    name="search"
                    type="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari murid..."
                >
                <button type="submit" class="admin-user-toolbar__search-button">Cari</button>
            </form>

            <a href="{{ route('admin.students.create') }}" class="dme-button">Tambah murid</a>
        </div>

        <section class="dme-section-card dme-section-stack">
            <x-admin.users-table
                :users="$students"
                show-route="admin.students.show"
                edit-route="admin.students.edit"
                action-mode="info"
                empty-title="Murid tidak ketemu"
            />
        </section>
    </div>
@endsection
