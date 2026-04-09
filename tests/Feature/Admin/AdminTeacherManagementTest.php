<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminTeacherManagementTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminRoutes();
    }

    public function test_admin_can_create_teacher(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-one');

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'Teacher One',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('user', [
            'nama' => 'Teacher One',
            'id_role' => Role::idForName(User::ROLE_TEACHER),
        ]);
    }

    public function test_admin_can_create_teacher_with_short_password(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-one-short');

        $response = $this->actingAs($admin)->post(route('admin.teachers.store'), [
            'name' => 'Teacher Short',
            'password' => 'abc',
        ]);

        $response->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('user', [
            'nama' => 'Teacher Short',
            'id_role' => Role::idForName(User::ROLE_TEACHER),
        ]);
    }

    public function test_admin_can_update_teacher(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-two');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-old', name: 'Teacher Old');

        $response = $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'name' => 'Teacher New',
        ]);

        $response->assertRedirect(route('admin.teachers.edit', $teacher));

        $this->assertDatabaseHas('user', [
            'id_user' => $teacher->getKey(),
            'nama' => 'Teacher New',
            'id_role' => Role::idForName(User::ROLE_TEACHER),
        ]);
    }

    public function test_admin_can_view_teacher_detail(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-detail');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-detail', name: 'Teacher Detail');

        $this->actingAs($admin)
            ->get(route('admin.teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Detail Guru')
            ->assertSee('Teacher Detail')
            ->assertSee('#'.$teacher->getKey());
    }

    public function test_admin_can_delete_teacher(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-three');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-drop');

        $response = $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('user', [
            'id_user' => $teacher->getKey(),
        ]);
    }

    public function test_admin_deleting_teacher_also_deletes_notes_authored_by_teacher(): void
    {
        $admin = $this->makeUser(role: 'super_admin', username: 'admin-notes');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-notes', name: 'Teacher Notes');
        $student = $this->makeUser(role: 'student', username: 'student-notes', name: 'Student Notes');

        $note = Note::query()->create([
            'student_id' => $student->getKey(),
            'author_id' => $teacher->getKey(),
            'author_name_snapshot' => $teacher->name,
            'author_role_snapshot' => User::ROLE_TEACHER,
            'body' => 'Teacher-authored note',
            'note_date' => '2026-04-02',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.teachers.destroy', $teacher));

        $response->assertRedirect(route('admin.teachers.index'));

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
