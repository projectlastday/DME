<?php

namespace App\Support\Transactions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TransactionPeriodOptions
{
    public static function all(array $selected = []): Collection
    {
        $currentYear = now()->year;
        $start = Carbon::create($currentYear - 1, 1, 1)->startOfMonth();
        $end = Carbon::create($currentYear + 1, 12, 1)->startOfMonth();

        $periods = collect();
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $periods->push(self::formatPeriod($cursor));
            $cursor->addMonth();
        }

        $selectedPeriods = collect($selected)
            ->map(fn (string $value): ?Carbon => self::parse($value))
            ->filter()
            ->map(fn (Carbon $date): array => self::formatPeriod($date));

        return $periods
            ->merge($selectedPeriods)
            ->unique('value')
            ->sortBy('value')
            ->values();
    }

    public static function parse(string $value): ?Carbon
    {
        if (! preg_match('/^(?<year>\d{4})-(?<month>\d{2})$/', $value, $matches)) {
            return null;
        }

        $month = (int) $matches['month'];
        $year = (int) $matches['year'];

        if ($month < 1 || $month > 12) {
            return null;
        }

        return Carbon::create($year, $month, 1)->startOfMonth();
    }

    public static function label(int $month, int $year): string
    {
        return Carbon::create($year, $month, 1)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    /**
     * @return array{value: string, label: string, bulan: int, tahun: int}
     */
    private static function formatPeriod(Carbon $date): array
    {
        return [
            'value' => $date->format('Y-m'),
            'label' => $date->locale('id')->translatedFormat('F Y'),
            'bulan' => (int) $date->month,
            'tahun' => (int) $date->year,
        ];
    }
}
