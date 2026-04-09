<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction', function (Blueprint $table) {
            $table->id('id_transaksi');
            $table->foreignId('id_murid')->constrained('user', 'id_user')->restrictOnDelete();
            $table->date('tanggal');
            $table->integer('jumlah');
            $table->timestamps();

            $table->index('id_murid');
            $table->index('tanggal');
        });

        Schema::create('detail_transaction', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_transaksi')->constrained('transaction', 'id_transaksi')->cascadeOnDelete();
            $table->unsignedTinyInteger('bulan');
            $table->unsignedSmallInteger('tahun');

            $table->index('bulan');
            $table->index('tahun');
            $table->unique(['id_transaksi', 'bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_transaction');
        Schema::dropIfExists('transaction');
    }
};
