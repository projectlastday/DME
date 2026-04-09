import test from 'node:test';
import assert from 'node:assert/strict';
import { indexedDB } from 'fake-indexeddb';

import { APP_DB_NAME } from '../../core/db/appDbSchema.js';
import { initAppDb } from '../../core/db/initAppDb.js';
import { createEntryWithPhotosTransaction } from './createEntryWithPhotosTransaction.js';

test('creates note-only, photo-only, and mixed entries with linked photos in one transaction', async () => {
    const { connection, repository } = await createHarness();

    await repository.createStudent({
        id: 'mei-lin',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    const noteOnly = await createEntryWithPhotosTransaction({
        db: connection,
        studentId: 'mei-lin',
        note: 'Note only entry',
        pendingPhotos: [],
        now: new Date('2026-03-30T10:00:00.000Z'),
    });

    const photoOnly = await createEntryWithPhotosTransaction({
        db: connection,
        studentId: 'mei-lin',
        note: '',
        pendingPhotos: [buildPendingPhoto('photo-only.jpg')],
        now: new Date('2026-03-30T11:00:00.000Z'),
    });

    const mixed = await createEntryWithPhotosTransaction({
        db: connection,
        studentId: 'mei-lin',
        note: 'Mixed entry',
        pendingPhotos: [buildPendingPhoto('mixed.jpg')],
        now: new Date('2026-03-30T12:00:00.000Z'),
    });

    const entries = await repository.loadEntriesByStudent('mei-lin');
    const photos = await repository.loadPhotosByEntryIds([photoOnly.entryRecord.id, mixed.entryRecord.id]);

    assert.equal(noteOnly.photoRecords.length, 0);
    assert.equal(entries.length, 3);
    assert.deepEqual(
        entries.map((entry) => entry.note),
        ['Mixed entry', '', 'Note only entry']
    );
    assert.equal(photos.length, 2);

    connection.close();
    await resetDatabase();
});

test('rolls back entry and photo writes when the create transaction fails', async () => {
    const { connection, repository } = await createHarness();

    await repository.createStudent({
        id: 'sam-wei',
        name: 'Sam Wei',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await assert.rejects(
        () =>
            createEntryWithPhotosTransaction({
                db: connection,
                studentId: 'sam-wei',
                note: 'Rollback me',
                pendingPhotos: [buildPendingPhoto('rollback.jpg')],
                hooks: {
                    beforeCommit() {
                        throw new Error('force rollback');
                    },
                },
            }),
        /force rollback/
    );

    assert.deepEqual(await repository.loadEntriesByStudent('sam-wei'), []);

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
