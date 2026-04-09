<?php

namespace App\Services\Notes;

use App\Support\HtmlSanitizer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NoteWriter
{
    public function __construct(
        private readonly NoteImageStorage $noteImageStorage,
    ) {
    }

    public function upsert(array $attributes): array
    {
        $noteId = $this->nullableInt($attributes['note_id'] ?? null);
        $studentId = $this->requiredInt($attributes['student_id'] ?? null, 'student_id');
        $actor = $attributes['actor'] ?? null;
        $body = $this->normalizeBody($attributes['body'] ?? null);
        $noteDate = $attributes['note_date'] ?? null;
        $uploadedImages = $attributes['uploaded_images'] ?? [];
        $retainedImageIds = collect($attributes['retained_image_ids'] ?? [])
            ->map(fn ($value): int => (int) $value)
            ->values();

        if (! is_object($actor)) {
            throw ValidationException::withMessages([
                'actor' => 'An authenticated actor is required.',
            ]);
        }

        return DB::transaction(function () use (
            $noteId,
            $studentId,
            $actor,
            $body,
            $noteDate,
            $uploadedImages,
            $retainedImageIds,
        ): array {
            $existingNote = $noteId === null
                ? null
                : DB::table('notes')->where('id', $noteId)->lockForUpdate()->first();

            if ($noteId !== null && $existingNote === null) {
                abort(404);
            }

            if ($existingNote !== null && (int) $existingNote->student_id !== $studentId) {
                throw ValidationException::withMessages([
                    'student_id' => 'The note does not belong to the provided student.',
                ]);
            }

            $existingImages = collect();

            if ($existingNote !== null) {
                $existingImages = DB::table('note_images')
                    ->where('note_id', $noteId)
                    ->orderBy('sort_order')
                    ->lockForUpdate()
                    ->get();

                $invalidRetainedIds = $retainedImageIds
                    ->diff($existingImages->pluck('id')->map(fn ($value): int => (int) $value))
                    ->values();

                if ($invalidRetainedIds->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'retained_image_ids' => 'One or more retained images do not belong to this note.',
                    ]);
                }
            }

            $retainedImages = $existingImages
                ->filter(fn (object $image): bool => $retainedImageIds->contains((int) $image->id))
                ->sortBy('sort_order')
                ->values();

            $finalImageCount = $retainedImages->count() + count(is_countable($uploadedImages) ? $uploadedImages : iterator_to_array($uploadedImages));

            if ($finalImageCount > NoteImageStorage::maxImagesPerNote()) {
                throw ValidationException::withMessages([
                    'images' => 'A note may have at most 6 images.',
                ]);
            }

            if ($body === null && $finalImageCount === 0) {
                throw ValidationException::withMessages([
                    'body' => 'A note must include text or at least one image.',
                ]);
            }

            $timestamp = now();

            if ($existingNote === null) {
                $noteId = (int) DB::table('notes')->insertGetId([
                    'student_id' => $studentId,
                    'author_id' => $this->requiredInt(data_get($actor, 'id'), 'actor.id'),
                    'author_name_snapshot' => (string) data_get($actor, 'name', ''),
                    'author_role_snapshot' => (string) data_get($actor, 'role', ''),
                    'body' => $body,
                    'note_date' => $this->normalizeNoteDate($noteDate),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            } else {
                DB::table('notes')
                    ->where('id', $noteId)
                    ->update([
                        'body' => $body,
                        'updated_at' => $timestamp,
                    ]);
            }

            $imagesToDelete = $existingImages
                ->reject(fn (object $image): bool => $retainedImageIds->contains((int) $image->id))
                ->values();

            if ($imagesToDelete->isNotEmpty()) {
                DB::table('note_images')->whereIn('id', $imagesToDelete->pluck('id'))->delete();
                $this->noteImageStorage->deleteStoredImages($imagesToDelete);
            }

            foreach ($retainedImages->values() as $index => $image) {
                DB::table('note_images')
                    ->where('id', $image->id)
                    ->update([
                        'sort_order' => $index + 1,
                        'updated_at' => $timestamp,
                    ]);
            }

            $storedUploads = $this->noteImageStorage
                ->storeUploadedImages($uploadedImages, $noteId, $retainedImages->count() + 1);

            if ($storedUploads->isNotEmpty()) {
                DB::table('note_images')->insert(
                    $storedUploads
                        ->map(fn (array $image): array => $image + [
                            'note_id' => $noteId,
                            'created_at' => $timestamp,
                            'updated_at' => $timestamp,
                        ])
                        ->all()
                );
            }

            $note = DB::table('notes')->where('id', $noteId)->first();
            $images = DB::table('note_images')
                ->where('note_id', $noteId)
                ->orderBy('sort_order')
                ->get();

            return [
                'note' => [
                    'id' => (int) $note->id,
                    'student_id' => (int) $note->student_id,
                    'author_id' => $note->author_id === null ? null : (int) $note->author_id,
                    'author_name_snapshot' => (string) $note->author_name_snapshot,
                    'author_role_snapshot' => (string) $note->author_role_snapshot,
                    'body' => $note->body,
                    'note_date' => (string) $note->note_date,
                    'created_at' => $note->created_at,
                    'updated_at' => $note->updated_at,
                ],
                'images' => $images->map(fn (object $image): array => [
                    'id' => (int) $image->id,
                    'note_id' => (int) $image->note_id,
                    'sort_order' => (int) $image->sort_order,
                    'mime_type' => (string) $image->mime_type,
                    'size_bytes' => (int) $image->size_bytes,
                    'display_url' => $this->noteImageStorage->displayUrl((int) $image->id),
                ])->values(),
            ];
        });
    }

    private function normalizeBody(mixed $body): ?string
    {
        if (! is_string($body)) {
            return null;
        }

        $trimmed = trim($body);

        if ($trimmed === '') {
            return null;
        }

        // Sanitize HTML to prevent XSS while allowing rich-text formatting.
        return HtmlSanitizer::sanitize($trimmed);
    }

    private function normalizeNoteDate(mixed $noteDate): string
    {
        if (! is_string($noteDate) || trim($noteDate) === '') {
            return Carbon::today()->toDateString();
        }

        return Carbon::parse($noteDate)->toDateString();
    }

    private function requiredInt(mixed $value, string $key): int
    {
        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $key => 'A numeric value is required.',
            ]);
        }

        return (int) $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
