import test from 'node:test';
import assert from 'node:assert/strict';

import { parseHashRoute } from './parseHashRoute.js';

test('parses the students list route', () => {
    assert.deepEqual(parseHashRoute('#/students'), {
        kind: 'student-list',
        hash: '#/students',
    });
});

test('parses a student detail route without a date', () => {
    assert.deepEqual(parseHashRoute('#/students/mei-lin'), {
        kind: 'student-detail',
        hash: '#/students/mei-lin',
        studentId: 'mei-lin',
        date: null,
    });
});

test('parses a student detail route with a date', () => {
    assert.deepEqual(parseHashRoute('#/students/mei-lin/date/2026-03-29'), {
        kind: 'student-detail',
        hash: '#/students/mei-lin/date/2026-03-29',
        studentId: 'mei-lin',
        date: '2026-03-29',
    });
});

test('repairs invalid hashes through the invalid route result', () => {
    assert.deepEqual(parseHashRoute('#/oops'), {
        kind: 'invalid',
        reason: 'unmatched-route',
        hash: '#/students',
    });
});

test('rejects impossible date values', () => {
    assert.deepEqual(parseHashRoute('#/students/mei-lin/date/2026-02-30'), {
        kind: 'invalid',
        reason: 'unmatched-route',
        hash: '#/students',
    });
});
