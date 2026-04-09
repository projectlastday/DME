<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => fn () => $this->createUser('student')->getKey(),
            'author_id' => fn () => $this->createUser('teacher')->getKey(),
            'author_name_snapshot' => fn (array $attributes) => $this->resolveUser($attributes['author_id'])?->name ?? 'Deleted Teacher',
            'author_role_snapshot' => fn (array $attributes) => $this->resolveUser($attributes['author_id'])?->role ?? 'teacher',
            'body' => fake()->paragraph(),
            'note_date' => fake()->dateTimeBetween('-1 month')->format('Y-m-d'),
        ];
    }

    public function forStudent(User $student): static
    {
        return $this->state(fn () => [
            'student_id' => $student->getKey(),
        ]);
    }

    public function authoredBy(User $author): static
    {
        return $this->state(fn () => [
            'author_id' => $author->getKey(),
            'author_name_snapshot' => $author->name,
            'author_role_snapshot' => $author->role,
        ]);
    }

    public function withoutAuthor(
        string $authorNameSnapshot = 'Deleted Teacher',
        string $authorRoleSnapshot = 'teacher',
    ): static {
        return $this->state(fn () => [
            'author_id' => null,
            'author_name_snapshot' => $authorNameSnapshot,
            'author_role_snapshot' => $authorRoleSnapshot,
        ]);
    }

    private function createUser(string $role): User
    {
        $attributes = [
            'nama' => fake()->name(),
            'id_role' => Role::idForName($role),
            'password' => Hash::make('password'),
        ];

        $id = DB::table('user')->insertGetId($attributes);

        return User::query()->findOrFail($id);
    }

    private function resolveUser(?int $userId): ?User
    {
        if ($userId === null) {
            return null;
        }

        return User::query()->find($userId);
    }
}
