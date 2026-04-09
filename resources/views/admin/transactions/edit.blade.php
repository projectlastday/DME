@extends('layouts.app')

@section('title', 'Ubah Transaksi')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Ubah Transaksi')

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-page-header">
            <h1 class="transaction-page-heading__title">Ubah Transaksi</h1>
            <a href="{{ route('admin.transactions.show', $transaction) }}" class="dme-button--ghost">Kembali ke detail</a>
        </div>

        <x-admin.transaction-form
            :action="route('admin.transactions.update', $transaction)"
            method="PUT"
            :students="collect()"
            :selected-student-id="old('id_murid', $transaction->id_murid)"
            :tanggal="old('tanggal', optional($transaction->tanggal)->toDateString())"
            :jumlah="old('jumlah', $transaction->jumlah)"
            :period-options="$periodOptions"
            :selected-periods="$selectedPeriods"
            submit-label="Perbarui transaksi"
            :student-locked="true"
            :student-label="$transaction->student?->name"
        />
    </div>
@endsection
