<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        require base_path('routes/teacher.php');
    }

    public function test_non_teacher_cannot_access_teacher_pages(): void
    {
        $studentUser = User::factory()->create()->forceFill(['role' => 'student']);

        $response = $this->actingAs($studentUser)->get('/teacher');

        $response->assertForbidden();
    }

    public function test_teacher_cannot_edit_student_authored_notes(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);
        $student = User::factory()->create();

        $this->app->instance('App\Services\Notes\NoteQueryService', new class($student)
        {
            public function __construct(private User $student)
            {
            }

            public function noteEditData(int|string $noteId, int|string $userId): array
            {
                return [
                    'student' => ['id' => $this->student->getKey(), 'name' => $this->student->name],
                    'note' => [
                        'id' => $noteId,
                        'student_id' => $this->student->getKey(),
                        'author_id' => 999,
                        'author_role_snapshot' => 'student',
                        'body' => 'Student note',
                        'note_date' => '2026-03-30',
                        'images' => [],
                    ],
                ];
            }
        });

        $response = $this->actingAs($teacher)->get('/teacher/notes/401/edit');

        $response->assertForbidden();
    }

    public function test_teacher_cannot_edit_other_teachers_notes(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);
        $student = User::factory()->create();

        $this->app->instance('App\Services\Notes\NoteQueryService', new class($student)
        {
            public function __construct(private User $student)
            {
            }

            public function noteEditData(int|string $noteId, int|string $userId): array
            {
                return [
                    'student' => ['id' => $this->student->getKey(), 'name' => $this->student->name],
                    'note' => [
                        'id' => $noteId,
                        'student_id' => $this->student->getKey(),
                        'author_id' => 12345,
                        'author_role_snapshot' => 'teacher',
                        'body' => 'Other teacher note',
                        'note_date' => '2026-03-30',
                        'images' => [],
                    ],
                ];
            }
        });

        $response = $this->actingAs($teacher)->put('/teacher/notes/402', [
            'body' => 'Attempted update',
            'note_date' => '2026-03-31',
        ]);

        $response->assertForbidden();
    }
}
