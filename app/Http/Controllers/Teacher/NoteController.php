<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreTeacherNoteRequest;
use App\Http\Requests\Teacher\UpdateTeacherNoteRequest;
use App\Models\User;
use App\Models\Note;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use LogicException;

class NoteController extends Controller
{
    public function store(StoreTeacherNoteRequest $request, string $student): RedirectResponse
    {
        $this->ensureTeacher();

        $this->createOrUpsertNote($request, $student);

        return redirect()
            ->route('teacher.students.show', $student);
    }

    public function edit(Request $request, string $note): View
    {
        $this->ensureTeacher();

        $payload = $this->editableNotePayload($request, $note);
        $noteData = data_get($payload, 'note', $payload);
        
        return view('teacher.notes.edit', [
            'student' => data_get($payload, 'student'),
            'note' => $this->normalizeNote($noteData),
        ]);
    }

    public function update(UpdateTeacherNoteRequest $request, string $note): RedirectResponse
    {
        $this->ensureTeacher();

        $payload = $this->editableNotePayload($request, $note);
        $noteData = data_get($payload, 'note', $payload);
        $studentId = (string) data_get($noteData, 'student_id', data_get($payload, 'student.id'));

        $this->updateOrUpsertNote($request, $note, $studentId);

        return redirect()
            ->route('teacher.students.show', $studentId);
    }

    public function destroy(string $note, NoteImageStorage $noteImageStorage): RedirectResponse
    {
        $this->ensureTeacher();

        $payload = $this->editableNotePayload(request(), $note);
        $noteData = data_get($payload, 'note', $payload);
        $studentId = (string) data_get($noteData, 'student_id', data_get($payload, 'student.id'));

        $writer = $this->writer();

        if ($this->supportsAnyMethod($writer, ['delete', 'deleteNote', 'destroy'])) {
            $this->invoke(
                $writer,
                ['delete', 'deleteNote', 'destroy'],
                [$note, request()->user()->getKey()]
            );
        } else {
            $noteModel = Note::query()->with('noteImages')->findOrFail($note);
            $noteImageStorage->deleteStoredImages($noteModel->noteImages);
            $noteModel->delete();
        }

        return redirect()
            ->route('teacher.students.show', $studentId);
    }

    private function editableNotePayload(Request $request, string $note): mixed
    {
        $payload = $this->invoke(
            $this->queryService(),
            ['noteForEditing', 'noteEditData', 'getNoteEditData', 'noteForEdit', 'getNoteForEdit', 'hydrateNoteForEdit'],
            [$note, $request->user()]
        );

        $noteData = data_get($payload, 'note', $payload);

        abort_unless($this->ownsEditableTeacherNote($noteData), 403);

        return $payload;
    }

    private function createOrUpsertNote(StoreTeacherNoteRequest $request, string $student): void
    {
        $writer = $this->writer();

        if ($this->supportsAnyMethod($writer, ['createForStudent', 'storeForStudent', 'create'])) {
            $validated = $request->validated();
            $validated['images'] = $request->uploadedImages();

            $this->invoke(
                $writer,
                ['createForStudent', 'storeForStudent', 'create'],
                [$student, $validated, $request->user()->getKey()]
            );

            return;
        }

        $this->invoke(
            $writer,
            ['upsert'],
            [$request->validatedPayload() + [
                'student_id' => (int) $student,
                'actor' => $request->user(),
            ]]
        );
    }

    private function updateOrUpsertNote(UpdateTeacherNoteRequest $request, string $note, string $studentId): void
    {
        $writer = $this->writer();

        if ($this->supportsAnyMethod($writer, ['update', 'updateNote'])) {
            $validated = $request->validated();
            $validated['images'] = $request->uploadedImages();

            $this->invoke(
                $writer,
                ['update', 'updateNote'],
                [$note, $validated, $request->user()->getKey()]
            );

            return;
        }

        $payload = $request->validatedPayload() + [
            'note_id' => (int) $note,
            'student_id' => (int) $studentId,
            'actor' => $request->user(),
        ];

        $this->invoke($writer, ['upsert'], [$payload]);
    }

    private function ownsEditableTeacherNote(mixed $note): bool
    {
        $authorId = data_get($note, 'author_id');
        $authorRole = data_get($note, 'author_role_snapshot', data_get($note, 'author_role'));

        return (string) $authorId === (string) request()->user()->getKey()
            && User::roleMatches($authorRole, User::ROLE_TEACHER);
    }

    private function writer(): mixed
    {
        return app('App\Services\Notes\NoteWriter');
    }

    private function queryService(): mixed
    {
        return app('App\Services\Notes\NoteQueryService');
    }

    private function supportsAnyMethod(mixed $service, array $methods): bool
    {
        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                return true;
            }
        }

        return false;
    }

    private function ensureTeacher(): void
    {
        abort_unless(request()->user()?->hasRole(User::ROLE_TEACHER), 403);
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
                try {
                    return $service->{$method}(...$arguments);
                } catch (AuthorizationException $exception) {
                    abort(403, $exception->getMessage());
                } catch (\ArgumentCountError) {
                    continue;
                }
            }
        }

        throw new LogicException('Teacher note contract is unavailable.');
    }
}
