<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function index(): RedirectResponse
    {
        $this->ensureTeacher();

        return redirect()->route('teacher.students.index');
    }

    private function ensureTeacher(): void
    {
        abort_unless(request()->user()?->hasRole(User::ROLE_TEACHER), 403);
    }
}
