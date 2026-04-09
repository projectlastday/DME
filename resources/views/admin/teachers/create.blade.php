@extends('layouts.app')

@section('title', 'Tambah Guru')
@section('eyebrow', '')
@section('page_title', 'Tambah Guru')
@section('page_description', '')
@section('page_actions')
    <a href="{{ route('admin.teachers.index') }}" class="dme-button--ghost">Kembali ke guru</a>
@endsection

@section('content')
    <div class="dme-page-stack">
        <section class="dme-section-stack">
            <x-admin.page-header heading="Tambah Guru" />

            <x-admin.user-form
                :action="route('admin.teachers.store')"
                method="POST"
                submit-label="Tambah guru"
                role-label="Guru"
                :show-password-fields="true"
            />
        </section>
    </div>
@endsection
