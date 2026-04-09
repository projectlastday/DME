import test from 'node:test';
import assert from 'node:assert/strict';
import { indexedDB } from 'fake-indexeddb';

import { APP_DB_NAME } from '../../core/db/appDbSchema.js';
import { initAppDb } from '../../core/db/initAppDb.js';
import { seedPhotoRecord } from '../../core/db/testDbHelpers.js';
import { updateEntryWithPhotosTransaction } from './updateEntryWithPhotosTransaction.js';

test('updates entry note, removes existing photos, and adds new photos in one transaction', async () => {
    const { connection, repository } = await createHarness();
    await repository.createStudent({
        id: 'mei-lin',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });
    const entry = await repository.createEntry({
        id: 'entry-1',
        studentId: 'mei-lin',
        date: '2026-03-30',
        note: 'Original note',
        createdAt: '2026-03-30T01:00:00.000Z',
        updatedAt: '2026-03-30T01:00:00.000Z',
    });
    await seedPhotoRecord(connection, buildSavedPhoto('saved-1', entry.id));
    await seedPhotoRecord(connection, buildSavedPhoto('saved-2', entry.id));

    const result = await updateEntryWithPhotosTransaction({
        db: connection,
        entryId: entry.id,
        note: 'Updated note',
        retainedPhotoIds: ['saved-2'],
        pendingPhotos: [buildPendingPhoto('new-photo.jpg')],
        now: new Date('2026-03-30T02:00:00.000Z'),
    });

    const entries = await repository.loadEntriesByStudent('mei-lin');
    const photos = await repository.loadPhotosByEntryIds([entry.id]);

    assert.equal(result.entryRecord.note, 'Updated note');
    assert.deepEqual(result.removedPhotoIds, ['saved-1']);
    assert.equal(result.newPhotoRecords.length, 1);
    assert.equal(entries[0].note, 'Updated note');
    assert.equal(photos.length, 2);
    assert.deepEqual(
        photos.map((photo) => photo.id).sort(),
        ['saved-2', result.newPhotoRecords[0].id].sort()
    );

    connection.close();
    await resetDatabase();
});

test('rolls back entry updates and photo mutations when the edit transaction fails', async () => {
    const { connection, repository } = await createHarness();
    await repository.createStudent({
        id: 'sam-wei',
        name: 'Sam Wei',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });
    await repository.createEntry({
        id: 'entry-rollback',
        studentId: 'sam-wei',
        date: '2026-03-30',
        note: 'Keep me',
        createdAt: '2026-03-30T01:00:00.000Z',
        updatedAt: '2026-03-30T01:00:00.000Z',
    });
    await seedPhotoRecord(connection, buildSavedPhoto('saved-rollback', 'entry-rollback'));

    await assert.rejects(
        () =>
            updateEntryWithPhotosTransaction({
                db: connection,
                entryId: 'entry-rollback',
                note: 'Changed',
                retainedPhotoIds: [],
                pendingPhotos: [buildPendingPhoto('new-photo.jpg')],
                hooks: {
                    beforeCommit() {
                        throw new Error('force rollback');
                    },
                },
            }),
        /force rollback/
    );

    const entries = await repository.loadEntriesByStudent('sam-wei');
    const photos = await repository.loadPhotosByEntryIds(['entry-rollback']);

    assert.equal(entries[0].note, 'Keep me');
    assert.equal(photos.length, 1);
    assert.equal(photos[0].id, 'saved-rollback');

    connection.close();
    await resetDatabase();
});

async function createHarness() {
    await resetDatabase();

    const dbState = await initAppDb({
        indexedDb: indexedDB,
    });

    return {
        connection: dbState.connection,
        repository: dbState.repository,
    };
}

function resetDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.deleteDatabase(APP_DB_NAME);

        request.onerror = () => reject(request.error ?? new Error('Failed to reset test database.'));
        request.onsuccess = () => resolve();
    });
}

function buildPendingPhoto(fileName) {
    return {
        id: `pending-${fileName}`,
        fileName,
        sourceMimeType: 'image/jpeg',
        fullImage: {
            blob: { type: 'image/jpeg', name: `${fileName}-full` },
            mimeType: 'image/jpeg',
            width: 1200,
            height: 900,
            size: 400000,
        },
        thumbnail: {
            blob: { type: 'image/jpeg', name: `${fileName}-thumb` },
            mimeType: 'image/jpeg',
            width: 320,
            height: 240,
            size: 45000,
            previewUrl: `blob:${fileName}`,
        },
    };
}

function buildSavedPhoto(id, entryId) {
    return {
        id,
        entryId,
        createdAt: '2026-03-30T01:00:00.000Z',
        blob: { type: 'image/jpeg', name: `${id}-full` },
        mimeType: 'image/jpeg',
        width: 1200,
        height: 900,
        size: 400000,
        thumbnailBlob: { type: 'image/jpeg', name: `${id}-thumb` },
        thumbnailMimeType: 'image/jpeg',
        thumbnailWidth: 320,
        thumbnailHeight: 240,
        thumbnailSize: 45000,
        fileName: `${id}.jpg`,
        sourceMimeType: 'image/jpeg',
    };
}
