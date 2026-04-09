import {
    ACCEPTED_PHOTO_MIME_TYPES,
    MAX_PENDING_PHOTOS,
    MAX_PHOTO_FILE_SIZE_BYTES,
} from './photoConstants.js';

export function validatePhotoSelection(files, existingPendingCount = 0) {
    const acceptedFiles = [];
    const rejectedFiles = [];
    let remainingSlots = Math.max(0, MAX_PENDING_PHOTOS - existingPendingCount);

    files.forEach((file) => {
        if (!ACCEPTED_PHOTO_MIME_TYPES.includes(file.type)) {
            rejectedFiles.push({
                fileName: file.name,
                message: 'Hanya file JPEG, PNG, dan WebP yang didukung.',
            });
            return;
        }

        if (file.size > MAX_PHOTO_FILE_SIZE_BYTES) {
            rejectedFiles.push({
                fileName: file.name,
                message: 'Ukuran file maksimal 15 MB.',
            });
            return;
        }

        if (remainingSlots <= 0) {
            rejectedFiles.push({
                fileName: file.name,
                message: 'Maksimal 6 foto untuk setiap entri.',
            });
            return;
        }

        acceptedFiles.push(file);
        remainingSlots -= 1;
    });

    return {
        acceptedFiles,
        rejectedFiles,
    };
}
