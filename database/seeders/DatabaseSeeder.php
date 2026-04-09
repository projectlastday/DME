<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->seedUser('System Admin', User::ROLE_SUPER_ADMIN);
        $this->seedUser('Teacher Demo', User::ROLE_TEACHER);
        $this->seedUser('Student Demo', User::ROLE_STUDENT);
    }

    private function seedUser(string $name, string $role): void
    {
        DB::table('user')->updateOrInsert([
            'nama' => $name,
            'id_role' => Role::idForName($role),
        ], [
            'password' => Hash::make('password'),
        ]);
    }
}
