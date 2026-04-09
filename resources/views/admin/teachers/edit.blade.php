@extends('layouts.app')

@section('title', 'Ubah Guru')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Ubah Guru')

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-page-header">
            <h1 class="transaction-page-heading__title">Ubah Guru</h1>
            <a href="{{ route('admin.teachers.show', $teacher) }}" class="dme-button--ghost">Kembali ke guru</a>
        </div>

        <section class="dme-section-stack">
            <x-admin.user-form
                :action="route('admin.teachers.update', $teacher)"
                method="PUT"
                submit-label="Simpan guru"
                role-label="Guru"
                :user="$teacher"
            />
        </section>

        <x-admin.password-reset-form
            :action="route('admin.teachers.reset-password', $teacher)"
            heading="Atur Ulang Kata Sandi Guru"
            submit-label="Atur ulang kata sandi"
        />
    </div>
@endsection
