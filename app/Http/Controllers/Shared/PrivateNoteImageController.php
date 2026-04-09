<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateNoteImageController extends Controller
{
    public function show(Request $request, int $noteImage, NoteImageStorage $noteImageStorage): StreamedResponse
    {
        $image = DB::table('note_images')
            ->join('notes', 'notes.id', '=', 'note_images.note_id')
            ->select([
                'note_images.id',
                'note_images.disk',
                'note_images.path',
                'note_images.original_filename',
                'note_images.mime_type',
                'note_images.size_bytes',
                'note_images.note_id',
                'notes.student_id',
                'notes.author_id',
                'notes.author_role_snapshot',
            ])
            ->where('note_images.id', $noteImage)
            ->first();

        abort_unless($image !== null, 404);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $role = (string) data_get($user, 'role');
        $userId = (int) data_get($user, 'id');

        $authorized = match ($role) {
            User::ROLE_SUPER_ADMIN => true,
            User::ROLE_TEACHER => true,
            User::ROLE_STUDENT => (int) $image->student_id === $userId,
            default => false,
        };

        abort_unless($authorized, 403);

        return $noteImageStorage->streamStoredImage((array) $image);
    }
}
