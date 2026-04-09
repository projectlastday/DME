@extends('layouts.app')

@section('title', 'Login')
@section('eyebrow', '')
@section('page_title', "Diana's Mandarin Education")
@section('page_layout', 'centered')
@section('hide_navigation', 'true')

@section('content')
    <form class="auth-form" method="POST" action="{{ route('login.attempt') }}">
        @csrf

        <label class="field">
            <span>Nama</span>
            <input
                autocomplete="username"
                class="input"
                name="login"
                required
                type="text"
                value="{{ old('login') }}"
            >
        </label>

        <label class="field">
            <span>Password</span>
            <input
                autocomplete="current-password"
                class="input"
                name="password"
                required
                type="password"
            >
        </label>

        <button class="button" type="submit">Login</button>
    </form>
@endsection
