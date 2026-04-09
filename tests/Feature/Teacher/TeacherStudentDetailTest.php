<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherStudentDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        require base_path('routes/teacher.php');
    }

    public function test_teacher_can_open_any_student_detail_page_and_see_teacher_and_student_notes(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);
        $student = User::factory()->student()->create(['name' => 'Nadia']);

        $this->app->instance('App\Services\Notes\NoteQueryService', new class($student)
        {
            public function __construct(private User $student)
            {
            }

            public function teacherStudentDetail(int|string $studentId): array
            {
                return [
                    'student' => ['id' => $studentId, 'name' => $this->student->name],
                    'grouped_notes' => [
                        '2026-03-30' => [
                            [
                                'id' => 101,
                                'student_id' => $studentId,
                                'author_id' => 44,
                                'author_name_snapshot' => 'Teacher A',
                                'author_role_snapshot' => 'teacher',
                                'body' => 'Teacher note body',
                                'created_at' => '2026-03-30 09:00',
                                'images' => [],
                            ],
                            [
                                'id' => 102,
                                'student_id' => $studentId,
                                'author_id' => 55,
                                'author_name_snapshot' => 'Student Nadia',
                                'author_role_snapshot' => 'student',
                                'body' => 'Student note body',
                                'created_at' => '2026-03-30 08:00',
                                'images' => [],
                            ],
                        ],
                    ],
                ];
            }
        });

        $response = $this->actingAs($teacher)->get("/teacher/students/{$student->getKey()}");

        $response->assertOk();
        $response->assertSee('Nadia');
        $response->assertSee('Teacher note body');
        $response->assertSee('Student note body');
        $response->assertSee('Buat catatan');
    }
}
