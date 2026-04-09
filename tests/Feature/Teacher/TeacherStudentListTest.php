<?php

namespace Tests\Feature\Teacher;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TeacherStudentListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        require base_path('routes/teacher.php');
    }

    public function test_teacher_dashboard_route_redirects_to_student_list(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);

        $response = $this->actingAs($teacher)->get('/teacher');

        $response->assertRedirect('/teacher/students');
    }

    public function test_teacher_can_search_the_student_list(): void
    {
        $teacher = User::factory()->create()->forceFill(['role' => 'teacher']);

        $this->app->instance('App\Services\Notes\NoteQueryService', new class
        {
            public function teacherStudentList(string $search): array
            {
                return [
                    'students' => [
                        ['id' => 11, 'name' => 'Maya Chen', 'username' => 'maya'],
                    ],
                    'total' => 1,
                    'search' => $search,
                ];
            }
        });

        $response = $this->actingAs($teacher)->get('/teacher/students?search=maya');

        $response->assertOk();
        $response->assertSee('Maya Chen');
        $response->assertDontSee('No students matched this search.');
    }
}
