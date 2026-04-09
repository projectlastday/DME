<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertTeacherRequest;
use App\Models\Note;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class TeacherController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $search = trim((string) request()->string('search'));

        return view('admin.teachers.index', [
            'search' => $search,
            'teachers' => User::query()
                ->roleNamed(User::ROLE_TEACHER)
                ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.teachers.create');
    }

    public function show(User $teacher): View
    {
        $this->authorizeAdmin();
        $this->ensureTeacher($teacher);

        return view('admin.teachers.show', [
            'teacher' => $teacher,
        ]);
    }

    public function store(UpsertTeacherRequest $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validated();

        User::query()->create([
            'nama' => $validated['name'],
            'id_role' => Role::idForName(User::ROLE_TEACHER),
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Guru berhasil ditambahkan.');
    }

    public function edit(User $teacher): View
    {
        $this->authorizeAdmin();
        $this->ensureTeacher($teacher);

        return view('admin.teachers.edit', [
            'teacher' => $teacher,
        ]);
    }

    public function update(UpsertTeacherRequest $request, User $teacher): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureTeacher($teacher);

        $teacher->update([
            'nama' => $request->validated()['name'],
        ]);

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('status', 'Data guru berhasil diperbarui.');
    }

    public function destroy(User $teacher): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureTeacher($teacher);

        Note::query()
            ->where('author_id', $teacher->getKey())
            ->delete();

        $teacher->delete();

        return redirect()
            ->route('admin.teachers.index')
            ->with('status', 'Akun guru telah dihapus.');
    }

    private function ensureTeacher(User $teacher): void
    {
        abort_unless($teacher->role === User::ROLE_TEACHER, 404);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_SUPER_ADMIN, 403);
    }
}
