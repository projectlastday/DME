@extends('layouts.app')

@php
    use App\Support\Transactions\TransactionPeriodOptions;

    $detailLabels = $transaction->details
        ->map(fn ($detail) => TransactionPeriodOptions::label($detail->bulan, $detail->tahun))
        ->all();
@endphp

@section('title', 'Detail Transaksi')
@section('hide_shell_header', 'true')
@section('eyebrow', '')
@section('page_title', 'Detail Transaksi')

@section('content')
    <div class="dme-page-stack">
        <div class="transaction-page-heading">
            <h1 class="transaction-page-heading__title">Detail Transaksi</h1>
        </div>

        <section class="dme-section-card dme-section-stack">
            <div class="transaction-detail-grid">
                <div>
                    <p class="transaction-row-card__label">Murid</p>
                    <p class="transaction-row-card__value">{{ $transaction->student?->name }}</p>
                </div>
                <div>
                    <p class="transaction-row-card__label">Tanggal transaksi</p>
                    <p class="transaction-row-card__value">{{ $transaction->tanggal?->locale('id')->translatedFormat('d F Y') }}</p>
                </div>
                <div>
                    <p class="transaction-row-card__label">Jumlah</p>
                    <p class="transaction-row-card__value">Rp{{ number_format($transaction->jumlah, 0, ',', '.') }}</p>
                </div>
                <div>
                    <p class="transaction-row-card__label">ID transaksi</p>
                    <p class="transaction-row-card__value">#{{ $transaction->getKey() }}</p>
                </div>
                <div>
                    <p class="transaction-row-card__label">Dibuat</p>
                    <p class="transaction-row-card__value">{{ $transaction->created_at?->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                </div>
                <div>
                    <p class="transaction-row-card__label">Diperbarui</p>
                    <p class="transaction-row-card__value">{{ $transaction->updated_at?->locale('id')->translatedFormat('d F Y, H:i') }}</p>
                </div>
            </div>

            <div>
                <p class="transaction-row-card__label">Bulan dibayar</p>
                <div class="transaction-inline-pills transaction-inline-pills--detail">
                    @foreach ($detailLabels as $label)
                        <span class="transaction-inline-pill">{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            <div class="transaction-detail-actions">
                <div class="transaction-detail-actions__inner">
                    <a href="{{ route('admin.transactions.edit', $transaction) }}" class="dme-button--secondary">Ubah</a>
                    <button type="button" class="dme-button--danger" data-teacher-dialog-open="delete-transaction-detail">Hapus</button>
                </div>
            </div>
        </section>
    </div>

    <div class="teacher-dialog" data-teacher-dialog="delete-transaction-detail" hidden>
        <div class="teacher-dialog__backdrop" data-teacher-dialog-close></div>
        <div class="teacher-dialog__panel">
            <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 text-red-600">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 1 1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="mb-2 text-center font-heading text-2xl font-bold text-slate-900">Hapus transaksi ini?</h3>
            <p class="mb-6 text-center leading-relaxed text-slate-600">Transaksi dan semua detail bulan pembayaran akan dihapus secara permanen.</p>
            <form method="POST" action="{{ route('admin.transactions.destroy', $transaction) }}" class="teacher-dialog__form">
                @csrf
                @method('DELETE')
                <div class="mt-4 flex gap-3">
                    <button class="h-12 flex-1 rounded-full bg-slate-100 px-4 font-semibold text-slate-700 transition-colors hover:bg-slate-200" type="button" data-teacher-dialog-close>Batal</button>
                    <button class="h-12 flex-1 rounded-full bg-red-600 px-4 font-semibold text-white transition-colors hover:bg-red-700" type="submit">Hapus</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-teacher-dialog-open]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = document.querySelector(`[data-teacher-dialog="${button.dataset.teacherDialogOpen}"]`);
                if (dialog) dialog.hidden = false;
            });
        });

        document.querySelectorAll('[data-teacher-dialog-close]').forEach((button) => {
            button.addEventListener('click', () => {
                const dialog = button.closest('[data-teacher-dialog]');
                if (dialog) dialog.hidden = true;
            });
        });
    </script>
@endpush
