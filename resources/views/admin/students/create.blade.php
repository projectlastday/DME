@extends('layouts.app')

@section('title', 'Tambah Murid')
@section('eyebrow', '')
@section('page_title', 'Tambah Murid')
@section('page_description', 'Buat akun murid dan tetapkan kredensial awalnya.')
@section('page_actions')
    <a href="{{ route('admin.students.index') }}" class="dme-button--ghost">Kembali ke murid</a>
@endsection

@section('content')
    <div class="dme-page-stack">
        <section class="dme-section-stack">
            <x-admin.page-header heading="Tambah Murid" description="Buat akun murid baru dengan kata sandi awal." />

            <x-admin.user-form
                :action="route('admin.students.store')"
                method="POST"
                submit-label="Tambah murid"
                role-label="Murid"
                :show-password-fields="true"
            />
        </section>
    </div>
@endsection
