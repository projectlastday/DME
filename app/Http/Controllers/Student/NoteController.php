<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpsertStudentNoteRequest;
use App\Models\Note;
use App\Services\Notes\NoteImageStorage;
use App\Services\Notes\NoteQueryService;
use App\Services\Notes\NoteWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class NoteController extends Controller
{
    public function index(Request $request, NoteQueryService $noteQueryService): View
    {
        $tab = (string) $request->query('tab', 'teacher');
        $payload = $noteQueryService->studentNotes($request->user()->getKey(), $tab);

        return view('student.notes.index', [
            'student' => data_get($payload, 'student', $request->user()),
            'noteGroups' => $this->normalizeNoteGroups(data_get($payload, 'note_groups', [])),
            'activeTab' => data_get($payload, 'active_tab', 'teacher'),
        ]);
    }

    public function store(UpsertStudentNoteRequest $request, NoteWriter $noteWriter): RedirectResponse
    {
        $noteWriter->upsert($request->validatedPayload() + [
            'student_id' => $request->user()->getKey(),
            'actor' => $request->user(),
        ]);

        return redirect()
            ->route('student.notes.index', ['tab' => 'mine'])
            ->with('status', 'Catatan berhasil ditambahkan.');
    }

    public function edit(Request $request, int $note, NoteQueryService $noteQueryService): View
    {
        try {
            $payload = $this->normalizeNote($noteQueryService->noteForEditing($note, $request->user()));
        } catch (AuthorizationException $exception) {
            abort(403, $exception->getMessage());
        }

        return view('student.notes.edit', [
            'note' => $payload,
        ]);
    }

    public function update(
        UpsertStudentNoteRequest $request,
        Note $note,
        NoteWriter $noteWriter,
    ): RedirectResponse {
        Gate::authorize('update', $note);

        $noteWriter->upsert($request->validatedPayload() + [
            'note_id' => $note->getKey(),
            'student_id' => $request->user()->getKey(),
            'actor' => $request->user(),
        ]);

        return redirect()
            ->route('student.notes.index', ['tab' => 'mine'])
            ->with('status', 'Catatan berhasil diperbarui.');
    }

    public function destroy(Note $note, NoteImageStorage $noteImageStorage): RedirectResponse
    {
        Gate::authorize('delete', $note);

        $noteImageStorage->deleteStoredImages($note->noteImages()->get());
        $note->delete();

        return redirect()
            ->route('student.notes.index', ['tab' => 'mine'])
            ->with('status', 'Catatan berhasil dihapus.');
    }

    /**
     * @param  mixed  $noteGroups
     * @return list<array{note_date: string, notes: array<int, mixed>}>
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
            return array_values(array_map(function (mixed $group): array {
                return [
                    'note_date' => (string) data_get($group, 'note_date', ''),
                    'notes' => $this->normalizeNotes(data_get($group, 'notes', [])),
                ];
            }, $noteGroups));
        }

        return collect($noteGroups)
            ->map(fn (mixed $notes, string $noteDate): array => [
                'note_date' => $noteDate,
                'notes' => $this->normalizeNotes($notes),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
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

        return $note + [
            'images' => is_array($images) ? array_values($images) : [],
        ];
    }
}
