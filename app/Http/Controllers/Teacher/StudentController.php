<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpsertStudentRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use LogicException;

class StudentController extends Controller
{
    public function index(): View
    {
        $this->ensureTeacher();

        $search = (string) request()->string('search')->trim();

        $payload = $this->invoke(
            $this->queryService(),
            ['teacherStudentList', 'getTeacherStudentList', 'forTeacherStudentList', 'studentsForTeacher'],
            [$search]
        );

        $students = data_get($payload, 'students', $payload);

        return view('teacher.students.index', [
            'search' => $search,
            'students' => is_iterable($students) ? $students : [],
            'totalStudents' => (int) data_get($payload, 'total', is_countable($students) ? count($students) : 0),
        ]);
    }

    public function store(UpsertStudentRequest $request): RedirectResponse
    {
        $this->ensureTeacher();

        $validated = $request->validated();

        $student = User::query()->create([
            'nama' => $validated['name'],
            'id_role' => Role::idForName(User::ROLE_STUDENT),
            'password' => (string) str()->random(16),
        ]);

        return redirect()
            ->route('teacher.students.show', $student)
            ->with('status', 'Murid berhasil ditambahkan.');
    }

    public function show(User $student): View
    {
        $this->ensureTeacher();
        $this->ensureStudent($student);

        $payload = $this->invoke(
            $this->queryService(),
            ['teacherStudentDetail', 'getTeacherStudentDetail', 'forTeacherStudentDetail', 'studentDetailForTeacher'],
            [$student->getKey()]
        );

        return view('teacher.students.show', [
            'student' => data_get($payload, 'student', $student),
            'noteGroups' => $this->normalizeNoteGroups(
                data_get($payload, 'note_groups', data_get($payload, 'grouped_notes', data_get($payload, 'noteGroups', [])))
            ),
            'notes' => $this->normalizeNotes(data_get($payload, 'notes', [])),
        ]);
    }

    public function update(UpsertStudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureTeacher();
        $this->ensureStudent($student);

        $student->update([
            'nama' => $request->validated()['name'],
        ]);

        return redirect()
            ->route('teacher.students.show', $student)
            ->with('status', 'Nama murid berhasil diperbarui.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->ensureTeacher();
        $this->ensureStudent($student);

        $student->delete();

        return redirect()
            ->route('teacher.students.index')
            ->with('status', 'Murid berhasil dihapus.');
    }

    private function queryService(): mixed
    {
        return app('App\Services\Notes\NoteQueryService');
    }

    private function ensureTeacher(): void
    {
        abort_unless(request()->user()?->hasRole(User::ROLE_TEACHER), 403);
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function normalizeNoteGroups(mixed $noteGroups): array
    {
        if ($noteGroups instanceof Collection) {
            $noteGroups = $noteGroups->all();
        }

        if (! is_array($noteGroups)) {
            return [];
        }

        if (array_is_list($noteGroups)) {
            return collect($noteGroups)
                ->mapWithKeys(fn (mixed $group): array => [
                    (string) data_get($group, 'note_date', '') => $this->normalizeNotes(data_get($group, 'notes', [])),
                ])
                ->filter(fn (array $notes, string $noteDate): bool => $noteDate !== '')
                ->all();
        }

        return collect($noteGroups)
            ->map(fn (mixed $notes): array => $this->normalizeNotes($notes))
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeNotes(mixed $notes): array
    {
        if ($notes instanceof Collection) {
            $notes = $notes->all();
        }

        if (! is_array($notes)) {
            return [];
        }

        return array_values(array_map(fn (mixed $note): array => $this->normalizeNote($note), $notes));
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeNote(mixed $note): array
    {
        if ($note instanceof Collection) {
            $note = $note->all();
        }

        if (! is_array($note)) {
            return [];
        }

        $images = data_get($note, 'images', []);

        if ($images instanceof Collection) {
            $images = $images->all();
        }

        $images = is_array($images)
            ? array_values(array_map(function (mixed $image): array {
                $image = $image instanceof Collection ? $image->all() : (array) $image;

                return $image + [
                    'url' => data_get($image, 'url', data_get($image, 'display_url')),
                ];
            }, $images))
            : [];

        return $note + [
            'images' => $images,
        ];
    }

    private function invoke(mixed $service, array $methods, array $arguments = []): mixed
    {
        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                return $service->{$method}(...$arguments);
            }
        }

        throw new LogicException('Teacher note query contract is unavailable.');
    }
}
