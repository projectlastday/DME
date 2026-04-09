<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\NoteImage;
use App\Models\User;
use App\Services\Notes\NoteImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class NoteImageController extends Controller
{
    public function destroy(string $noteImage, NoteImageStorage $noteImageStorage): RedirectResponse
    {
        $this->ensureTeacher();

        $writer = app('App\Services\Notes\NoteWriter');

        if ($this->supportsAnyMethod($writer, ['deleteImage', 'deleteNoteImage', 'removeImage'])) {
            $this->invoke(
                $writer,
                ['deleteImage', 'deleteNoteImage', 'removeImage'],
                [$noteImage, request()->user()->getKey()]
            );

            $studentId = (string) request()->input('student_id', '');
        } else {
            $noteImageModel = NoteImage::query()->with('note')->findOrFail($noteImage);

            Gate::authorize('delete', $noteImageModel);

            $studentId = (string) $noteImageModel->note->student_id;

            DB::transaction(function () use ($noteImageModel, $noteImageStorage): void {
                $note = $noteImageModel->note()->firstOrFail();
                $deletedSortOrder = (int) $noteImageModel->sort_order;

                $noteImageStorage->deleteStoredImages([$noteImageModel]);
                $noteImageModel->delete();

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
        }

        return redirect()
            ->route('teacher.students.show', $studentId)
            ->with('status', 'Foto catatan berhasil dihapus.');
    }

    private function supportsAnyMethod(mixed $service, array $methods): bool
    {
        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                return true;
            }
        }

        return false;
    }

    private function invoke(mixed $service, array $methods, array $arguments = []): mixed
    {
        foreach ($methods as $method) {
            if (method_exists($service, $method)) {
                try {
                    return $service->{$method}(...$arguments);
                } catch (AuthorizationException $exception) {
                    abort(403, $exception->getMessage());
                } catch (\ArgumentCountError) {
                    continue;
                }
            }
        }

        throw new LogicException('Teacher note image contract is unavailable.');
    }

    private function ensureTeacher(): void
    {
        abort_unless(request()->user()?->hasRole(User::ROLE_TEACHER), 403);
    }
}
