<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\NoteImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NoteImage>
 */
class NoteImageFactory extends Factory
{
    protected $model = NoteImage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'note_id' => Note::factory(),
            'disk' => 'private',
            'path' => 'notes/'.fake()->uuid().'.jpg',
            'original_filename' => fake()->lexify('image-????').'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => fake()->numberBetween(25_000, 3_000_000),
            'sort_order' => 1,
        ];
    }

    public function forNote(Note $note, int $sortOrder = 1): static
    {
        return $this->state(fn () => [
            'note_id' => $note->getKey(),
            'sort_order' => $sortOrder,
        ]);
    }
}
