<?php

namespace Tests\Feature\Shared;

use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PrivateNoteImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/login', fn () => 'login')->name('login');
        require base_path('routes/shared.php');
        Storage::fake(NoteImageStorage::PRIVATE_DISK);
    }

    public function test_guest_cannot_access_private_image_route(): void
    {
        $imageId = $this->seedImageFixture();

        $this->get("/note-images/{$imageId}")
            ->assertRedirect('/login');
    }

    public function test_student_can_access_own_private_image(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $imageId = $this->seedImageFixture($student->id, $teacher->id, 'owned.png', 'owned-content');

        $this->actingAs($student)
            ->get("/note-images/{$imageId}")
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertStreamedContent('owned-content');
    }

    public function test_unrelated_student_is_forbidden(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $otherStudent = $this->seedActor('student', 'Student Two', 'student-two');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $imageId = $this->seedImageFixture($student->id, $teacher->id, 'forbidden.png', 'forbidden-content');

        $this->actingAs($otherStudent)
            ->get("/note-images/{$imageId}")
            ->assertForbidden();
    }

    public function test_teacher_can_access_private_image_through_shared_controller(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $imageId = $this->seedImageFixture($student->id, $teacher->id, 'teacher.png', 'teacher-content');

        $this->actingAs($teacher)
            ->get("/note-images/{$imageId}")
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertStreamedContent('teacher-content');
    }

    public function test_teacher_can_access_private_image_with_non_prefixed_stored_path(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $imageId = $this->seedImageFixture($student->id, $teacher->id, 'plain-path.png', 'plain-content', false);

        $this->actingAs($teacher)
            ->get("/note-images/{$imageId}")
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertStreamedContent('plain-content');
    }

    private function seedImageFixture(?int $studentId = null, ?int $authorId = null, string $filename = 'test.png', string $contents = 'binary-image', bool $withPrefix = true): int
    {
        $student = $studentId ? User::query()->findOrFail($studentId) : $this->seedActor('student', 'Seed Student', 'seed-student');
        $author = $authorId ? User::query()->findOrFail($authorId) : $this->seedActor('teacher', 'Seed Teacher', 'seed-teacher');

        $noteId = DB::table('notes')->insertGetId([
            'student_id' => $student->id,
            'author_id' => $author->id,
            'author_name_snapshot' => $author->name,
            'author_role_snapshot' => $author->role,
            'body' => 'Body',
            'note_date' => '2026-03-31',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $path = $withPrefix
            ? "note-images/note-{$noteId}/{$filename}"
            : "note-{$noteId}/{$filename}";
        Storage::disk(NoteImageStorage::PRIVATE_DISK)->put($path, $contents);

        return (int) DB::table('note_images')->insertGetId([
            'note_id' => $noteId,
            'disk' => NoteImageStorage::PRIVATE_DISK,
            'path' => $path,
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'size_bytes' => strlen($contents),
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedActor(string $role, string $name, string $username): User
    {
        return match ($role) {
            'super_admin' => $this->createSuperAdmin(['name' => $name, 'username' => $username]),
            'student' => $this->createStudent(['name' => $name, 'username' => $username]),
            default => $this->createTeacher(['name' => $name, 'username' => $username]),
        };
    }
}
