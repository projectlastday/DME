<?php

namespace Tests\Feature\Shared;

use App\Http\Requests\Shared\UpsertNoteRequest;
use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/_test/note-upsert-validation', function (UpsertNoteRequest $request) {
            $payload = $request->validatedPayload();

            return response()->json([
                'body' => $payload['body'],
                'note_date' => $payload['note_date'],
                'retained_image_ids' => $payload['retained_image_ids'],
                'uploaded_image_count' => count($payload['uploaded_images']),
            ]);
        });
    }

    public function test_note_only_payload_passes_validation(): void
    {
        $teacher = $this->seedActor('teacher');

        $response = $this
            ->actingAs($teacher)
            ->post('/_test/note-upsert-validation', [
                'body' => '  Trimmed body  ',
                'note_date' => '2026-03-31',
            ]);

        $response->assertOk()
            ->assertJsonPath('body', 'Trimmed body')
            ->assertJsonPath('note_date', '2026-03-31')
            ->assertJsonPath('uploaded_image_count', 0);
    }

    public function test_accepts_up_to_six_images(): void
    {
        $teacher = $this->seedActor('teacher');

        $response = $this
            ->actingAs($teacher)
            ->post('/_test/note-upsert-validation', [
                'images' => $this->fakeImages(6),
            ]);

        $response->assertOk()
            ->assertJsonPath('uploaded_image_count', 6);
    }

    public function test_rejects_seventh_image(): void
    {
        $teacher = $this->seedActor('teacher');

        $response = $this
            ->from('/_test/form')
            ->actingAs($teacher)
            ->post('/_test/note-upsert-validation', [
                'images' => $this->fakeImages(7),
            ]);

        $response->assertRedirect('/_test/form');
        $response->assertSessionHasErrors();
    }

    public function test_rejects_unsupported_mime_type(): void
    {
        $teacher = $this->seedActor('teacher');

        $response = $this
            ->from('/_test/form')
            ->actingAs($teacher)
            ->post('/_test/note-upsert-validation', [
                'images' => [
                    UploadedFile::fake()->create('bad.pdf', 10, 'application/pdf'),
                ],
            ]);

        $response->assertRedirect('/_test/form');
        $response->assertSessionHasErrors();
    }

    public function test_rejects_oversized_file(): void
    {
        $teacher = $this->seedActor('teacher');

        $response = $this
            ->from('/_test/form')
            ->actingAs($teacher)
            ->post('/_test/note-upsert-validation', [
                'images' => [
                    UploadedFile::fake()->image('too-large.png')->size(NoteImageStorage::maxFileKilobytes() + 1),
                ],
            ]);

        $response->assertRedirect('/_test/form');
        $response->assertSessionHasErrors();
    }

    private function fakeImages(int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $index): UploadedFile => UploadedFile::fake()->image("note-{$index}.png")->size(100))
            ->all();
    }

    private function seedActor(string $role): User
    {
        return match ($role) {
            'super_admin' => $this->createSuperAdmin(['name' => 'Super Admin User']),
            'student' => $this->createStudent(['name' => 'Student User']),
            default => $this->createTeacher(['name' => 'Teacher User']),
        };
    }
}
