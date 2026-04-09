import test from 'node:test';
import assert from 'node:assert/strict';
import { indexedDB } from 'fake-indexeddb';

import { APP_DB_INDEXES, APP_DB_NAME, APP_DB_STORES, APP_DB_VERSION } from './appDbSchema.js';
import { initAppDb } from './initAppDb.js';

test('opens the dianas-mandarin-pwa database with the locked stores and indexes', async () => {
    await resetDatabase();

    const dbState = await initAppDb({
        indexedDb: indexedDB,
    });

    assert.equal(dbState.isReady, true);
    assert.equal(dbState.isSupported, true);
    assert.equal(dbState.name, APP_DB_NAME);
    assert.equal(dbState.version, APP_DB_VERSION);
    assert.ok(dbState.connection.objectStoreNames.contains(APP_DB_STORES.students));
    assert.ok(dbState.connection.objectStoreNames.contains(APP_DB_STORES.entries));
    assert.ok(dbState.connection.objectStoreNames.contains(APP_DB_STORES.photos));
    assert.ok(dbState.connection.objectStoreNames.contains(APP_DB_STORES.meta));

    const transaction = dbState.connection.transaction(
        [APP_DB_STORES.students, APP_DB_STORES.entries, APP_DB_STORES.photos],
        'readonly'
    );

    assert.ok(
        transaction.objectStore(APP_DB_STORES.students).indexNames.contains(APP_DB_INDEXES.studentsByName)
    );
    assert.ok(
        transaction.objectStore(APP_DB_STORES.entries).indexNames.contains(APP_DB_INDEXES.entriesByStudent)
    );
    assert.ok(
        transaction.objectStore(APP_DB_STORES.entries).indexNames.contains(APP_DB_INDEXES.entriesByStudentDate)
    );
    assert.ok(
        transaction.objectStore(APP_DB_STORES.entries).indexNames.contains(APP_DB_INDEXES.entriesByStudentCreated)
    );
    assert.ok(transaction.objectStore(APP_DB_STORES.photos).indexNames.contains(APP_DB_INDEXES.photosByEntry));

    dbState.connection.close();
    await resetDatabase();
});

function resetDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.deleteDatabase(APP_DB_NAME);

        request.onerror = () => reject(request.error ?? new Error('Failed to reset test database.'));
        request.onsuccess = () => resolve();
    });
}
