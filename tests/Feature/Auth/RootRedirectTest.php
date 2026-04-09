<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    public function test_guests_are_redirected_to_login_from_root(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    #[DataProvider('roleRedirectProvider')]
    public function test_authenticated_users_are_redirected_from_root_by_role(string $role, string $expectedRoute): void
    {
        $user = $this->createUser([
            'role' => $role,
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route($expectedRoute));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function roleRedirectProvider(): array
    {
        return [
            'super admin' => [User::ROLE_SUPER_ADMIN, 'admin.dashboard'],
            'teacher' => [User::ROLE_TEACHER, 'teacher.dashboard'],
            'student' => [User::ROLE_STUDENT, 'student.dashboard'],
        ];
    }
}
