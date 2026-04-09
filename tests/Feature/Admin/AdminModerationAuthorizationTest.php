<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminModerationAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->registerAdminRoutes();
    }

    public function test_non_admins_are_blocked_from_note_moderation_routes(): void
    {
        if (! class_exists(\App\Models\Note::class)) {
            $this->markTestSkipped('Phase 2 note model is not present in this checkout.');
        }

        $student = $this->makeUser(role: 'student', username: 'student-owner');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-blocked');
        $noteId = DB::table('notes')->insertGetId([
            'student_id' => $student->id,
            'author_id' => $teacher->id,
            'author_name_snapshot' => $teacher->name,
            'author_role_snapshot' => 'teacher',
            'body' => 'Moderation target note',
            'note_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = \App\Models\Note::query()->findOrFail($noteId);

        $response = $this->actingAs($teacher)->delete(route('admin.notes.destroy', $note));

        $response->assertForbidden();
        $this->assertDatabaseHas('notes', ['id' => $noteId]);
    }

    public function test_admin_can_delete_note_and_note_image(): void
    {
        if (! class_exists(\App\Models\Note::class) || ! class_exists(\App\Models\NoteImage::class)) {
            $this->markTestSkipped('Phase 2 note models are not present in this checkout.');
        }

        $admin = $this->makeUser(role: 'super_admin', username: 'admin-moderator');
        $student = $this->makeUser(role: 'student', username: 'student-target');
        $teacher = $this->makeUser(role: 'teacher', username: 'teacher-target');

        $noteId = DB::table('notes')->insertGetId([
            'student_id' => $student->id,
            'author_id' => $teacher->id,
            'author_name_snapshot' => $teacher->name,
            'author_role_snapshot' => 'teacher',
            'body' => 'Moderation target note',
            'note_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imageId = DB::table('note_images')->insertGetId([
            'note_id' => $noteId,
            'disk' => 'private',
            'path' => 'notes/sample.webp',
            'original_filename' => 'sample.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => 1024,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $note = \App\Models\Note::query()->findOrFail($noteId);
        $noteImage = \App\Models\NoteImage::query()->findOrFail($imageId);

        $this->actingAs($admin)
            ->delete(route('admin.note-images.destroy', $noteImage))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('note_images', ['id' => $imageId]);

        $this->actingAs($admin)
            ->delete(route('admin.notes.destroy', $note))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('notes', ['id' => $noteId]);
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
