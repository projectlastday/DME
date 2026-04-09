<?php

namespace Tests\Feature\Student;

use App\Models\Note;
use App\Models\User;
use Tests\TestCase;

class StudentNotesIndexTest extends TestCase
{
    public function test_student_dashboard_redirects_to_teacher_notes_and_only_shows_teacher_and_admin_notes(): void
    {
        $student = $this->createStudent([
            'name' => 'Nadia Student',
            'username' => 'nadia-student',
        ]);
        $otherStudent = $this->createStudent([
            'name' => 'Other Student',
            'username' => 'other-student',
        ]);
        $teacher = $this->createTeacher([
            'name' => 'Teacher Ada',
            'username' => 'teacher-ada',
        ]);
        $admin = $this->createSuperAdmin([
            'name' => 'Admin Lee',
            'username' => 'admin-lee',
        ]);

        Note::factory()->forStudent($student)->authoredBy($teacher)->create([
            'body' => 'Teacher note for Nadia',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($admin)->create([
            'body' => 'Admin note for Nadia',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($student)->create([
            'body' => 'Nadia private note',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($otherStudent)->authoredBy($teacher)->create([
            'body' => 'Teacher note for someone else',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertRedirect(route('student.notes.index'));

        $this->actingAs($student)
            ->get(route('student.notes.index'))
            ->assertOk()
            ->assertSee('Catatan Guru')
            ->assertSee('Teacher note for Nadia')
            ->assertSee('Admin note for Nadia')
            ->assertDontSee('Nadia private note')
            ->assertDontSee('Teacher note for someone else');
    }

    public function test_student_notes_page_ignores_old_tab_query_and_still_shows_only_teacher_notes(): void
    {
        $student = $this->createStudent([
            'name' => 'Mina Student',
            'username' => 'mina-student',
        ]);
        $teacher = $this->createTeacher([
            'name' => 'Teacher Noor',
            'username' => 'teacher-noor',
        ]);
        $otherStudent = $this->createStudent([
            'name' => 'Lia Student',
            'username' => 'lia-student',
        ]);

        Note::factory()->forStudent($student)->authoredBy($teacher)->create([
            'body' => 'Teacher note visible only in teacher tab',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($student)->create([
            'body' => 'My owned note',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($otherStudent)->authoredBy($otherStudent)->create([
            'body' => 'Other student private note',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($student)
            ->get(route('student.notes.index', ['tab' => 'mine']))
            ->assertOk()
            ->assertSee('Teacher note visible only in teacher tab')
            ->assertDontSee('My owned note')
            ->assertDontSee('Other student private note');

        $this->actingAs($student)
            ->get(route('student.notes.index', ['tab' => 'nope']))
            ->assertOk()
            ->assertSee('Teacher note visible only in teacher tab')
            ->assertDontSee('My owned note');
    }
}
