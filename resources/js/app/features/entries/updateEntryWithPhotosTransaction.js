import { APP_DB_INDEXES, APP_DB_STORES } from '../../core/db/appDbSchema.js';
import { getAllByIndex, getRecordByKey, runTransaction } from '../../core/db/idbHelpers.js';
import { createEntryWriteRecord, normalizeEntryRecord, normalizePhotoRecord } from '../../core/db/normalizers.js';

export async function updateEntryWithPhotosTransaction({
    db,
    entryId,
    note,
    retainedPhotoIds,
    pendingPhotos,
    now = new Date(),
    hooks = {},
}) {
    const existingRecord = await getRecordByKey(db, APP_DB_STORES.entries, entryId);
    const existingEntry = normalizeEntryRecord(existingRecord);

    if (!existingEntry) {
        throw new Error(`Entri "${entryId}" tidak ditemukan.`);
    }

    const existingPhotos = (
        await getAllByIndex(db, APP_DB_STORES.photos, APP_DB_INDEXES.photosByEntry, entryId)
    )
        .map((photo) => normalizePhotoRecord(photo))
        .filter(Boolean);
    const retainedPhotoIdSet = new Set(
        retainedPhotoIds.filter((photoId) => typeof photoId === 'string' && photoId.trim())
    );
    const removedPhotoIds = existingPhotos
        .filter((photo) => !retainedPhotoIdSet.has(photo.id))
        .map((photo) => photo.id);
    const updatedAt = normalizeDate(now).toISOString();
    const entryRecord = createEntryWriteRecord({
        ...existingEntry,
        note,
        updatedAt,
    });
    const newPhotoRecords = pendingPhotos.map((photo) =>
        createPhotoRecord({
            entryId,
            pendingPhoto: photo,
            createdAt: updatedAt,
        })
    );

    await runTransaction(
        db,
        [APP_DB_STORES.entries, APP_DB_STORES.photos],
        'readwrite',
        ({ stores }) => {
            stores.entries.put(entryRecord);

            removedPhotoIds.forEach((photoId) => {
                stores.photos.delete(photoId);
            });

            newPhotoRecords.forEach((photoRecord) => {
                stores.photos.put(photoRecord);
            });

            hooks.beforeCommit?.({
                entryRecord,
                removedPhotoIds,
                newPhotoRecords,
            });
        }
    );

    return {
        entryRecord,
        removedPhotoIds,
        newPhotoRecords,
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

function normalizeDate(value) {
    return value instanceof Date ? value : new Date(value);
}

function createId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `entry-photo-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
