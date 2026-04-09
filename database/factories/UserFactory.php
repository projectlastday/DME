<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'username' => null,
            'id_role' => Role::idForName(User::ROLE_TEACHER),
            'password' => static::$password ??= Hash::make('password'),
        ];
    }

    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'id_role' => Role::idForName(User::ROLE_SUPER_ADMIN),
        ]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['id_role' => Role::idForName(User::ROLE_TEACHER)]);
    }

    public function student(): static
    {
        return $this->state(fn () => ['id_role' => Role::idForName(User::ROLE_STUDENT)]);
    }
}
