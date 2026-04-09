<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class TeacherPasswordResetController extends Controller
{
    public function __invoke(ResetUserPasswordRequest $request, User $teacher): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole(User::ROLE_SUPER_ADMIN), 403);
        abort_unless($teacher->hasRole(User::ROLE_TEACHER), 404);

        $teacher->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()
            ->route('admin.teachers.edit', $teacher)
            ->with('status', 'Kata sandi guru berhasil diatur ulang.');
    }
}
