<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\RedirectResponse;

class NoteModerationController extends Controller
{
    public function __invoke(Note $note): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $note->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Catatan berhasil dihapus.');
    }
}
