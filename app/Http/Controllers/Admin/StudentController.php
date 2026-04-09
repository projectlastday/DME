<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpsertStudentRequest;
use App\Models\Note;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class StudentController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $search = trim((string) request()->string('search'));

        return view('admin.students.index', [
            'search' => $search,
            'students' => User::query()
                ->roleNamed(User::ROLE_STUDENT)
                ->when($search !== '', fn ($query) => $query->where('nama', 'like', "%{$search}%"))
                ->orderBy('nama')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        $this->authorizeAdmin();

        return view('admin.students.create');
    }

    public function show(User $student): View
    {
        $this->authorizeAdmin();
        $this->ensureStudent($student);

        return view('admin.students.show', [
            'student' => $student,
        ]);
    }

    public function store(UpsertStudentRequest $request): RedirectResponse
    {
        $this->authorizeAdmin();

        $validated = $request->validated();

        User::query()->create([
            'nama' => $validated['name'],
            'id_role' => Role::idForName(User::ROLE_STUDENT),
            'password' => $validated['password'],
        ]);

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Murid berhasil ditambahkan.');
    }

    public function edit(User $student): View
    {
        $this->authorizeAdmin();
        $this->ensureStudent($student);

        return view('admin.students.edit', [
            'student' => $student,
        ]);
    }

    public function update(UpsertStudentRequest $request, User $student): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureStudent($student);

        $student->update([
            'nama' => $request->validated()['name'],
        ]);

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('status', 'Data murid berhasil diperbarui.');
    }

    public function destroy(User $student): RedirectResponse
    {
        $this->authorizeAdmin();
        $this->ensureStudent($student);

        Note::query()
            ->where('student_id', $student->getKey())
            ->delete();

        $student->delete();

        return redirect()
            ->route('admin.students.index')
            ->with('status', 'Akun murid telah dihapus.');
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->role === User::ROLE_STUDENT, 404);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->role === User::ROLE_SUPER_ADMIN, 403);
    }
}
