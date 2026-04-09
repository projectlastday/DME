<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminPasswordResetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminRoutes();
    }

    public function test_admin_can_reset_teacher_password(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-password-one');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-password-one');

        $response = $this->actingAs($admin)->post(route('admin.teachers.reset-password', $teacher), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.teachers.edit', $teacher));

        $teacher->refresh();

        $this->assertTrue(Hash::check('newpassword123', $teacher->password));
    }

    public function test_admin_can_reset_student_password(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-password-two');
        $student = $this->makeUser(role: 'student', username: 'student-password-one');

        $response = $this->actingAs($admin)->post(route('admin.students.reset-password', $student), [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('admin.students.edit', $student));

        $student->refresh();

        $this->assertTrue(Hash::check('newpassword123', $student->password));
    }

    private function registerAdminRoutes(): void
    {
        if (! Route::has('admin.dashboard')) {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        }
    }

    private function makeUser(string $role, string $username): User
    {
        $user = new User();
        $user->forceFill([
            'name' => ucfirst(str_replace('-', ' ', $username)),
            'username' => $username,
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
        $user->save();

        return $user->fresh();
    }
}
