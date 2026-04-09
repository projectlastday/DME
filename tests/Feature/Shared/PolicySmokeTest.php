<?php

namespace Tests\Feature\Shared;

use App\Models\Note;
use App\Models\NoteImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicySmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_manage_teacher_and_student_records_but_not_other_super_admins(): void
    {
        $admin = $this->makeUser('Admin', 'admin-one', 'super_admin');
        $teacher = $this->makeUser('Teacher', 'teacher-one', 'teacher');
        $student = $this->makeUser('Student', 'student-one', 'student');
        $otherAdmin = $this->makeUser('Other Admin', 'admin-two', 'super_admin');

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $teacher));
        $this->assertTrue(Gate::forUser($admin)->allows('resetPassword', $student));
        $this->assertFalse(Gate::forUser($admin)->allows('delete', $otherAdmin));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $student));
    }

    public function test_teacher_policy_matches_the_contract(): void
    {
        $teacher = $this->makeUser('Teacher One', 'teacher-one', 'teacher');
        $otherTeacher = $this->makeUser('Teacher Two', 'teacher-two', 'teacher');
        $student = $this->makeUser('Student One', 'student-one', 'student');

        $ownNote = Note::factory()->forStudent($student)->authoredBy($teacher)->create();
        $otherTeacherNote = Note::factory()->forStudent($student)->authoredBy($otherTeacher)->create();
        $studentNote = Note::factory()->forStudent($student)->authoredBy($student)->create();
        $ownImage = NoteImage::factory()->forNote($ownNote)->create();

        $this->assertTrue(Gate::forUser($teacher)->allows('view', $ownNote));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $studentNote));
        $this->assertTrue(Gate::forUser($teacher)->allows('create', [Note::class, $student]));
        $this->assertTrue(Gate::forUser($teacher)->allows('update', $ownNote));
        $this->assertTrue(Gate::forUser($teacher)->allows('delete', $ownNote));
        $this->assertTrue(Gate::forUser($teacher)->allows('delete', $ownImage));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $otherTeacherNote));
        $this->assertFalse(Gate::forUser($teacher)->allows('update', $studentNote));
    }

    public function test_student_policy_matches_the_contract(): void
    {
        $student = $this->makeUser('Student One', 'student-one', 'student');
        $otherStudent = $this->makeUser('Student Two', 'student-two', 'student');
        $teacher = $this->makeUser('Teacher One', 'teacher-one', 'teacher');
        $admin = $this->makeUser('Admin One', 'admin-one', 'super_admin');

        $teacherNote = Note::factory()->forStudent($student)->authoredBy($teacher)->create();
        $adminNote = Note::factory()->forStudent($student)->authoredBy($admin)->create();
        $ownNote = Note::factory()->forStudent($student)->authoredBy($student)->create();
        $otherStudentNote = Note::factory()->forStudent($otherStudent)->authoredBy($otherStudent)->create();
        $teacherImage = NoteImage::factory()->forNote($teacherNote)->create();
        $ownImage = NoteImage::factory()->forNote($ownNote)->create();

        $this->assertTrue(Gate::forUser($student)->allows('view', $teacherNote));
        $this->assertTrue(Gate::forUser($student)->allows('view', $adminNote));
        $this->assertTrue(Gate::forUser($student)->allows('view', $ownNote));
        $this->assertTrue(Gate::forUser($student)->allows('create', [Note::class, $student]));
        $this->assertTrue(Gate::forUser($student)->allows('update', $ownNote));
        $this->assertTrue(Gate::forUser($student)->allows('delete', $ownImage));
        $this->assertFalse(Gate::forUser($student)->allows('view', $otherStudentNote));
        $this->assertFalse(Gate::forUser($student)->allows('update', $teacherNote));
        $this->assertFalse(Gate::forUser($student)->allows('delete', $teacherImage));
    }

    private function makeUser(string $name, string $username, string $role): User
    {
        return match ($role) {
            'super_admin' => $this->createSuperAdmin(['name' => $name, 'username' => $username]),
            'student' => $this->createStudent(['name' => $name, 'username' => $username]),
            default => $this->createTeacher(['name' => $name, 'username' => $username]),
        };
    }
}
