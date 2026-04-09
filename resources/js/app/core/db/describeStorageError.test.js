import test from 'node:test';
import assert from 'node:assert/strict';

import {
    describeDbInitializationError,
    describeEntryWriteError,
    isQuotaExceededError,
} from './describeStorageError.js';

test('detects quota exceeded errors from common browser shapes', () => {
    assert.equal(isQuotaExceededError({ name: 'QuotaExceededError' }), true);
    assert.equal(isQuotaExceededError({ name: 'NS_ERROR_DOM_QUOTA_REACHED' }), true);
    assert.equal(isQuotaExceededError(new Error('quota exceeded while writing photo blob')), true);
    assert.equal(isQuotaExceededError(new Error('generic write failure')), false);
});

test('describes db initialization failures for unsupported and thrown cases', () => {
    assert.match(
        describeDbInitializationError({ isSupported: false }),
        /IndexedDB tidak tersedia/i
    );
    assert.equal(
        describeDbInitializationError(new Error('open failed')),
        'open failed'
    );
});

test('describes quota entry write failures with the user-facing storage full message', () => {
    assert.equal(
        describeEntryWriteError({ name: 'QuotaExceededError' }),
        'Penyimpanan perangkat penuh. Hapus beberapa entri atau foto, lalu coba simpan lagi.'
    );
});
