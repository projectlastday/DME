<?php

namespace App\Services\Notes;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NoteQueryService
{
    public function teacherStudentList(?string $search = null): Collection
    {
        $query = DB::table('user')
            ->join('role', 'role.id_role', '=', 'user.id_role')
            ->select([
                'user.id_user as id',
                'user.nama as name',
                'role.nama as role',
            ])
            ->where('role.nama', User::ROLE_STUDENT)
            ->orderBy('user.nama')
            ->orderBy('user.id_user');

        $search = $this->normalizeSearch($search);

        if ($search !== null) {
            $query->where('user.nama', 'like', "%{$search}%");
        }

        return $query->get()->map(fn (object $student): array => (array) $student);
    }

    public function teacherStudentDetail(int $studentId): array
    {
        $student = $this->studentLookupQuery()
            ->where('user.id_user', $studentId)
            ->where('role.nama', User::ROLE_STUDENT)
            ->first();

        abort_unless($student !== null, 404);

        return [
            'student' => (array) $student,
            'note_groups' => $this->groupNotesForStudent($studentId),
        ];
    }

    public function studentNotes(int $studentId, string $tab = 'teacher'): array
    {
        $activeTab = $tab === 'mine' ? 'mine' : 'teacher';

        $student = $this->studentLookupQuery()
            ->where('user.id_user', $studentId)
            ->where('role.nama', User::ROLE_STUDENT)
            ->first();

        abort_unless($student !== null, 404);

        $notes = $this->baseStudentNotesQuery($studentId)
            ->when($activeTab === 'teacher', function ($query): void {
                $query->whereIn('notes.author_role_snapshot', [User::ROLE_TEACHER, User::ROLE_SUPER_ADMIN, 'teacher']);
            })
            ->when($activeTab === 'mine', function ($query) use ($studentId): void {
                $query->where('notes.author_id', $studentId);
            })
            ->get();

        return [
            'student' => (array) $student,
            'active_tab' => $activeTab,
            'note_groups' => $this->groupHydratedNotes($notes),
        ];
    }

    public function noteForEditing(int $noteId, object $actor): array
    {
        $note = $this->hydrateNotesQuery()
            ->where('notes.id', $noteId)
            ->get()
            ->first();

        abort_unless($note !== null, 404);

        if (! $this->canEdit((array) $note, $actor)) {
            throw new AuthorizationException();
        }

        return $this->mapHydratedNoteGroup(collect([$note]))->first();
    }

    private function groupNotesForStudent(int $studentId): Collection
    {
        return $this->groupHydratedNotes(
            $this->baseStudentNotesQuery($studentId)
                ->when($this->currentTeacherId() !== null, function ($query): void {
                    $teacherId = $this->currentTeacherId();

                    $query->where(function ($builder) use ($teacherId): void {
                        $builder
                            ->whereNotIn('notes.author_role_snapshot', [User::ROLE_TEACHER, 'teacher'])
                            ->orWhere('notes.author_id', $teacherId);
                    });
                })
                ->get()
        );
    }

    private function baseStudentNotesQuery(int $studentId)
    {
        return $this->hydrateNotesQuery()
            ->where('notes.student_id', $studentId);
    }

    private function hydrateNotesQuery()
    {
        return DB::table('notes')
            ->leftJoin('note_images', 'note_images.note_id', '=', 'notes.id')
            ->select([
                'notes.id',
                'notes.student_id',
                'notes.author_id',
                'notes.author_name_snapshot',
                'notes.author_role_snapshot',
                'notes.body',
                'notes.note_date',
                'notes.created_at',
                'notes.updated_at',
                'note_images.id as image_id',
                'note_images.note_id as image_note_id',
                'note_images.sort_order as image_sort_order',
                'note_images.mime_type as image_mime_type',
                'note_images.size_bytes as image_size_bytes',
            ])
            ->orderByDesc('notes.note_date')
            ->orderByDesc('notes.created_at')
            ->orderBy('note_images.sort_order');
    }

    private function groupHydratedNotes(Collection $rows): Collection
    {
        return $this->mapHydratedNoteGroup($rows)
            ->groupBy('note_date')
            ->map(function (Collection $notes, string $noteDate): array {
                return [
                    'note_date' => $noteDate,
                    'notes' => $notes->values(),
                ];
            })
            ->sortByDesc('note_date')
            ->values();
    }

    private function mapHydratedNoteGroup(Collection $rows): Collection
    {
        return $rows
            ->groupBy('id')
            ->map(function (Collection $group): array {
                $first = (array) $group->first();
                $images = $group
                    ->filter(fn (object $row): bool => $row->image_id !== null)
                    ->map(fn (object $row): array => [
                        'id' => (int) $row->image_id,
                        'note_id' => (int) $row->image_note_id,
                        'sort_order' => (int) $row->image_sort_order,
                        'mime_type' => (string) $row->image_mime_type,
                        'size_bytes' => (int) $row->image_size_bytes,
                        'display_url' => $this->displayUrl((int) $row->image_id),
                    ])
                    ->sortBy('sort_order')
                    ->values();

                return [
                    'id' => (int) $first['id'],
                    'student_id' => (int) $first['student_id'],
                    'author_id' => $first['author_id'] === null ? null : (int) $first['author_id'],
                    'author_name_snapshot' => (string) $first['author_name_snapshot'],
                    'author_role_snapshot' => (string) $first['author_role_snapshot'],
                    'body' => $first['body'],
                    'note_date' => (string) $first['note_date'],
                    'created_at' => $first['created_at'],
                    'updated_at' => $first['updated_at'],
                    'images' => $images,
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    private function canEdit(array $note, object $actor): bool
    {
        $role = (string) data_get($actor, 'role');
        $actorId = (int) data_get($actor, 'id');

        return match ($role) {
            User::ROLE_SUPER_ADMIN => true,
            User::ROLE_TEACHER => User::roleMatches($note['author_role_snapshot'], User::ROLE_TEACHER) && (int) $note['author_id'] === $actorId,
            User::ROLE_STUDENT => (int) $note['student_id'] === $actorId && (int) $note['author_id'] === $actorId,
            default => false,
        };
    }

    private function displayUrl(int $noteImageId): string
    {
        return "/note-images/{$noteImageId}";
    }

    private function normalizeSearch(?string $search): ?string
    {
        if (! is_string($search)) {
            return null;
        }

        $trimmed = trim($search);

        return $trimmed === '' ? null : $trimmed;
    }

    private function currentTeacherId(): ?int
    {
        $user = auth()->user();

        if (! User::roleMatches($user?->getAttribute('role'), User::ROLE_TEACHER)) {
            return null;
        }

        return (int) $user->getKey();
    }

    private function studentLookupQuery()
    {
        return DB::table('user')
            ->join('role', 'role.id_role', '=', 'user.id_role')
            ->select([
                'user.id_user as id',
                'user.nama as name',
                'role.nama as role',
            ]);
    }
}
