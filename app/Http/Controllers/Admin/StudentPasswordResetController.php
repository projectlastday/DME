<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class StudentPasswordResetController extends Controller
{
    public function __invoke(ResetUserPasswordRequest $request, User $student): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole(User::ROLE_SUPER_ADMIN), 403);
        abort_unless($student->hasRole(User::ROLE_STUDENT), 404);

        $student->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()
            ->route('admin.students.edit', $student)
            ->with('status', 'Kata sandi murid berhasil diatur ulang.');
    }
}
