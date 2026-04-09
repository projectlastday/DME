<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    protected $table = 'role';

    protected $primaryKey = 'id_role';

    public $timestamps = false;

    protected $fillable = [
        'nama',
    ];

    public static function idForName(string $name): int
    {
        $name = User::normalizeRoleName($name) ?? $name;

        $id = static::query()
            ->where('nama', $name)
            ->value('id_role');

        if ($id === null) {
            throw new \RuntimeException("Role [{$name}] is not available.");
        }

        return (int) $id;
    }

    public static function nameForId(int $id): ?string
    {
        return User::normalizeRoleName(DB::table('role')
            ->where('id_role', $id)
            ->value('nama'));
    }
}
