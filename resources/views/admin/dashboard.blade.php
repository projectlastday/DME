@extends('layouts.app')

@section('title', 'Dashboard Admin')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', '')

@section('content')
    <div class="dme-page-stack">
        <section class="dme-stat-grid" aria-label="Statistik platform">
            @if (\Illuminate\Support\Facades\Route::has('admin.teachers.index'))
                <a href="{{ route('admin.teachers.index') }}" class="dme-stat-card">
                    <p class="dme-stat-card__label">Guru</p>
                    <p class="dme-stat-card__value">{{ $teacherCount }}</p>
                </a>
            @else
                <article class="dme-stat-card">
                    <p class="dme-stat-card__label">Guru</p>
                    <p class="dme-stat-card__value">{{ $teacherCount }}</p>
                </article>
            @endif

            @if (\Illuminate\Support\Facades\Route::has('admin.students.index'))
                <a href="{{ route('admin.students.index') }}" class="dme-stat-card">
                    <p class="dme-stat-card__label">Murid</p>
                    <p class="dme-stat-card__value">{{ $studentCount }}</p>
                </a>
            @else
                <article class="dme-stat-card">
                    <p class="dme-stat-card__label">Murid</p>
                    <p class="dme-stat-card__value">{{ $studentCount }}</p>
                </article>
            @endif
        </section>

        <section class="dme-section-card dme-section-stack transaction-dashboard-card">
            <div class="transaction-dashboard-card__header">
                <div>
                    <h2 class="dme-section-title">Grafik transaksi</h2>
                </div>

                <form method="GET" action="{{ route('admin.dashboard') }}" class="transaction-dashboard-card__filter">
                    <label for="dashboard-transaction-year" class="sr-only">Pilih tahun transaksi</label>
                    <select
                        id="dashboard-transaction-year"
                        name="tahun"
                        class="dme-field__control"
                        onchange="this.form.submit()"
                    >
                        @foreach ($transactionYearOptions as $option)
                            <option value="{{ $option['value'] }}" @selected($selectedTransactionYear === $option['value'])>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="transaction-dashboard-card__summary">
                <article class="transaction-dashboard-metric">
                    <p class="transaction-dashboard-metric__label">Total transaksi</p>
                    <p class="transaction-dashboard-metric__value">{{ $transactionChart['totalTransactions'] }}</p>
                </article>
                <article class="transaction-dashboard-metric">
                    <p class="transaction-dashboard-metric__label">Total pemasukan</p>
                    <p class="transaction-dashboard-metric__value">Rp{{ number_format($transactionChart['totalAmount'], 0, ',', '.') }}</p>
                </article>
            </div>

            <div class="transaction-chart" aria-label="Grafik transaksi per bulan">
                <div class="transaction-chart__bars">
                    @foreach ($transactionChart['points'] as $point)
                        @php
                            $height = $point['amount'] > 0
                                ? max(8, (int) round(($point['amount'] / $transactionChart['maxAmount']) * 220))
                                : 0;
                        @endphp
                        <div class="transaction-chart__item">
                            @if ($point['amount'] > 0)
                                <div class="transaction-chart__tooltip">
                                    <strong>{{ $point['label'] }} {{ $selectedTransactionYear }}</strong>
                                    <span>Rp{{ number_format($point['amount'], 0, ',', '.') }}</span>
                                </div>
                                <div
                                    class="transaction-chart__bar"
                                    style="height: {{ $height }}px"
                                ></div>
                            @else
                                <div class="transaction-chart__bar transaction-chart__bar--empty" aria-hidden="true"></div>
                            @endif
                            <span class="transaction-chart__label">{{ $point['month'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
@endsection
