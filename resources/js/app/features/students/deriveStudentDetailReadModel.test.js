import test from 'node:test';
import assert from 'node:assert/strict';

import { deriveStudentDetailReadModel } from './deriveStudentDetailReadModel.js';

test('selected date defaults to the newest available date when the route has no date', () => {
    const readModel = deriveStudentDetailReadModel([
        {
            id: 'entry-1',
            studentId: 'mei-lin',
            date: '2026-03-20',
            createdAt: '2026-03-20T08:00:00.000Z',
            updatedAt: '2026-03-20T08:00:00.000Z',
            note: 'Older note',
        },
        {
            id: 'entry-2',
            studentId: 'mei-lin',
            date: '2026-03-29',
            createdAt: '2026-03-29T09:00:00.000Z',
            updatedAt: '2026-03-29T09:00:00.000Z',
            note: 'Newest note',
        },
    ]);

    assert.equal(readModel.selectedDateTab, '2026-03-29');
    assert.deepEqual(readModel.availableDates, ['2026-03-29', '2026-03-20']);
});

test('groups entries by date and sorts groups newest first', () => {
    const readModel = deriveStudentDetailReadModel([
        {
            id: 'entry-1',
            studentId: 'mei-lin',
            date: '2026-03-29',
            createdAt: '2026-03-29T08:00:00.000Z',
            updatedAt: '2026-03-29T08:00:00.000Z',
            note: 'Earlier note',
        },
        {
            id: 'entry-2',
            studentId: 'mei-lin',
            date: '2026-03-29',
            createdAt: '2026-03-29T10:00:00.000Z',
            updatedAt: '2026-03-29T10:00:00.000Z',
            note: 'Later note',
        },
        {
            id: 'entry-3',
            studentId: 'mei-lin',
            date: '2026-03-15',
            createdAt: '2026-03-15T09:00:00.000Z',
            updatedAt: '2026-03-15T09:00:00.000Z',
            note: 'Older group',
        },
    ]);

    assert.deepEqual(
        readModel.dateGroups.map((group) => group.date),
        ['2026-03-29', '2026-03-15']
    );
    assert.deepEqual(
        readModel.dateGroups[0].entries.map((entry) => entry.id),
        ['entry-2', 'entry-1']
    );
});
