<?php

namespace App\Http\Requests\Shared;

use App\Services\Notes\NoteImageStorage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;

class UpsertNoteRequest extends FormRequest
{
    /**
     * @var array<int, UploadedFile>|null
     */
    private ?array $decodedImagePayloadFiles = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'body' => ['nullable', 'string'],
            'note_date' => ['nullable', 'date_format:Y-m-d'],
            'retained_image_ids' => ['sometimes', 'array'],
            'retained_image_ids.*' => ['integer', 'min:1'],
            'images' => ['sometimes', 'array', 'max:'.NoteImageStorage::maxImagesPerNote()],
            'images.*' => [
                'file',
                'mimetypes:'.implode(',', NoteImageStorage::allowedMimeTypes()),
                'max:'.NoteImageStorage::maxFileKilobytes(),
            ],
            'new_images' => ['sometimes', 'array', 'max:'.NoteImageStorage::maxImagesPerNote()],
            'new_images.*' => [
                'file',
                'mimetypes:'.implode(',', NoteImageStorage::allowedMimeTypes()),
                'max:'.NoteImageStorage::maxFileKilobytes(),
            ],
            'new_image_payloads' => ['sometimes', 'array', 'max:'.NoteImageStorage::maxImagesPerNote()],
            'new_image_payloads.*' => ['string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $body = $this->input('body');
        $uploadedImages = array_values((array) $this->file('images', $this->file('new_images', [])));

        $this->merge([
            'body' => is_string($body) ? trim($body) : $body,
            'retained_image_ids' => array_values(array_filter(
                (array) $this->input('retained_image_ids', []),
                static fn ($value): bool => $value !== null && $value !== ''
            )),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $body = $this->validatedBody();
            $retainedCount = count((array) $this->input('retained_image_ids', []));
            $uploadedCount = count($this->uploadedImages());
            $finalImageCount = $retainedCount + $uploadedCount;

            if ($finalImageCount > NoteImageStorage::maxImagesPerNote()) {
                $validator->errors()->add('images', 'Catatan hanya boleh memiliki maksimal 6 gambar.');
            }

            if ($body === null && $finalImageCount === 0) {
                $validator->errors()->add('body', 'Catatan harus berisi teks atau setidaknya satu gambar.');
            }
        });
    }

    public function validatedPayload(): array
    {
        return [
            'body' => $this->validatedBody(),
            'note_date' => $this->validated('note_date'),
            'retained_image_ids' => array_map('intval', (array) $this->validated('retained_image_ids', [])),
            'uploaded_images' => $this->uploadedImages(),
        ];
    }

    /**
     * @return array<int, UploadedFile>
     */
    public function uploadedImages(): array
    {
        return array_values([
            ...array_values((array) $this->file('images', $this->file('new_images', []))),
            ...$this->decodedImagePayloadFiles(),
        ]);
    }

    private function validatedBody(): ?string
    {
        $body = $this->input('body');

        if (! is_string($body)) {
            return null;
        }

        $trimmed = trim($body);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function decodedImagePayloadFiles(): array
    {
        if ($this->decodedImagePayloadFiles !== null) {
            return $this->decodedImagePayloadFiles;
        }

        $this->decodedImagePayloadFiles = [];

        foreach ((array) $this->input('new_image_payloads', []) as $payload) {
            if (! is_string($payload) || trim($payload) === '') {
                continue;
            }

            $decoded = json_decode($payload, true);

            if (! is_array($decoded)) {
                continue;
            }

            $dataUrl = data_get($decoded, 'data_url');
            $mimeType = (string) data_get($decoded, 'type', 'application/octet-stream');
            $originalName = (string) data_get($decoded, 'name', Str::uuid()->toString());

            if (! is_string($dataUrl) || ! preg_match('/^data:(.*?);base64,(.*)$/', $dataUrl, $matches)) {
                continue;
            }

            $binary = base64_decode($matches[2], true);

            if ($binary === false) {
                continue;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'dme_note_');

            if (! is_string($tempPath)) {
                continue;
            }

            file_put_contents($tempPath, $binary);

            $this->decodedImagePayloadFiles[] = new UploadedFile(
                $tempPath,
                $originalName !== '' ? $originalName : Str::uuid()->toString(),
                $mimeType !== '' ? $mimeType : $matches[1],
                null,
                true
            );
        }

        return $this->decodedImagePayloadFiles;
    }
}
