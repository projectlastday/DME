@extends('layouts.app')

@section('title', 'Ubah Murid')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Ubah Murid')

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-page-header">
            <h1 class="transaction-page-heading__title">Ubah Murid</h1>
            <a href="{{ route('admin.students.show', $student) }}" class="dme-button--ghost">Kembali ke murid</a>
        </div>

        <section class="dme-section-stack">
            <x-admin.user-form
                :action="route('admin.students.update', $student)"
                method="PUT"
                submit-label="Simpan murid"
                role-label="Murid"
                :user="$student"
            />
        </section>

        <x-admin.password-reset-form
            :action="route('admin.students.reset-password', $student)"
            heading="Atur Ulang Kata Sandi Murid"
            submit-label="Atur ulang kata sandi"
        />
    </div>
@endsection
