<?php

namespace Tests\Feature\Shared;

use App\Models\Note;
use App\Models\NoteImage;
use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CrossRoleIntegrationTest extends TestCase
{
    public function test_each_role_is_blocked_from_other_role_areas(): void
    {
        $admin = $this->createSuperAdmin(['username' => 'admin-boundary']);
        $teacher = $this->createTeacher(['username' => 'teacher-boundary']);
        $student = $this->createStudent(['username' => 'student-boundary']);

        $this->actingAs($admin)->get(route('teacher.dashboard'))->assertForbidden();
        $this->actingAs($admin)->get(route('student.dashboard'))->assertForbidden();

        $this->actingAs($teacher)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($teacher)->get(route('student.dashboard'))->assertForbidden();

        $this->actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($student)->get(route('teacher.dashboard'))->assertForbidden();
    }

    public function test_teacher_and_student_note_visibility_hold_together_end_to_end(): void
    {
        $student = $this->createStudent([
            'name' => 'Nadia Student',
            'username' => 'nadia-student-phase6',
        ]);
        $otherStudent = $this->createStudent([
            'name' => 'Other Student',
            'username' => 'other-student-phase6',
        ]);
        $teacher = $this->createTeacher([
            'name' => 'Teacher Ada',
            'username' => 'teacher-ada-phase6',
        ]);
        $otherTeacher = $this->createTeacher([
            'name' => 'Teacher Noor',
            'username' => 'teacher-noor-phase6',
        ]);
        $admin = $this->createSuperAdmin([
            'name' => 'Admin Lee',
            'username' => 'admin-lee-phase6',
        ]);

        Note::factory()->forStudent($student)->authoredBy($teacher)->create([
            'body' => 'Teacher note for Nadia',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($otherTeacher)->create([
            'body' => 'Other teacher note for Nadia',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($admin)->create([
            'body' => 'Admin note for Nadia',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($student)->authoredBy($student)->create([
            'body' => 'Nadia personal note',
            'note_date' => '2026-03-31',
        ]);
        Note::factory()->forStudent($otherStudent)->authoredBy($otherStudent)->create([
            'body' => 'Other student private note',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($teacher)
            ->get(route('teacher.students.show', $student))
            ->assertOk()
            ->assertSee('Teacher note for Nadia')
            ->assertDontSee('Other teacher note for Nadia')
            ->assertSee('Admin note for Nadia')
            ->assertSee('Nadia personal note')
            ->assertDontSee('Other student private note');

        $this->actingAs($student)
            ->get(route('student.notes.index'))
            ->assertOk()
            ->assertSee('Teacher note for Nadia')
            ->assertSee('Other teacher note for Nadia')
            ->assertSee('Admin note for Nadia')
            ->assertDontSee('Nadia personal note')
            ->assertDontSee('Other student private note');

        $this->actingAs($student)
            ->get(route('student.notes.index', ['tab' => 'mine']))
            ->assertOk()
            ->assertSee('Nadia personal note')
            ->assertDontSee('Teacher note for Nadia')
            ->assertDontSee('Admin note for Nadia')
            ->assertDontSee('Other student private note');
    }

    public function test_teacher_can_manage_only_own_note_end_to_end_with_real_services(): void
    {
        Storage::fake(NoteImageStorage::PRIVATE_DISK);

        $teacher = $this->createTeacher([
            'name' => 'Teacher Owner',
            'username' => 'teacher-owner-phase6',
        ]);
        $student = $this->createStudent([
            'name' => 'Student Owner',
            'username' => 'student-owner-phase6',
        ]);
        $otherTeacher = $this->createTeacher([
            'name' => 'Teacher Other',
            'username' => 'teacher-other-phase6',
        ]);

        $otherTeacherNote = Note::factory()->forStudent($student)->authoredBy($otherTeacher)->create([
            'body' => 'Other teacher protected note',
            'note_date' => '2026-03-30',
        ]);
        $studentNote = Note::factory()->forStudent($student)->authoredBy($student)->create([
            'body' => 'Student protected note',
            'note_date' => '2026-03-30',
        ]);

        $this->actingAs($teacher)
            ->post(route('teacher.notes.store', $student), [
                'body' => 'Teacher created note',
                'note_date' => '2026-03-31',
                'new_images' => [
                    UploadedFile::fake()->image('teacher-created.jpg')->size(200),
                ],
            ])
            ->assertRedirect(route('teacher.students.show', $student));

        $ownNote = Note::query()
            ->where('student_id', $student->getKey())
            ->where('author_id', $teacher->getKey())
            ->latest('id')
            ->firstOrFail();
        $ownImage = NoteImage::query()->where('note_id', $ownNote->getKey())->firstOrFail();

        Storage::disk(NoteImageStorage::PRIVATE_DISK)->assertExists($ownImage->path);

        $this->actingAs($teacher)
            ->get(route('teacher.notes.edit', $ownNote))
            ->assertOk()
            ->assertSee('Teacher created note');

        $this->actingAs($teacher)
            ->get(route('teacher.notes.edit', $otherTeacherNote))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('teacher.notes.edit', $studentNote))
            ->assertForbidden();

        $updateResponse = $this->actingAs($teacher)
            ->put(route('teacher.notes.update', $ownNote), [
                'body' => 'Teacher updated note',
                'note_date' => '2026-04-01',
                'student_id' => $student->getKey(),
                'retained_image_ids' => [$ownImage->getKey()],
            ]);

        $this->assertSame(302, $updateResponse->getStatusCode());

        $this->assertDatabaseHas('notes', [
            'id' => $ownNote->getKey(),
            'body' => 'Teacher updated note',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($teacher)
            ->put(route('teacher.notes.update', $otherTeacherNote), [
                'body' => 'Attempted update',
                'note_date' => '2026-04-01',
                'student_id' => $student->getKey(),
            ])
            ->assertForbidden();

        $this->actingAs($teacher)
            ->delete(route('teacher.note-images.destroy', $ownImage), [
                'student_id' => $student->getKey(),
            ])
            ->assertRedirect(route('teacher.students.show', $student));

        $this->assertDatabaseMissing('note_images', ['id' => $ownImage->getKey()]);
        Storage::disk(NoteImageStorage::PRIVATE_DISK)->assertMissing($ownImage->path);

        $this->actingAs($teacher)
            ->delete(route('teacher.notes.destroy', $ownNote))
            ->assertRedirect(route('teacher.students.show', $student));

        $this->assertDatabaseMissing('notes', ['id' => $ownNote->getKey()]);
        $this->assertDatabaseHas('notes', ['id' => $otherTeacherNote->getKey()]);
        $this->assertDatabaseHas('notes', ['id' => $studentNote->getKey()]);
    }

    public function test_private_image_authorization_holds_across_roles(): void
    {
        Storage::fake(NoteImageStorage::PRIVATE_DISK);

        $student = $this->createStudent(['username' => 'provider-student']);
        $otherStudent = $this->createStudent(['username' => 'provider-student-other']);
        $teacher = $this->createTeacher(['username' => 'provider-teacher']);
        $admin = $this->createSuperAdmin(['username' => 'provider-admin']);

        $note = Note::factory()->forStudent($student)->authoredBy($teacher)->create([
            'body' => 'Private image note',
            'note_date' => '2026-03-31',
        ]);
        $image = NoteImage::factory()->forNote($note)->create([
            'disk' => NoteImageStorage::PRIVATE_DISK,
            'path' => 'note-images/note-'.$note->getKey().'/private-image.jpg',
            'original_filename' => 'private-image.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        Storage::disk(NoteImageStorage::PRIVATE_DISK)->put($image->path, 'private-image-content');

        $this->actingAs($student)
            ->get(route('note-images.show', $image))
            ->assertOk();

        $this->actingAs($otherStudent)
            ->get(route('note-images.show', $image))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('note-images.show', $image))
            ->assertOk();

        $this->actingAs($admin)
            ->get(route('note-images.show', $image))
            ->assertOk();
    }
}
