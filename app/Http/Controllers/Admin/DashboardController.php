<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\NoteImage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $this->authorizeAdmin();

        $selectedYear = $this->resolveSelectedYear();
        $chart = $this->transactionChart($selectedYear);

        return view('admin.dashboard', [
            'teacherCount' => User::query()->roleNamed(User::ROLE_TEACHER)->count(),
            'studentCount' => User::query()->roleNamed(User::ROLE_STUDENT)->count(),
            'transactionYearOptions' => $this->transactionYearOptions($selectedYear),
            'selectedTransactionYear' => (string) $selectedYear,
            'transactionChart' => $chart,
            'noteCount' => $this->noteModelExists() ? Note::query()->count() : 0,
            'noteImageCount' => $this->noteImageModelExists() ? NoteImage::query()->count() : 0,
            'recentNotes' => $this->recentNotes(),
            'recentNoteImages' => $this->recentNoteImages(),
        ]);
    }

    private function recentNotes(): Collection
    {
        if (! $this->noteModelExists()) {
            return collect();
        }

        return Note::query()
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'student_id',
                'author_id',
                'author_name_snapshot',
                'author_role_snapshot',
                'body',
                'note_date',
                'created_at',
            ]);
    }

    private function recentNoteImages(): Collection
    {
        if (! $this->noteImageModelExists()) {
            return collect();
        }

        return NoteImage::query()
            ->latest()
            ->limit(10)
            ->get([
                'id',
                'note_id',
                'original_filename',
                'mime_type',
                'size_bytes',
                'sort_order',
                'created_at',
            ]);
    }

    private function noteModelExists(): bool
    {
        return class_exists(Note::class);
    }

    private function noteImageModelExists(): bool
    {
        return class_exists(NoteImage::class);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);
    }

    private function resolveSelectedYear(): int
    {
        $year = (int) request()->integer('tahun');

        if ($year >= 2000 && $year <= 2100) {
            return $year;
        }

        return now()->year;
    }

    private function transactionYearOptions(int $selectedYear): Collection
    {
        $years = Transaction::query()
            ->selectRaw($this->yearExpression().' as period_year')
            ->distinct()
            ->orderByDesc('period_year')
            ->pluck('period_year')
            ->filter(fn ($value) => $value !== null)
            ->map(fn ($value) => (int) $value)
            ->values();

        if (! $years->contains($selectedYear)) {
            $years->prepend($selectedYear);
        }

        return $years
            ->unique()
            ->map(function (int $year): array {
                return [
                    'value' => (string) $year,
                    'label' => (string) $year,
                ];
            })
            ->values();
    }

    private function transactionChart(int $selectedYear): array
    {
        $totalsByMonth = Transaction::query()
            ->whereYear('tanggal', $selectedYear)
            ->selectRaw($this->monthExpression().' as month_number')
            ->selectRaw('SUM(jumlah) as total_amount')
            ->selectRaw('COUNT(*) as total_transactions')
            ->groupBy('month_number')
            ->orderBy('month_number')
            ->get()
            ->keyBy('month_number');

        $points = collect(range(1, 12))
            ->map(function (int $month) use ($totalsByMonth, $selectedYear): array {
                $row = $totalsByMonth->get($month);

                return [
                    'month' => $month,
                    'label' => Carbon::create($selectedYear, $month, 1)->locale('id')->translatedFormat('F'),
                    'amount' => (int) ($row->total_amount ?? 0),
                    'count' => (int) ($row->total_transactions ?? 0),
                ];
            });

        return [
            'points' => $points,
            'maxAmount' => max(1, (int) $points->max('amount')),
            'totalAmount' => (int) $points->sum('amount'),
            'totalTransactions' => (int) $points->sum('count'),
        ];
    }

    private function yearExpression(): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? 'YEAR(tanggal)'
            : "CAST(strftime('%Y', tanggal) as integer)";
    }

    private function monthExpression(): string
    {
        return DB::connection()->getDriverName() === 'mysql'
            ? 'MONTH(tanggal)'
            : "CAST(strftime('%m', tanggal) as integer)";
    }
}
