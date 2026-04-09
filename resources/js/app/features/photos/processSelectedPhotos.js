import {
    THUMBNAIL_MAX_BYTES,
    THUMBNAIL_MAX_HEIGHT,
    THUMBNAIL_MAX_WIDTH,
    THUMBNAIL_PRIMARY_QUALITY,
    THUMBNAIL_RETRY_QUALITY,
} from './photoConstants.js';
import { validatePhotoSelection } from './validatePhotoSelection.js';

export async function processSelectedPhotos({ files, existingPendingPhotos = [], adapter }) {
    const { acceptedFiles, rejectedFiles } = validatePhotoSelection(files, existingPendingPhotos.length);
    const processedPhotos = [];
    const processingErrors = [...rejectedFiles];

    for (const file of acceptedFiles) {
        try {
            const processedPhoto = await processImageFile({
                file,
                adapter,
            });

            processedPhotos.push(processedPhoto);
        } catch (error) {
            processingErrors.push({
                fileName: file.name,
                message: error instanceof Error ? error.message : 'Foto gagal diproses.',
            });
        }
    }

    return {
        pendingPhotos: processedPhotos,
        errors: processingErrors,
    };
}

export async function processImageFile({ file, adapter }) {
    const decodedImage = await adapter.decodeFile(file);

    try {
        const thumbnail = await renderWithRetry({
            adapter,
            image: decodedImage,
            maxWidth: THUMBNAIL_MAX_WIDTH,
            maxHeight: THUMBNAIL_MAX_HEIGHT,
            primaryQuality: THUMBNAIL_PRIMARY_QUALITY,
            retryQuality: THUMBNAIL_RETRY_QUALITY,
            maxBytes: THUMBNAIL_MAX_BYTES,
        });

        return {
            id: createPendingPhotoId(),
            fileName: file.name,
            sourceMimeType: file.type,
            fullImage: {
                blob: file,
                mimeType: file.type,
                width: decodedImage.width,
                height: decodedImage.height,
                size: file.size,
            },
            thumbnail: {
                blob: thumbnail.blob,
                mimeType: 'image/jpeg',
                width: thumbnail.width,
                height: thumbnail.height,
                size: thumbnail.blob.size,
                previewUrl: adapter.createObjectUrl(thumbnail.blob),
            },
        };
    } finally {
        decodedImage.close?.();
    }
}

async function renderWithRetry({
    adapter,
    image,
    maxWidth,
    maxHeight,
    primaryQuality,
    retryQuality,
    maxBytes,
}) {
    const primaryResult = await adapter.renderJpeg({
        image,
        maxWidth,
        maxHeight,
        quality: primaryQuality,
    });

    if (primaryResult.blob.size <= maxBytes) {
        return primaryResult;
    }

    const retryResult = await adapter.renderJpeg({
        image,
        maxWidth,
        maxHeight,
        quality: retryQuality,
    });

    if (retryResult.blob.size > maxBytes) {
        throw new Error('Hasil pemrosesan gambar melebihi batas ukuran keluaran.');
    }

    return retryResult;
}

function createPendingPhotoId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `pending-photo-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
