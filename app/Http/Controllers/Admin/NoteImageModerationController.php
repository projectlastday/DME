<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NoteImage;
use Illuminate\Http\RedirectResponse;

class NoteImageModerationController extends Controller
{
    public function __invoke(NoteImage $noteImage): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'super_admin', 403);

        $noteImage->delete();

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Foto catatan berhasil dihapus.');
    }
}
