@extends('layouts.app')

@section('hide_shell_header', 'true')
@section('eyebrow', '')

@php
    use App\Support\Transactions\TransactionPeriodOptions;

    $groupedPeriodOptions = collect($periodOptions)->groupBy('tahun')->sortKeys();
@endphp

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-toolbar">
            <form method="GET" action="{{ route('admin.transactions.index') }}" class="transaction-toolbar__filters">
                <div class="transaction-toolbar__row">
                    <div class="dme-field transaction-toolbar__field">
                        <label for="transaction-student-filter" class="dme-field__label">Murid</label>
                        <select class="dme-field__control" id="transaction-student-filter" name="id_murid">
                            <option value="">Semua murid</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->getKey() }}" @selected((string) $studentFilter === (string) $student->getKey())>
                                    {{ $student->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dme-field transaction-toolbar__field">
                        <label for="transaction-period-filter" class="dme-field__label">Periode</label>
                        <select class="dme-field__control" id="transaction-period-filter" name="periode">
                            <option value="">Semua bulan</option>
                            @foreach ($groupedPeriodOptions as $year => $periods)
                                <optgroup label="{{ $year }}">
                                    @foreach ($periods as $period)
                                        <option value="{{ $period['value'] }}" @selected($periodFilter === $period['value'])>
                                            {{ $period['label'] }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="transaction-toolbar__buttons">
                        <button type="submit" class="admin-user-toolbar__search-button">Filter</button>
                        <a href="{{ route('admin.transactions.create') }}" class="dme-button">Tambah transaksi</a>
                    </div>
                </div>
            </form>
        </div>

        <section class="dme-section-card dme-section-stack">
            <div class="dme-table-wrap">
                <table class="dme-table">
                    <thead>
                        <tr>
                            <th scope="col" class="dme-table__center-column">Murid</th>
                            <th scope="col" class="dme-table__center-column">Tanggal</th>
                            <th scope="col" class="dme-table__center-column">Jumlah</th>
                            <th scope="col" class="dme-table__actions-column">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="dme-table__center-cell">
                                    <strong>{{ $transaction->student?->name }}</strong>
                                </td>
                                <td class="dme-table__center-cell">
                                    <strong>{{ $transaction->tanggal?->locale('id')->translatedFormat('d F Y') }}</strong>
                                </td>
                                <td class="dme-table__center-cell">
                                    <strong>Rp{{ number_format($transaction->jumlah, 0, ',', '.') }}</strong>
                                </td>
                                <td class="dme-table__actions-cell">
                                    <div class="dme-action-row">
                                        <a href="{{ route('admin.transactions.show', $transaction) }}" class="dme-button--secondary">Info</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="dme-empty-state">
                                        <h3 class="dme-empty-state__title">Transaksi tidak ketemu</h3>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($transactions, 'links'))
                <div>
                    {{ $transactions->links() }}
                </div>
            @endif
        </section>
    </div>
@endsection
