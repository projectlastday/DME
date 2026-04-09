<?php

namespace Tests\Feature\Admin;

use App\Models\DetailTransaction;
use App\Models\Transaction;
use App\Models\User;
use Tests\TestCase;

class AdminTransactionManagementTest extends TestCase
{
    public function test_super_admin_can_create_transaction_with_multiple_periods(): void
    {
        $admin = $this->createSuperAdmin(['name' => 'Admin Transaksi']);
        $student = $this->createStudent(['name' => 'Murid Satu']);

        $response = $this->actingAs($admin)->post(route('admin.transactions.store'), [
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 500000,
            'periods' => ['2026-04', '2026-05'],
        ]);

        $transaction = Transaction::query()->firstOrFail();

        $response->assertRedirect(route('admin.transactions.show', $transaction));
        $this->assertSame($student->getKey(), $transaction->id_murid);
        $this->assertSame(500000, $transaction->jumlah);
        $this->assertSame(['2026-04', '2026-05'], $transaction->details->map(fn ($detail) => sprintf('%04d-%02d', $detail->tahun, $detail->bulan))->all());
    }

    public function test_create_fails_when_period_is_already_paid(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent();
        $existing = Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 200000,
        ]);
        $existing->details()->create([
            'bulan' => 4,
            'tahun' => 2026,
        ]);

        $response = $this->from(route('admin.transactions.create'))
            ->actingAs($admin)
            ->post(route('admin.transactions.store'), [
                'id_murid' => $student->getKey(),
                'tanggal' => '2026-04-10',
                'jumlah' => 300000,
                'periods' => ['2026-04', '2026-05'],
            ]);

        $response->assertRedirect(route('admin.transactions.create'));
        $response->assertSessionHasErrors('periods');
        $this->assertCount(1, Transaction::query()->get());
    }

    public function test_list_can_filter_by_student_and_period(): void
    {
        $admin = $this->createSuperAdmin();
        $studentA = $this->createStudent(['name' => 'Alya']);
        $studentB = $this->createStudent(['name' => 'Bima']);

        $transactionA = Transaction::query()->create([
            'id_murid' => $studentA->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 100000,
            'created_at' => '2026-04-01 08:00:00',
            'updated_at' => '2026-04-01 08:00:00',
        ]);
        $transactionA->details()->createMany([
            ['bulan' => 4, 'tahun' => 2026],
            ['bulan' => 5, 'tahun' => 2026],
        ]);

        $transactionB = Transaction::query()->create([
            'id_murid' => $studentB->getKey(),
            'tanggal' => '2026-04-03',
            'jumlah' => 120000,
            'created_at' => '2026-04-03 09:00:00',
            'updated_at' => '2026-04-03 09:00:00',
        ]);
        $transactionB->details()->create([
            'bulan' => 6,
            'tahun' => 2026,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.index', [
                'id_murid' => $studentA->getKey(),
                'periode' => '2026-05',
            ]))
            ->assertOk()
            ->assertSee('Alya')
            ->assertSee('April 2026')
            ->assertSee('Mei 2026')
            ->assertSee('Rp100.000')
            ->assertDontSee('Rp120.000');
    }

    public function test_super_admin_can_view_and_update_transaction_without_changing_student(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent(['name' => 'Citra']);

        $transaction = Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 150000,
        ]);
        $transaction->details()->createMany([
            ['bulan' => 4, 'tahun' => 2026],
            ['bulan' => 5, 'tahun' => 2026],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.transactions.show', $transaction))
            ->assertOk()
            ->assertSee('Citra')
            ->assertSee('01 April 2026', false);

        $response = $this->actingAs($admin)->put(route('admin.transactions.update', $transaction), [
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-15',
            'jumlah' => 175000,
            'periods' => ['2026-06'],
        ]);

        $response->assertRedirect(route('admin.transactions.show', $transaction));

        $transaction->refresh();

        $this->assertSame(175000, $transaction->jumlah);
        $this->assertSame('2026-04-15', $transaction->tanggal->toDateString());
        $this->assertSame(['2026-06'], $transaction->details->map(fn ($detail) => sprintf('%04d-%02d', $detail->tahun, $detail->bulan))->all());
    }

    public function test_delete_removes_transaction_and_details(): void
    {
        $admin = $this->createSuperAdmin();
        $student = $this->createStudent();

        $transaction = Transaction::query()->create([
            'id_murid' => $student->getKey(),
            'tanggal' => '2026-04-01',
            'jumlah' => 150000,
        ]);
        $transaction->details()->createMany([
            ['bulan' => 4, 'tahun' => 2026],
            ['bulan' => 5, 'tahun' => 2026],
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.transactions.destroy', $transaction))
            ->assertRedirect(route('admin.transactions.index'));

        $this->assertDatabaseMissing('transaction', ['id_transaksi' => $transaction->getKey()]);
        $this->assertSame(0, DetailTransaction::query()->count());
    }
}
