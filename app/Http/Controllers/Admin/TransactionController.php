<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertTransactionRequest;
use App\Models\DetailTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Transactions\TransactionPeriodOptions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $studentFilter = request()->integer('id_murid');
        $periodFilter = trim((string) request()->string('periode'));

        $query = Transaction::query()
            ->with(['student', 'details'])
            ->when($studentFilter > 0, fn ($builder) => $builder->where('id_murid', $studentFilter))
            ->when($periodFilter !== '', function ($builder) use ($periodFilter): void {
                $period = TransactionPeriodOptions::parse($periodFilter);

                if ($period === null) {
                    return;
                }

                $builder->whereHas('details', function ($detailQuery) use ($period): void {
                    $detailQuery
                        ->where('bulan', (int) $period->month)
                        ->where('tahun', (int) $period->year);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id_transaksi');

        return view('admin.transactions.index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'students' => $this->studentOptions(),
            'studentFilter' => $studentFilter > 0 ? $studentFilter : null,
            'periodFilter' => $periodFilter,
            'periodOptions' => TransactionPeriodOptions::all($periodFilter !== '' ? [$periodFilter] : []),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.transactions.create', [
            'students' => $this->studentOptions(),
            'periodOptions' => TransactionPeriodOptions::all(old('periods', [])),
            'selectedPeriods' => old('periods', []),
            'today' => now()->toDateString(),
        ]);
    }

    public function store(UpsertTransactionRequest $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $transaction = DB::transaction(function () use ($request): Transaction {
            $payload = $request->validatedPayload();

            $transaction = Transaction::query()->create([
                'id_murid' => $payload['id_murid'],
                'tanggal' => $payload['tanggal'],
                'jumlah' => $payload['jumlah'],
            ]);

            $transaction->details()->createMany(
                collect($payload['periods'])
                    ->map(fn (array $period): array => [
                        'bulan' => $period['bulan'],
                        'tahun' => $period['tahun'],
                    ])
                    ->all()
            );

            return $transaction;
        });

        return redirect()
            ->route('admin.transactions.show', $transaction)
            ->with('status', 'Transaksi berhasil ditambahkan.');
    }

    public function show(Transaction $transaction): View
    {
        $this->authorizeAdmin();

        return view('admin.transactions.show', [
            'transaction' => $this->hydrateTransaction($transaction),
        ]);
    }

    public function edit(Transaction $transaction): View
    {
        $this->authorizeAdmin();

        $transaction = $this->hydrateTransaction($transaction);
        $selectedPeriods = $transaction->details
            ->map(fn (DetailTransaction $detail): string => sprintf('%04d-%02d', $detail->tahun, $detail->bulan))
            ->all();

        return view('admin.transactions.edit', [
            'transaction' => $transaction,
            'periodOptions' => TransactionPeriodOptions::all(old('periods', $selectedPeriods)),
            'selectedPeriods' => old('periods', $selectedPeriods),
        ]);
    }

    public function update(UpsertTransactionRequest $request, Transaction $transaction): RedirectResponse
    {
        $this->authorizeAdmin();

        DB::transaction(function () use ($request, $transaction): void {
            $payload = $request->validatedPayload();

            $transaction->update([
                'id_murid' => $transaction->id_murid,
                'tanggal' => $payload['tanggal'],
                'jumlah' => $payload['jumlah'],
            ]);

            $transaction->details()->delete();

            $transaction->details()->createMany(
                collect($payload['periods'])
                    ->map(fn (array $period): array => [
                        'bulan' => $period['bulan'],
                        'tahun' => $period['tahun'],
                    ])
                    ->all()
            );
        });

        return redirect()
            ->route('admin.transactions.show', $transaction)
            ->with('status', 'Transaksi berhasil diperbarui.');
    }

    public function destroy(Transaction $transaction): RedirectResponse
    {
        $this->authorizeAdmin();

        $transaction->delete();

        return redirect()
            ->route('admin.transactions.index')
            ->with('status', 'Transaksi berhasil dihapus.');
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_SUPER_ADMIN, 403);
    }

    private function studentOptions()
    {
        return User::query()
            ->roleNamed(User::ROLE_STUDENT)
            ->orderBy('nama')
            ->get();
    }

    private function hydrateTransaction(Transaction $transaction): Transaction
    {
        return $transaction->load(['student', 'details']);
    }
}
