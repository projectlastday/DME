<?php

namespace App\Http\Requests\Admin;

use App\Models\Transaction;
use App\Models\User;
use App\Support\Transactions\TransactionPeriodOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpsertTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_SUPER_ADMIN;
    }

    public function rules(): array
    {
        return [
            'id_murid' => ['required', 'integer', 'exists:user,id_user'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'gt:0', 'max:2147483647'],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['required', 'string', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'jumlah.max' => 'Jumlah tidak boleh lebih dari 2.147.483.647.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'periods' => array_values(array_filter((array) $this->input('periods', []))),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $studentId = (int) $this->input('id_murid');
            $periods = collect((array) $this->input('periods', []));

            if ($periods->isEmpty()) {
                return;
            }

            $student = User::query()->find($studentId);

            if (! $student || $student->role !== User::ROLE_STUDENT) {
                $validator->errors()->add('id_murid', 'Murid tidak valid.');

                return;
            }

            $parsed = $periods->map(function (string $value, int $index) use ($validator): ?array {
                $date = TransactionPeriodOptions::parse($value);

                if ($date === null) {
                    $validator->errors()->add("periods.{$index}", 'Periode pembayaran tidak valid.');

                    return null;
                }

                return [
                    'value' => $value,
                    'bulan' => (int) $date->month,
                    'tahun' => (int) $date->year,
                    'label' => TransactionPeriodOptions::label((int) $date->month, (int) $date->year),
                ];
            })->filter();

            if ($parsed->count() !== $periods->count()) {
                return;
            }

            if ($parsed->pluck('value')->unique()->count() !== $parsed->count()) {
                $validator->errors()->add('periods', 'Periode pembayaran tidak boleh duplikat dalam satu transaksi.');
            }

            $transactionId = $this->route('transaction')?->getKey();

            $duplicates = DB::table('detail_transaction')
                ->join('transaction', 'transaction.id_transaksi', '=', 'detail_transaction.id_transaksi')
                ->where('transaction.id_murid', $studentId)
                ->when($transactionId, fn ($query) => $query->where('transaction.id_transaksi', '!=', $transactionId))
                ->where(function ($query) use ($parsed): void {
                    foreach ($parsed as $period) {
                        $query->orWhere(function ($periodQuery) use ($period): void {
                            $periodQuery
                                ->where('detail_transaction.bulan', $period['bulan'])
                                ->where('detail_transaction.tahun', $period['tahun']);
                        });
                    }
                })
                ->select(['detail_transaction.bulan', 'detail_transaction.tahun'])
                ->distinct()
                ->get()
                ->map(fn (object $row): string => TransactionPeriodOptions::label((int) $row->bulan, (int) $row->tahun))
                ->values();

            foreach ($duplicates as $label) {
                $validator->errors()->add('periods', "Bulan {$label} sudah dibayar.");
            }
        });
    }

    public function validatedPayload(): array
    {
        return [
            'id_murid' => (int) $this->validated('id_murid'),
            'tanggal' => (string) $this->validated('tanggal'),
            'jumlah' => (int) $this->validated('jumlah'),
            'periods' => collect((array) $this->validated('periods'))
                ->map(function (string $value): array {
                    $date = TransactionPeriodOptions::parse($value);

                    return [
                        'bulan' => (int) $date->month,
                        'tahun' => (int) $date->year,
                        'value' => $value,
                        'label' => TransactionPeriodOptions::label((int) $date->month, (int) $date->year),
                    ];
                })
                ->values()
                ->all(),
        ];
    }
}
