<?php

namespace Tests\Feature\Shared;

use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use App\Services\Notes\NoteWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NoteWriterTest extends TestCase
{
    use RefreshDatabase;

    private NoteWriter $noteWriter;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(NoteImageStorage::PRIVATE_DISK);
        $this->noteWriter = app(NoteWriter::class);
    }

    public function test_creates_note_only_note_with_author_snapshots(): void
    {
        $teacher = $this->seedActor('teacher', 'Teacher One');
        $student = $this->seedActor('student', 'Student One');

        $result = $this->noteWriter->upsert([
            'student_id' => $student->id,
            'actor' => $teacher,
            'body' => 'First note',
            'note_date' => '2026-03-20',
        ]);

        $this->assertSame('First note', $result['note']['body']);
        $this->assertSame('Teacher One', $result['note']['author_name_snapshot']);
        $this->assertSame('teacher', $result['note']['author_role_snapshot']);
        $this->assertSame('2026-03-20', $result['note']['note_date']);
        $this->assertCount(0, $result['images']);
    }

    public function test_creates_image_only_and_mixed_notes_on_private_storage(): void
    {
        $teacher = $this->seedActor('teacher', 'Teacher One');
        $student = $this->seedActor('student', 'Student One');

        $imageOnly = $this->noteWriter->upsert([
            'student_id' => $student->id,
            'actor' => $teacher,
            'body' => null,
            'uploaded_images' => [
                UploadedFile::fake()->image('image-only.png')->size(120),
            ],
        ]);

        $mixed = $this->noteWriter->upsert([
            'student_id' => $student->id,
            'actor' => $teacher,
            'body' => 'Mixed note',
            'uploaded_images' => [
                UploadedFile::fake()->image('mixed-a.png')->size(120),
                UploadedFile::fake()->image('mixed-b.png')->size(120),
            ],
        ]);

        $this->assertNull($imageOnly['note']['body']);
        $this->assertCount(1, $imageOnly['images']);
        $this->assertSame('/note-images/'.$imageOnly['images'][0]['id'], $imageOnly['images'][0]['display_url']);

        $this->assertSame('Mixed note', $mixed['note']['body']);
        $this->assertCount(2, $mixed['images']);

        $storedPaths = DB::table('note_images')->pluck('path')->all();

        foreach ($storedPaths as $path) {
            Storage::disk(NoteImageStorage::PRIVATE_DISK)->assertExists($path);
        }
    }

    public function test_updates_existing_note_preserves_note_date_and_reorders_images(): void
    {
        $teacher = $this->seedActor('teacher', 'Teacher One');
        $student = $this->seedActor('student', 'Student One');

        $created = $this->noteWriter->upsert([
            'student_id' => $student->id,
            'actor' => $teacher,
            'body' => 'Original body',
            'note_date' => '2026-03-01',
            'uploaded_images' => [
                UploadedFile::fake()->image('keep.png')->size(120),
                UploadedFile::fake()->image('remove.png')->size(120),
            ],
        ]);

        $removedPath = DB::table('note_images')
            ->where('id', $created['images'][1]['id'])
            ->value('path');

        $updated = $this->noteWriter->upsert([
            'note_id' => $created['note']['id'],
            'student_id' => $student->id,
            'actor' => $teacher,
            'body' => 'Updated body',
            'note_date' => '2026-03-31',
            'retained_image_ids' => [$created['images'][0]['id']],
            'uploaded_images' => [
                UploadedFile::fake()->image('new.png')->size(120),
            ],
        ]);

        $this->assertSame('2026-03-01', $updated['note']['note_date']);
        $this->assertSame('Updated body', $updated['note']['body']);
        $this->assertSame([1, 2], collect($updated['images'])->pluck('sort_order')->all());
        Storage::disk(NoteImageStorage::PRIVATE_DISK)->assertMissing($removedPath);
    }

    public function test_rejects_more_than_six_total_images(): void
    {
        $teacher = $this->seedActor('teacher', 'Teacher One');
        $student = $this->seedActor('student', 'Student One');

        $this->expectException(ValidationException::class);

        $this->noteWriter->upsert([
            'student_id' => $student->id,
            'actor' => $teacher,
            'uploaded_images' => collect(range(1, 7))
                ->map(fn (int $index): UploadedFile => UploadedFile::fake()->image("{$index}.png")->size(100))
                ->all(),
        ]);
    }

    private function seedActor(string $role, string $name): User
    {
        return match ($role) {
            'super_admin' => $this->createSuperAdmin(['name' => $name]),
            'student' => $this->createStudent(['name' => $name]),
            default => $this->createTeacher(['name' => $name]),
        };
    }
}
