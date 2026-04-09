import { APP_DB_STORES } from '../../core/db/appDbSchema.js';
import { createEntryWriteRecord } from '../../core/db/normalizers.js';
import { runTransaction } from '../../core/db/idbHelpers.js';

export async function createEntryWithPhotosTransaction({
    db,
    studentId,
    note,
    pendingPhotos,
    now = new Date(),
    hooks = {},
}) {
    const timestamp = normalizeDate(now);
    const createdAt = timestamp.toISOString();
    const entryDate = formatLocalDate(timestamp);
    const entryRecord = createEntryWriteRecord({
        studentId,
        date: entryDate,
        note,
        createdAt,
        updatedAt: createdAt,
    });
    const photoRecords = pendingPhotos.map((photo) =>
        createPhotoRecord({
            entryId: entryRecord.id,
            pendingPhoto: photo,
            createdAt,
        })
    );

    await runTransaction(
        db,
        [APP_DB_STORES.entries, APP_DB_STORES.photos],
        'readwrite',
        ({ stores }) => {
            stores.entries.put(entryRecord);

            photoRecords.forEach((photoRecord) => {
                stores.photos.put(photoRecord);
            });

            hooks.beforeCommit?.({
                entryRecord,
                photoRecords,
            });
        }
    );

    return {
        entryRecord,
        photoRecords,
    };
}

function createPhotoRecord({ entryId, pendingPhoto, createdAt }) {
    return {
        id: createId(),
        entryId,
        createdAt,
        blob: pendingPhoto.fullImage.blob,
        mimeType: pendingPhoto.fullImage.mimeType,
        width: pendingPhoto.fullImage.width,
        height: pendingPhoto.fullImage.height,
        size: pendingPhoto.fullImage.size,
        thumbnailBlob: pendingPhoto.thumbnail.blob,
        thumbnailMimeType: pendingPhoto.thumbnail.mimeType,
        thumbnailWidth: pendingPhoto.thumbnail.width,
        thumbnailHeight: pendingPhoto.thumbnail.height,
        thumbnailSize: pendingPhoto.thumbnail.size,
        fileName: pendingPhoto.fileName,
        sourceMimeType: pendingPhoto.sourceMimeType,
    };
}

function formatLocalDate(value) {
    const year = value.getFullYear();
    const month = `${value.getMonth() + 1}`.padStart(2, '0');
    const day = `${value.getDate()}`.padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function normalizeDate(value) {
    return value instanceof Date ? value : new Date(value);
}

function createId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `entry-photo-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
