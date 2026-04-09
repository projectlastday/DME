<?php

namespace Tests\Feature\Student;

use App\Models\Note;
use Tests\TestCase;

class StudentAuthorizationTest extends TestCase
{
    public function test_non_student_cannot_access_student_pages(): void
    {
        $teacher = $this->createTeacher([
            'username' => 'teacher-blocked',
        ]);

        $this->actingAs($teacher)
            ->get(route('student.dashboard'))
            ->assertForbidden();
    }

    public function test_student_cannot_modify_teacher_or_admin_authored_notes_for_self(): void
    {
        $student = $this->createStudent([
            'username' => 'student-owner',
        ]);
        $teacher = $this->createTeacher([
            'username' => 'teacher-owner',
        ]);
        $admin = $this->createSuperAdmin([
            'username' => 'admin-owner',
        ]);

        $teacherNote = Note::factory()->forStudent($student)->authoredBy($teacher)->create([
            'body' => 'Teacher-owned note',
            'note_date' => '2026-03-31',
        ]);
        $adminNote = Note::factory()->forStudent($student)->authoredBy($admin)->create([
            'body' => 'Admin-owned note',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($student)
            ->get(route('student.notes.edit', $teacherNote))
            ->assertForbidden();

        $this->actingAs($student)
            ->put(route('student.notes.update', $teacherNote), [
                'body' => 'Attempted change',
                'note_date' => '2026-04-01',
            ])
            ->assertForbidden();

        $this->actingAs($student)
            ->delete(route('student.notes.destroy', $adminNote))
            ->assertForbidden();

        $this->assertDatabaseHas('notes', ['id' => $teacherNote->getKey()]);
        $this->assertDatabaseHas('notes', ['id' => $adminNote->getKey()]);
    }
}
