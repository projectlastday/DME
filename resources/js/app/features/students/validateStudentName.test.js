import test from 'node:test';
import assert from 'node:assert/strict';

import { validateStudentName } from './validateStudentName.js';

test('rejects an empty student name', () => {
    assert.deepEqual(validateStudentName('   '), {
        isValid: false,
        message: 'Nama murid wajib diisi.',
        normalizedName: '',
    });
});

test('accepts and trims a valid student name', () => {
    assert.deepEqual(validateStudentName('  Mei Lin  '), {
        isValid: true,
        message: null,
        normalizedName: 'Mei Lin',
    });
});
