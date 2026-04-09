import { validateEntryDraft } from './validateEntryDraft.js';

export function buildEntryEditPayload({ draftEntry, retainedPhotos = [], pendingPhotos = [] }) {
    const validation = validateEntryDraft({
        draftEntry,
        pendingPhotos,
        retainedPhotoCount: retainedPhotos.length,
    });

    if (!validation.isValid) {
        return {
            isValid: false,
            message: validation.message,
            note: validation.normalizedNote,
            retainedPhotoIds: [],
            pendingPhotos,
        };
    }

    return {
        isValid: true,
        message: null,
        note: validation.normalizedNote,
        retainedPhotoIds: retainedPhotos.map((photo) => photo.id),
        pendingPhotos,
    };
}
