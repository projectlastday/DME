import test from 'node:test';
import assert from 'node:assert/strict';

import { deriveStudentList } from './deriveStudentList.js';

test('sorts students alphabetically by name', () => {
    const result = deriveStudentList(
        [
            { id: '2', name: 'Sam Wei' },
            { id: '1', name: 'Mei Lin' },
            { id: '3', name: 'Anya Zhou' },
        ],
        ''
    );

    assert.deepEqual(
        result.map((student) => student.name),
        ['Anya Zhou', 'Mei Lin', 'Sam Wei']
    );
});

test('filters students immediately by partial search query', () => {
    const result = deriveStudentList(
        [
            { id: '1', name: 'Mei Lin' },
            { id: '2', name: 'Sam Wei' },
            { id: '3', name: 'Lina Park' },
        ],
        'lin'
    );

    assert.deepEqual(
        result.map((student) => student.name),
        ['Lina Park', 'Mei Lin']
    );
});

test('renamed student values participate in sorting and search derivation', () => {
    const result = deriveStudentList(
        [
            { id: '1', name: 'Mei Lin Chen' },
            { id: '2', name: 'Sam Wei' },
            { id: '3', name: 'Anya Zhou' },
        ],
        'chen'
    );

    assert.deepEqual(
        result.map((student) => student.name),
        ['Mei Lin Chen']
    );
});
