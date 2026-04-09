<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    protected $table = 'transaction';

    protected $primaryKey = 'id_transaksi';

    protected $fillable = [
        'id_murid',
        'tanggal',
        'jumlah',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_murid', 'id_user');
    }

    public function details(): HasMany
    {
        return $this->hasMany(DetailTransaction::class, 'id_transaksi', 'id_transaksi')
            ->orderBy('tahun')
            ->orderBy('bulan');
    }
}
