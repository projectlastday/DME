<?php

namespace Tests\Feature\Admin;

use App\Models\Transaction;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    public function test_super_admin_can_view_transaction_chart_for_selected_year(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(['name' => 'Murid Grafik']);

        Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 150000,
            'created_at' => '2026-04-01 10:00:00',
            'updated_at' => '2026-04-01 10:00:00',
        ]);

        Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-05-15',
            'jumlah' => 250000,
            'created_at' => '2026-05-15 10:00:00',
            'updated_at' => '2026-05-15 10:00:00',
        ]);

        Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2025-03-20',
            'jumlah' => 90000,
            'created_at' => '2025-03-20 10:00:00',
            'updated_at' => '2025-03-20 10:00:00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard', ['tahun' => '2026']))
            ->assertOk()
            ->assertSee('Grafik transaksi')
            ->assertSee('Rp400.000')
            ->assertSee('2')
            ->assertSee('name="tahun"', false)
            ->assertSee('>1<', false)
            ->assertSee('>12<', false);
    }
}
