import { APP_DB_INDEXES, APP_DB_NAME, APP_DB_STORES, APP_DB_VERSION } from './appDbSchema.js';
import { createAppRepository } from './createAppRepository.js';
import { openIndexedDb } from './idbHelpers.js';

export async function initAppDb({ indexedDb }) {
    if (!indexedDb) {
        return {
            isReady: false,
            isSupported: false,
            name: APP_DB_NAME,
            version: APP_DB_VERSION,
            connection: null,
            repository: null,
        };
    }

    const connection = await openIndexedDb({
        indexedDb,
        name: APP_DB_NAME,
        version: APP_DB_VERSION,
        upgrade: ({ db }) => {
            if (!db.objectStoreNames.contains(APP_DB_STORES.students)) {
                const students = db.createObjectStore(APP_DB_STORES.students, {
                    keyPath: 'id',
                });

                students.createIndex(APP_DB_INDEXES.studentsByName, 'name', {
                    unique: false,
                });
            }

            if (!db.objectStoreNames.contains(APP_DB_STORES.entries)) {
                const entries = db.createObjectStore(APP_DB_STORES.entries, {
                    keyPath: 'id',
                });

                entries.createIndex(APP_DB_INDEXES.entriesByStudent, 'studentId', {
                    unique: false,
                });
                entries.createIndex(APP_DB_INDEXES.entriesByStudentDate, ['studentId', 'date'], {
                    unique: false,
                });
                entries.createIndex(APP_DB_INDEXES.entriesByStudentCreated, ['studentId', 'createdAt'], {
                    unique: false,
                });
            }

            if (!db.objectStoreNames.contains(APP_DB_STORES.photos)) {
                const photos = db.createObjectStore(APP_DB_STORES.photos, {
                    keyPath: 'id',
                });

                photos.createIndex(APP_DB_INDEXES.photosByEntry, 'entryId', {
                    unique: false,
                });
            }

            if (!db.objectStoreNames.contains(APP_DB_STORES.meta)) {
                db.createObjectStore(APP_DB_STORES.meta, {
                    keyPath: 'key',
                });
            }
        },
    });

    return {
        isReady: true,
        isSupported: true,
        name: APP_DB_NAME,
        version: APP_DB_VERSION,
        connection,
        repository: createAppRepository({
            db: connection,
        }),
    };
}
