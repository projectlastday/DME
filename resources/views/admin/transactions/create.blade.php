@extends('layouts.app')

@section('title', 'Tambah Transaksi')
@section('eyebrow', '')
@section('page_title', 'Tambah Transaksi')
@section('page_actions')
    <a href="{{ route('admin.transactions.index') }}" class="dme-button--ghost">Kembali ke transaksi</a>
@endsection

@section('content')
    <div class="dme-page-stack">
        <x-admin.transaction-form
            :action="route('admin.transactions.store')"
            method="POST"
            :students="$students"
            :selected-student-id="old('id_murid')"
            :tanggal="old('tanggal', $today)"
            :jumlah="old('jumlah')"
            :period-options="$periodOptions"
            :selected-periods="$selectedPeriods"
            submit-label="Simpan transaksi"
        />
    </div>
@endsection
