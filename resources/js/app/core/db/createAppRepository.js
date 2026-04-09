import { APP_DB_INDEXES, APP_DB_STORES } from './appDbSchema.js';
import {
    getAllByIndex,
    getAllRecords,
    getRecordByKey,
    putRecord,
    runTransaction,
} from './idbHelpers.js';
import {
    createEntryWriteRecord,
    createNowIsoString,
    createStudentWriteRecord,
    normalizeEntryRecord,
    normalizePhotoRecord,
    normalizeStudentRecord,
} from './normalizers.js';

export function createAppRepository({ db, hooks = {} }) {
    return {
        async loadAllStudents() {
            const records = await getAllRecords(db, APP_DB_STORES.students);

            return records
                .map((record) => normalizeStudentRecord(record))
                .filter(Boolean)
                .sort((left, right) => left.name.localeCompare(right.name));
        },

        async loadStudentById(studentId) {
            const record = await getRecordByKey(db, APP_DB_STORES.students, studentId);

            return normalizeStudentRecord(record);
        },

        async loadEntriesByStudent(studentId) {
            const records = await getAllByIndex(
                db,
                APP_DB_STORES.entries,
                APP_DB_INDEXES.entriesByStudent,
                studentId
            );

            return records
                .map((record) => normalizeEntryRecord(record))
                .filter(Boolean)
                .sort((left, right) => right.createdAt.localeCompare(left.createdAt));
        },

        async loadPhotosByEntryIds(entryIds) {
            const uniqueEntryIds = [...new Set(entryIds.filter((entryId) => typeof entryId === 'string'))];
            const photoBuckets = await Promise.all(
                uniqueEntryIds.map((entryId) =>
                    getAllByIndex(db, APP_DB_STORES.photos, APP_DB_INDEXES.photosByEntry, entryId)
                )
            );

            return photoBuckets
                .flat()
                .map((record) => normalizePhotoRecord(record))
                .filter(Boolean)
                .sort((left, right) => left.createdAt.localeCompare(right.createdAt));
        },

        async createStudent(input) {
            const record = createStudentWriteRecord(input);
            await putRecord(db, APP_DB_STORES.students, record);

            return record;
        },

        async updateStudent(input) {
            const existingRecord = await getRecordByKey(db, APP_DB_STORES.students, input.id);
            const normalizedExisting = normalizeStudentRecord(existingRecord);

            if (!normalizedExisting) {
                throw new Error(`Murid "${input.id}" tidak ditemukan.`);
            }

            const record = createStudentWriteRecord({
                ...normalizedExisting,
                ...input,
                updatedAt: input.updatedAt ?? createNowIsoString(),
            });

            await putRecord(db, APP_DB_STORES.students, record);

            return record;
        },

        async deleteStudent(studentId) {
            const rawEntries = await getAllByIndex(
                db,
                APP_DB_STORES.entries,
                APP_DB_INDEXES.entriesByStudent,
                studentId
            );
            const entryIds = rawEntries
                .map((entry) => (typeof entry?.id === 'string' && entry.id.trim() ? entry.id : null))
                .filter(Boolean);
            const rawPhotos = await collectRawPhotosByEntryIds(db, entryIds);
            const photoIds = rawPhotos
                .map((photo) => (typeof photo?.id === 'string' && photo.id.trim() ? photo.id : null))
                .filter(Boolean);

            await runTransaction(
                db,
                [APP_DB_STORES.students, APP_DB_STORES.entries, APP_DB_STORES.photos],
                'readwrite',
                ({ stores }) => {
                    photoIds.forEach((photoId) => {
                        stores.photos.delete(photoId);
                    });

                    entryIds.forEach((entryId) => {
                        stores.entries.delete(entryId);
                    });

                    stores.students.delete(studentId);

                    hooks.beforeDeleteStudentCommit?.({
                        studentId,
                        entryIds,
                        photoIds,
                    });
                }
            );
        },

        async createEntry(input) {
            const student = await getRecordByKey(db, APP_DB_STORES.students, input.studentId);

            if (!normalizeStudentRecord(student)) {
                throw new Error(`Murid "${input.studentId}" tidak ditemukan.`);
            }

            const record = createEntryWriteRecord(input);
            await putRecord(db, APP_DB_STORES.entries, record);

            return record;
        },

        async updateEntry(input) {
            const existingRecord = await getRecordByKey(db, APP_DB_STORES.entries, input.id);
            const normalizedExisting = normalizeEntryRecord(existingRecord);

            if (!normalizedExisting) {
                throw new Error(`Entri "${input.id}" tidak ditemukan.`);
            }

            const record = createEntryWriteRecord({
                ...normalizedExisting,
                ...input,
                updatedAt: input.updatedAt ?? createNowIsoString(),
            });

            await putRecord(db, APP_DB_STORES.entries, record);

            return record;
        },

        async deleteEntry(entryId) {
            const rawPhotos = await collectRawPhotosByEntryIds(db, [entryId]);
            const photoIds = rawPhotos
                .map((photo) => (typeof photo?.id === 'string' && photo.id.trim() ? photo.id : null))
                .filter(Boolean);

            await runTransaction(
                db,
                [APP_DB_STORES.entries, APP_DB_STORES.photos],
                'readwrite',
                ({ stores }) => {
                    photoIds.forEach((photoId) => {
                        stores.photos.delete(photoId);
                    });

                    stores.entries.delete(entryId);
                }
            );
        },
    };
}

async function collectRawPhotosByEntryIds(db, entryIds) {
    const buckets = await Promise.all(
        [...new Set(entryIds)].map((entryId) =>
            getAllByIndex(db, APP_DB_STORES.photos, APP_DB_INDEXES.photosByEntry, entryId)
        )
    );

    return buckets.flat();
}
