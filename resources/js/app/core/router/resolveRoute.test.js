import test from 'node:test';
import assert from 'node:assert/strict';

import { resolveRoute } from './resolveRoute.js';

const repository = {
    async loadAllStudents() {
        return [
            { id: 'mei-lin', name: 'Mei Lin', createdAt: '2026-03-30T00:00:00.000Z', updatedAt: '2026-03-30T00:00:00.000Z' },
        ];
    },
    async loadStudentById(studentId) {
        return studentId === 'mei-lin'
            ? { id: 'mei-lin', name: 'Mei Lin', createdAt: '2026-03-30T00:00:00.000Z', updatedAt: '2026-03-30T00:00:00.000Z' }
            : null;
    },
    async loadEntriesByStudent(studentId) {
        return studentId === 'mei-lin'
            ? [{ id: 'entry-1', studentId: 'mei-lin', date: '2026-03-29', createdAt: '2026-03-30T00:00:00.000Z', updatedAt: '2026-03-30T00:00:00.000Z', note: '' }]
            : [];
    },
    async loadPhotosByEntryIds() {
        return [];
    },
};

test('repairs a route for a missing student id back to the students list', async () => {
    const resolution = await resolveRoute({
        kind: 'student-detail',
        hash: '#/students/missing-student',
        studentId: 'missing-student',
        date: null,
    }, repository);

    assert.deepEqual(resolution, {
        type: 'repair',
        hash: '#/students',
    });
});

test('repairs an unavailable date tab to the first valid date for that student', async () => {
    const resolution = await resolveRoute({
        kind: 'student-detail',
        hash: '#/students/mei-lin/date/2026-04-30',
        studentId: 'mei-lin',
        date: '2026-04-30',
    }, repository);

    assert.deepEqual(resolution, {
        type: 'repair',
        hash: '#/students/mei-lin/date/2026-03-29',
    });
});

test('repairs to the newest remaining date when the selected date group becomes empty after delete', async () => {
    const resolution = await resolveRoute(
        {
            kind: 'student-detail',
            hash: '#/students/mei-lin/date/2026-03-30',
            studentId: 'mei-lin',
            date: '2026-03-30',
        },
        {
            ...repository,
            async loadEntriesByStudent(studentId) {
                return studentId === 'mei-lin'
                    ? [
                          {
                              id: 'entry-2',
                              studentId: 'mei-lin',
                              date: '2026-03-29',
                              createdAt: '2026-03-29T00:00:00.000Z',
                              updatedAt: '2026-03-29T00:00:00.000Z',
                              note: '',
                          },
                      ]
                    : [];
            },
        }
    );

    assert.deepEqual(resolution, {
        type: 'repair',
        hash: '#/students/mei-lin/date/2026-03-29',
    });
});

test('repairs to the student detail route without a date when the last entry overall is deleted', async () => {
    const resolution = await resolveRoute(
        {
            kind: 'student-detail',
            hash: '#/students/mei-lin/date/2026-03-30',
            studentId: 'mei-lin',
            date: '2026-03-30',
        },
        {
            ...repository,
            async loadEntriesByStudent() {
                return [];
            },
        }
    );

    assert.deepEqual(resolution, {
        type: 'repair',
        hash: '#/students/mei-lin',
    });
});
