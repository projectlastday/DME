<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminRoutes();
    }

    public function test_admin_can_create_student(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-student-one');

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student One',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('user', [
            'nama' => 'Student One',
            'id_role' => Role::idForName(User::ROLE_STUDENT),
        ]);
    }

    public function test_admin_can_create_student_with_short_password(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-student-short');

        $response = $this->actingAs($admin)->post(route('admin.students.store'), [
            'name' => 'Student Short',
            'password' => 'abc',
        ]);

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseHas('user', [
            'nama' => 'Student Short',
            'id_role' => Role::idForName(User::ROLE_STUDENT),
        ]);
    }

    public function test_admin_can_update_student(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-student-two');
        $student = $this->makeUser(role: 'student', username: 'student-old', name: 'Student Old');

        $response = $this->actingAs($admin)->put(route('admin.students.update', $student), [
            'name' => 'Student New',
        ]);

        $response->assertRedirect(route('admin.students.edit', $student));

        $this->assertDatabaseHas('user', [
            'id_user' => $student->getKey(),
            'nama' => 'Student New',
            'id_role' => Role::idForName(User::ROLE_STUDENT),
        ]);
    }

    public function test_admin_can_delete_student(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-student-three');
        $student = $this->makeUser(role: 'student', username: 'student-drop');

        $response = $this->actingAs($admin)->delete(route('admin.students.destroy', $student));

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseMissing('user', [
            'id_user' => $student->getKey(),
        ]);
    }

    public function test_admin_deleting_student_also_deletes_notes_for_that_student(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-student-notes');
        $student = $this->makeUser(role: 'student', username: 'student-notes', name: 'Student Notes');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-for-student-notes', name: 'Teacher Notes');

        $note = Note::query()->create([
            'student_id' => $student->getKey(),
            'author_id' => $teacher->getKey(),
            'author_name_snapshot' => $teacher->name,
            'author_role_snapshot' => User::ROLE_TEACHER,
            'body' => 'Note for student',
            'note_date' => '2026-04-02',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.students.destroy', $student));

        $response->assertRedirect(route('admin.students.index'));

        $this->assertDatabaseMissing('notes', [
            'id' => $note->getKey(),
        ]);
    }

    private function registerAdminRoutes(): void
    {
        if (! Route::has('admin.dashboard')) {
            Route::middleware('web')->group(base_path('routes/admin.php'));
        }
    }

    private function makeUser(string $role, string $username, ?string $name = null): User
    {
        $user = new User();
        $user->forceFill([
            'name' => $name ?? ucfirst(str_replace('-', ' ', $username)),
            'username' => $username,
            'role' => $role,
            'password' => Hash::make('password123'),
        ]);
        $user->save();

        return $user->fresh();
    }
}
