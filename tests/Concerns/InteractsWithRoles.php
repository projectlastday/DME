<?php

namespace Tests\Concerns;

use App\Models\User;

trait InteractsWithRoles
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createSuperAdmin(array $attributes = []): User
    {
        return User::factory()->superAdmin()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createTeacher(array $attributes = []): User
    {
        return User::factory()->teacher()->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createStudent(array $attributes = []): User
    {
        return User::factory()->student()->create($attributes);
    }
}
