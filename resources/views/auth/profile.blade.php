@extends('layouts.app')

@section('title', 'Profil')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Profil')

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-page-heading">
            <h1 class="transaction-page-heading__title">Profil</h1>
        </div>

        <section class="dme-section-card">
            <form method="POST" action="{{ route('profile.update') }}" class="dme-form-stack">
                @csrf
                @method('PUT')

                <div class="dme-field">
                    <label for="name" class="dme-field__label">Nama akun</label>
                    <input
                        class="dme-field__control"
                        id="name"
                        name="name"
                        type="text"
                        value="{{ old('name', $user?->name) }}"
                        required
                    >
                </div>

                <div class="dme-action-row">
                    <button type="submit" class="dme-button">Simpan profil</button>
                </div>
            </form>
        </section>
    </div>
@endsection
