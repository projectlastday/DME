<?php

namespace App\Services\Notes;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NoteImageStorage
{
    public const PRIVATE_DISK = 'note-images-private';

    public static function maxImagesPerNote(): int
    {
        return 6;
    }

    public static function maxFileKilobytes(): int
    {
        return 15 * 1024;
    }

    public static function allowedMimeTypes(): array
    {
        return [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];
    }

    public function storeUploadedImages(iterable $uploadedImages, ?int $noteId = null, int $startingSortOrder = 1): Collection
    {
        $stored = [];
        $sortOrder = $startingSortOrder;

        foreach ($uploadedImages as $uploadedImage) {
            if (! $uploadedImage instanceof UploadedFile) {
                continue;
            }

            $path = $uploadedImage->storeAs(
                $this->directoryFor($noteId),
                $this->generateFilename($uploadedImage),
                self::PRIVATE_DISK,
            );

            $stored[] = [
                'disk' => self::PRIVATE_DISK,
                'path' => $path,
                'original_filename' => $uploadedImage->getClientOriginalName(),
                'mime_type' => $uploadedImage->getMimeType() ?: $uploadedImage->getClientMimeType() ?: 'application/octet-stream',
                'size_bytes' => $uploadedImage->getSize() ?: 0,
                'sort_order' => $sortOrder++,
            ];
        }

        return collect($stored);
    }

    public function deleteStoredImages(iterable $images): void
    {
        foreach ($images as $image) {
            $disk = (string) data_get($image, 'disk', self::PRIVATE_DISK);
            $path = data_get($image, 'path');

            if (is_string($path) && $path !== '') {
                Storage::disk($disk)->delete($this->normalizePath($path));
            }
        }
    }

    public function displayUrl(int $noteImageId): string
    {
        return "/note-images/{$noteImageId}";
    }

    public function streamStoredImage(array $image): StreamedResponse
    {
        $disk = (string) ($image['disk'] ?? self::PRIVATE_DISK);
        $storedPath = (string) ($image['path'] ?? '');
        $path = $this->resolvablePath($disk, $storedPath);
        $filesystem = Storage::disk($disk);
        $headers = [
            'Content-Type' => (string) ($image['mime_type'] ?? 'application/octet-stream'),
            'Content-Length' => (string) ($image['size_bytes'] ?? ''),
            'Cache-Control' => 'private, max-age=0, must-revalidate',
            'Content-Disposition' => 'inline; filename="'.addslashes((string) ($image['original_filename'] ?? basename($path))).'"',
        ];

        $stream = $filesystem->readStream($path);

        if (is_resource($stream)) {
            return response()->stream(function () use ($stream): void {
                fpassthru($stream);
                fclose($stream);
            }, 200, $headers);
        }

        return response()->stream(function () use ($filesystem, $path): void {
            echo $filesystem->get($path);
        }, 200, $headers);
    }

    private function directoryFor(?int $noteId): string
    {
        $segment = $noteId === null ? 'pending' : "note-{$noteId}";

        return $segment;
    }

    private function generateFilename(UploadedFile $uploadedFile): string
    {
        $extension = $uploadedFile->guessExtension()
            ?: $uploadedFile->clientExtension()
            ?: $uploadedFile->extension()
            ?: 'bin';

        return Str::uuid()->toString().'.'.$extension;
    }

    private function normalizePath(string $path): string
    {
        return str_starts_with($path, 'note-images/')
            ? substr($path, strlen('note-images/'))
            : $path;
    }

    private function resolvablePath(string $disk, string $path): string
    {
        $normalizedPath = $this->normalizePath($path);

        if (Storage::disk($disk)->exists($normalizedPath)) {
            return $normalizedPath;
        }

        if ($path !== '' && Storage::disk($disk)->exists($path)) {
            return $path;
        }

        return $normalizedPath;
    }
}
