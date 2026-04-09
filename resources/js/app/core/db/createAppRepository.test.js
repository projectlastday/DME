import test from 'node:test';
import assert from 'node:assert/strict';
import { indexedDB } from 'fake-indexeddb';

import { APP_DB_NAME, APP_DB_STORES } from './appDbSchema.js';
import { createAppRepository } from './createAppRepository.js';
import { initAppDb } from './initAppDb.js';
import { insertRawRecord, seedPhotoRecord } from './testDbHelpers.js';

test('repository supports student create, load, update, and delete', async () => {
    const { connection, repository } = await createRepositoryHarness();

    const student = await repository.createStudent({
        id: 'mei-lin',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    const loadedStudent = await repository.loadStudentById(student.id);

    assert.equal(loadedStudent?.name, 'Mei Lin');

    await repository.updateStudent({
        id: student.id,
        name: 'Mei Lin Chen',
    });

    const allStudents = await repository.loadAllStudents();

    assert.deepEqual(
        allStudents.map((item) => item.name),
        ['Mei Lin Chen']
    );

    await repository.deleteStudent(student.id);

    assert.equal(await repository.loadStudentById(student.id), null);

    connection.close();
});

test('repository supports entry create, load, update, and delete', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await repository.createStudent({
        id: 'sam-wei',
        name: 'Sam Wei',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    const entry = await repository.createEntry({
        id: 'entry-1',
        studentId: 'sam-wei',
        date: '2026-03-29',
        note: 'Practiced tones.',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await repository.updateEntry({
        id: entry.id,
        note: 'Practiced tones and greetings.',
    });

    const entries = await repository.loadEntriesByStudent('sam-wei');

    assert.equal(entries.length, 1);
    assert.equal(entries[0].note, 'Practiced tones and greetings.');

    await repository.deleteEntry(entry.id);

    assert.deepEqual(await repository.loadEntriesByStudent('sam-wei'), []);

    connection.close();
});

test('loadPhotosByEntryIds skips malformed photos and returns valid ones', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await seedPhotoRecord(connection, {
        id: 'photo-1',
        entryId: 'entry-1',
        createdAt: '2026-03-30T00:00:00.000Z',
        mimeType: 'image/jpeg',
    });

    await insertRawRecord(connection, APP_DB_STORES.photos, {
        id: 'photo-bad',
        entryId: '',
        createdAt: 'bad-date',
    });

    const photos = await repository.loadPhotosByEntryIds(['entry-1']);

    assert.deepEqual(
        photos.map((photo) => photo.id),
        ['photo-1']
    );

    connection.close();
});

test('loadPhotosByEntryIds keeps incomplete photo previews from crashing reads', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await seedPhotoRecord(connection, {
        id: 'photo-incomplete',
        entryId: 'entry-1',
        createdAt: '2026-03-30T00:00:00.000Z',
        fileName: 'saved.jpg',
    });

    const photos = await repository.loadPhotosByEntryIds(['entry-1']);

    assert.equal(photos.length, 1);
    assert.equal(photos[0].thumbnailBlob, null);
    assert.equal(photos[0].blob, null);

    connection.close();
});

test('cascade delete removes student entries and entry photos in one repository flow', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await repository.createStudent({
        id: 'mei-lin',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await repository.createEntry({
        id: 'entry-1',
        studentId: 'mei-lin',
        date: '2026-03-29',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await seedPhotoRecord(connection, {
        id: 'photo-1',
        entryId: 'entry-1',
        createdAt: '2026-03-30T00:00:00.000Z',
        mimeType: 'image/jpeg',
    });

    await repository.deleteStudent('mei-lin');

    assert.equal(await repository.loadStudentById('mei-lin'), null);
    assert.deepEqual(await repository.loadEntriesByStudent('mei-lin'), []);
    assert.deepEqual(await repository.loadPhotosByEntryIds(['entry-1']), []);

    connection.close();
});

test('rename updates sorting and search behavior through repository-backed reads', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await repository.createStudent({
        id: 'student-1',
        name: 'Zoe Park',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await repository.createStudent({
        id: 'student-2',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await repository.updateStudent({
        id: 'student-1',
        name: 'Anya Park',
    });

    const students = await repository.loadAllStudents();

    assert.deepEqual(
        students.map((student) => student.name),
        ['Anya Park', 'Mei Lin']
    );
    assert.deepEqual(
        students.filter((student) => student.name.toLowerCase().includes('anya')).map((student) => student.id),
        ['student-1']
    );

    connection.close();
});

test('malformed records are skipped on reads instead of crashing the repository', async () => {
    const { connection, repository } = await createRepositoryHarness();

    await insertRawRecord(connection, APP_DB_STORES.students, {
        id: 'good-student',
        name: 'Good Student',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });
    await insertRawRecord(connection, APP_DB_STORES.students, {
        id: 'bad-student',
        name: '',
        createdAt: 'broken',
        updatedAt: 'broken',
    });

    await insertRawRecord(connection, APP_DB_STORES.entries, {
        id: 'good-entry',
        studentId: 'good-student',
        date: '2026-03-29',
        note: 'Valid entry',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });
    await insertRawRecord(connection, APP_DB_STORES.entries, {
        id: 'bad-entry',
        studentId: 'good-student',
        date: 'invalid',
        note: 'Invalid entry',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: 'bad',
    });

    const students = await repository.loadAllStudents();
    const entries = await repository.loadEntriesByStudent('good-student');

    assert.deepEqual(
        students.map((student) => student.id),
        ['good-student']
    );
    assert.deepEqual(
        entries.map((entry) => entry.id),
        ['good-entry']
    );

    connection.close();
});

test('cascade delete aborts cleanly when the transaction wrapper throws', async () => {
    const { connection } = await createRepositoryHarness();
    const repository = createAppRepository({
        db: connection,
        hooks: {
            beforeDeleteStudentCommit() {
                throw new Error('forced rollback');
            },
        },
    });

    await repository.createStudent({
        id: 'mei-lin',
        name: 'Mei Lin',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await repository.createEntry({
        id: 'entry-rollback',
        studentId: 'mei-lin',
        date: '2026-03-29',
        createdAt: '2026-03-30T00:00:00.000Z',
        updatedAt: '2026-03-30T00:00:00.000Z',
    });

    await seedPhotoRecord(connection, {
        id: 'photo-rollback',
        entryId: 'entry-rollback',
        createdAt: '2026-03-30T00:00:00.000Z',
        mimeType: 'image/jpeg',
    });

    await assert.rejects(() => repository.deleteStudent('mei-lin'), /forced rollback/);

    assert.ok(await repository.loadStudentById('mei-lin'));
    assert.equal((await repository.loadEntriesByStudent('mei-lin')).length, 1);
    assert.equal((await repository.loadPhotosByEntryIds(['entry-rollback'])).length, 1);

    connection.close();
});

async function createRepositoryHarness() {
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
