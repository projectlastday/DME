<?php

namespace Tests\Feature\Shared;

use App\Models\User;
use App\Services\Notes\NoteQueryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NoteQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    private NoteQueryService $noteQueryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->noteQueryService = app(NoteQueryService::class);
    }

    public function test_teacher_student_list_supports_search(): void
    {
        $this->seedActor('student', 'Alice Student', 'alice-student');
        $this->seedActor('student', 'Bob Student', 'bob-student');
        $this->seedActor('teacher', 'Teacher One', 'teacher-one');

        $results = $this->noteQueryService->teacherStudentList('bob');

        $this->assertCount(1, $results);
        $this->assertSame('Bob Student', $results->first()['name']);
    }

    public function test_teacher_student_detail_groups_notes_and_orders_images(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');

        $olderNoteId = $this->insertNote($student->id, $teacher->id, 'Teacher One', 'teacher', 'Older', '2026-03-01', '2026-03-01 08:00:00');
        $newerNoteId = $this->insertNote($student->id, $teacher->id, 'Teacher One', 'teacher', 'Newer', '2026-03-02', '2026-03-02 09:00:00');

        $this->insertImage($newerNoteId, 2, 'newer-b.png');
        $this->insertImage($newerNoteId, 1, 'newer-a.png');

        $detail = $this->noteQueryService->teacherStudentDetail($student->id);

        $this->assertSame($student->id, $detail['student']['id']);
        $this->assertSame(['2026-03-02', '2026-03-01'], collect($detail['note_groups'])->pluck('note_date')->all());
        $this->assertSame('Newer', $detail['note_groups'][0]['notes'][0]['body']);
        $this->assertSame([1, 2], collect($detail['note_groups'][0]['notes'][0]['images'])->pluck('sort_order')->all());
    }

    public function test_teacher_student_detail_hides_notes_from_other_teachers(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $otherTeacher = $this->seedActor('teacher', 'Teacher Two', 'teacher-two');
        $admin = $this->seedActor('super_admin', 'Admin One', 'admin-one');

        $this->insertNote($student->id, $teacher->id, 'Teacher One', 'teacher', 'Teacher own note', '2026-03-02', '2026-03-02 09:00:00');
        $this->insertNote($student->id, $otherTeacher->id, 'Teacher Two', 'teacher', 'Teacher other note', '2026-03-02', '2026-03-02 10:00:00');
        $this->insertNote($student->id, $admin->id, 'Admin One', 'super_admin', 'Admin note', '2026-03-02', '2026-03-02 11:00:00');
        $this->insertNote($student->id, $student->id, 'Student One', 'student', 'Student note', '2026-03-02', '2026-03-02 12:00:00');

        $this->actingAs($teacher);

        $detail = $this->noteQueryService->teacherStudentDetail($student->id);
        $bodies = collect($detail['note_groups'])
            ->flatMap(fn (array $group) => collect($group['notes'])->pluck('body'))
            ->all();

        $this->assertContains('Teacher own note', $bodies);
        $this->assertContains('Admin note', $bodies);
        $this->assertContains('Student note', $bodies);
        $this->assertNotContains('Teacher other note', $bodies);
    }

    public function test_student_notes_returns_teacher_and_mine_tabs(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $admin = $this->seedActor('super_admin', 'Admin One', 'admin-one');

        $this->insertNote($student->id, $teacher->id, 'Teacher One', 'teacher', 'Teacher note', '2026-03-03', '2026-03-03 10:00:00');
        $this->insertNote($student->id, $admin->id, 'Admin One', 'super_admin', 'Admin note', '2026-03-02', '2026-03-02 10:00:00');
        $this->insertNote($student->id, $student->id, 'Student One', 'student', 'My note', '2026-03-01', '2026-03-01 10:00:00');

        $teacherTab = $this->noteQueryService->studentNotes($student->id, 'teacher');
        $mineTab = $this->noteQueryService->studentNotes($student->id, 'mine');

        $this->assertSame('teacher', $teacherTab['active_tab']);
        $this->assertSame(['Teacher note', 'Admin note'], collect($teacherTab['note_groups'])->flatMap(fn (array $group) => collect($group['notes'])->pluck('body'))->all());

        $this->assertSame('mine', $mineTab['active_tab']);
        $this->assertSame(['My note'], collect($mineTab['note_groups'])->flatMap(fn (array $group) => collect($group['notes'])->pluck('body'))->all());
    }

    public function test_note_for_editing_allows_owner_and_rejects_other_users(): void
    {
        $student = $this->seedActor('student', 'Student One', 'student-one');
        $teacher = $this->seedActor('teacher', 'Teacher One', 'teacher-one');
        $otherTeacher = $this->seedActor('teacher', 'Teacher Two', 'teacher-two');

        $teacherNoteId = $this->insertNote($student->id, $teacher->id, 'Teacher One', 'teacher', 'Teacher note', '2026-03-03', '2026-03-03 10:00:00');
        $studentNoteId = $this->insertNote($student->id, $student->id, 'Student One', 'student', 'Student note', '2026-03-04', '2026-03-04 10:00:00');

        $teacherEditable = $this->noteQueryService->noteForEditing($teacherNoteId, $teacher);
        $studentEditable = $this->noteQueryService->noteForEditing($studentNoteId, $student);

        $this->assertSame('Teacher note', $teacherEditable['body']);
        $this->assertSame('Student note', $studentEditable['body']);

        $this->expectException(AuthorizationException::class);
        $this->noteQueryService->noteForEditing($teacherNoteId, $otherTeacher);
    }

    private function seedActor(string $role, string $name, string $username): User
    {
        return match ($role) {
            'super_admin' => $this->createSuperAdmin(['name' => $name, 'username' => $username]),
            'student' => $this->createStudent(['name' => $name, 'username' => $username]),
            default => $this->createTeacher(['name' => $name, 'username' => $username]),
        };
    }

    private function insertNote(int $studentId, int $authorId, string $authorName, string $authorRole, string $body, string $noteDate, string $createdAt): int
    {
        return DB::table('notes')->insertGetId([
            'student_id' => $studentId,
            'author_id' => $authorId,
            'author_name_snapshot' => $authorName,
            'author_role_snapshot' => $authorRole,
            'body' => $body,
            'note_date' => $noteDate,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function insertImage(int $noteId, int $sortOrder, string $filename): void
    {
        DB::table('note_images')->insert([
            'note_id' => $noteId,
            'disk' => 'note-images-private',
            'path' => "note-images/note-{$noteId}/{$filename}",
            'original_filename' => $filename,
            'mime_type' => 'image/png',
            'size_bytes' => 123,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
