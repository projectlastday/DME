<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherNoteCrudTest extends TestCase
{
    use RefreshDatabase;

    protected array $calls = [];

    protected function setUp(): void
    {
        parent::setUp();

        require base_path('routes/teacher.php');
    }

    public function test_teacher_can_create_edit_delete_notes_and_delete_note_images(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);
        $student = User::factory()->create(['name' => 'Ari']);

        $this->app->instance('App\Services\Notes\NoteQueryService', new class($teacher, $student)
        {
            public function __construct(private User $teacher, private User $student)
            {
            }

            public function noteEditData(int|string $noteId, int|string $userId): array
            {
                return [
                    'student' => ['id' => $this->student->getKey(), 'name' => $this->student->name],
                    'note' => [
                        'id' => $noteId,
                        'student_id' => $this->student->getKey(),
                        'author_id' => $this->teacher->getKey(),
                        'author_role_snapshot' => 'teacher',
                        'body' => 'Original note',
                        'note_date' => '2026-03-30',
                        'images' => [
                            ['id' => 701, 'url' => '/note-images/701'],
                        ],
                    ],
                ];
            }
        });

        $calls = &$this->calls;

        $this->app->instance('App\Services\Notes\NoteWriter', new class($student, $calls)
        {
            public function __construct(private User $student, private array &$calls)
            {
            }

            public function create(int|string $studentId, array $data, int|string $userId): array
            {
                $this->calls[] = ['method' => 'create', 'studentId' => $studentId, 'data' => $data, 'userId' => $userId];

                return ['id' => 201, 'student_id' => $studentId];
            }

            public function update(int|string $noteId, array $data, int|string $userId): array
            {
                $this->calls[] = ['method' => 'update', 'noteId' => $noteId, 'data' => $data, 'userId' => $userId];

                return ['id' => $noteId, 'student_id' => $this->student->getKey()];
            }

            public function delete(int|string $noteId, int|string $userId): void
            {
                $this->calls[] = ['method' => 'delete', 'noteId' => $noteId, 'userId' => $userId];
            }

            public function deleteImage(int|string $noteImageId, int|string $userId): void
            {
                $this->calls[] = ['method' => 'deleteImage', 'noteImageId' => $noteImageId, 'userId' => $userId];
            }
        });

        $createResponse = $this->actingAs($teacher)->post("/teacher/students/{$student->getKey()}/notes", [
            'body' => 'Created note',
            'note_date' => '2026-03-31',
        ]);

        $createResponse->assertRedirect("/teacher/students/{$student->getKey()}");

        $editResponse = $this->actingAs($teacher)->get('/teacher/notes/201/edit');
        $editResponse->assertOk();
        $editResponse->assertSee('Original note');

        $updateResponse = $this->actingAs($teacher)->put('/teacher/notes/201', [
            'body' => 'Updated note',
            'note_date' => '2026-03-31',
            'retained_image_ids' => ['701'],
        ]);

        $updateResponse->assertRedirect("/teacher/students/{$student->getKey()}");

        $deleteResponse = $this->actingAs($teacher)->delete('/teacher/notes/201');
        $deleteResponse->assertRedirect("/teacher/students/{$student->getKey()}");

        $deleteImageResponse = $this->actingAs($teacher)->delete('/teacher/note-images/701', [
            'student_id' => $student->getKey(),
        ]);

        $deleteImageResponse->assertRedirect("/teacher/students/{$student->getKey()}");

        $this->assertSame(['create', 'update', 'delete', 'deleteImage'], array_column($this->calls, 'method'));
    }

    public function test_teacher_can_create_note_with_only_an_image(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);
        $student = User::factory()->create(['name' => 'Ari']);

        $calls = &$this->calls;

        $this->app->instance('App\Services\Notes\NoteWriter', new class($calls)
        {
            public function __construct(private array &$calls)
            {
            }

            public function create(int|string $studentId, array $data, int|string $userId): array
            {
                $this->calls[] = ['method' => 'create', 'studentId' => $studentId, 'data' => $data, 'userId' => $userId];

                return ['id' => 301, 'student_id' => $studentId];
            }
        });

        $response = $this->actingAs($teacher)->post("/teacher/students/{$student->getKey()}/notes", [
            'body' => '',
            'note_date' => '2026-03-31',
            'new_images' => [
                UploadedFile::fake()->image('photo.jpg'),
            ],
        ]);

        $response->assertRedirect("/teacher/students/{$student->getKey()}");
        $this->assertCount(1, $this->calls);
        $this->assertSame('create', $this->calls[0]['method']);
        $this->assertNull($this->calls[0]['data']['body']);
        $this->assertCount(1, $this->calls[0]['data']['images']);
    }
}
