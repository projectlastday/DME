<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_users_can_log_in_with_name_and_password(): void
    {
        $user = $this->createTeacher([
            'name' => 'Teacher Alpha',
            'username' => 'teacher-alpha',
            'password' => 'secret-pass',
        ]);

        $response = $this->post(route('login.attempt'), [
            'login' => 'Teacher Alpha',
            'password' => 'secret-pass',
        ]);

        $response->assertRedirect(route('teacher.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_is_rejected(): void
    {
        $this->createTeacher([
            'name' => 'Teacher Alpha',
            'username' => 'teacher-alpha',
            'password' => 'secret-pass',
        ]);

        $response = $this->from(route('login'))->post(route('login.attempt'), [
            'login' => 'Teacher Alpha',
            'password' => 'wrong-pass',
        ]);

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_super_admin_login_ignores_intended_url_and_redirects_to_admin_dashboard(): void
    {
        $admin = $this->createSuperAdmin([
            'name' => 'System Admin',
            'password' => 'password',
        ]);

        $response = $this->withSession([
            'url.intended' => route('admin.transactions.index'),
        ])->post(route('login.attempt'), [
            'login' => 'System Admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }
}
