<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\NoteImage;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class NoteImageController extends Controller
{
    public function destroy(NoteImage $noteImage, NoteImageStorage $noteImageStorage): RedirectResponse
    {
        $noteImage->loadMissing('note');

        Gate::authorize('delete', $noteImage);

        $note = $noteImage->note;
        $remainingImages = $note->noteImages()->count() - 1;

        if (blank($note->body) && $remainingImages < 1) {
            return redirect()
                ->route('student.notes.edit', $note->getKey())
                ->with('error', 'Foto ini tidak dapat dihapus karena catatan akan menjadi kosong. Silakan hapus seluruh catatan saja.');
        }

        DB::transaction(function () use ($noteImage, $noteImageStorage): void {
            $note = $noteImage->note()->firstOrFail();
            $deletedSortOrder = (int) $noteImage->sort_order;

            $noteImageStorage->deleteStoredImages([$noteImage]);
            $noteImage->delete();

            $note->noteImages()
                ->where('sort_order', '>', $deletedSortOrder)
                ->orderBy('sort_order')
                ->get()
                ->each(function (NoteImage $image): void {
                    $image->update([
                        'sort_order' => $image->sort_order - 1,
                    ]);
                });
        });

        return redirect()
            ->route('student.notes.edit', $note->getKey())
            ->with('status', 'Foto catatan berhasil dihapus.');
    }
}
