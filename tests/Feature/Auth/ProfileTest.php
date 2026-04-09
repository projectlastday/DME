<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class ProfileTest extends TestCase
{
    public function test_authenticated_user_can_open_profile_page(): void
    {
        $user = $this->createTeacher([
            'name' => 'Guru Profil',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profil')
            ->assertSee('Nama akun');
    }

    public function test_authenticated_user_can_update_profile_name(): void
    {
        $user = $this->createTeacher([
            'name' => 'Guru Lama',
        ]);

        $response = $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Guru Baru',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertDatabaseHas('user', [
            'id_user' => $user->getKey(),
            'nama' => 'Guru Baru',
        ]);
    }
}
