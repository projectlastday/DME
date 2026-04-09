<?php

namespace Tests\Feature\Student;

use App\Models\Note;
use App\Models\NoteImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentNoteCrudTest extends TestCase
{
    public function test_student_can_create_edit_delete_own_notes_and_delete_own_note_images(): void
    {
        Storage::fake('note-images-private');

        $student = $this->createStudent([
            'name' => 'Kai Student',
            'username' => 'kai-student',
        ]);

        $this->actingAs($student)
            ->post(route('student.notes.store'), [
                'body' => 'Created by student',
                'note_date' => '2026-03-31',
                'images' => [
                    UploadedFile::fake()->image('created-note.jpg')->size(200),
                ],
            ])
            ->assertRedirect(route('student.notes.index', ['tab' => 'mine']));

        $note = Note::query()->latest('id')->firstOrFail();
        $noteImage = NoteImage::query()->where('note_id', $note->getKey())->firstOrFail();

        $this->assertSame($student->getKey(), $note->student_id);
        $this->assertSame($student->getKey(), $note->author_id);
        $this->assertSame('student', $note->author_role_snapshot);
        Storage::disk('note-images-private')->assertExists($noteImage->path);

        $this->actingAs($student)
            ->get(route('student.notes.edit', $note))
            ->assertOk()
            ->assertSee('Edit Note')
            ->assertSee('Created by student');

        $this->actingAs($student)
            ->delete(route('student.note-images.destroy', $noteImage))
            ->assertRedirect(route('student.notes.edit', $note));

        $this->assertDatabaseMissing('note_images', ['id' => $noteImage->getKey()]);
        Storage::disk('note-images-private')->assertMissing($noteImage->path);

        $this->actingAs($student)
            ->put(route('student.notes.update', $note), [
                'body' => 'Updated by student',
                'note_date' => '2026-04-01',
            ])
            ->assertRedirect(route('student.notes.index', ['tab' => 'mine']));

        $this->assertDatabaseHas('notes', [
            'id' => $note->getKey(),
            'body' => 'Updated by student',
            'note_date' => '2026-03-31',
        ]);

        $this->actingAs($student)
            ->delete(route('student.notes.destroy', $note))
            ->assertRedirect(route('student.notes.index', ['tab' => 'mine']));

        $this->assertDatabaseMissing('notes', ['id' => $note->getKey()]);
    }
}
