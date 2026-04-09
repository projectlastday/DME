import test from 'node:test';
import assert from 'node:assert/strict';

import { normalizePhotoRecord } from './normalizers.js';

test('normalizePhotoRecord tolerates missing preview blobs without crashing callers', () => {
    assert.deepEqual(
        normalizePhotoRecord({
            id: 'photo-1',
            entryId: 'entry-1',
            createdAt: '2026-03-30T00:00:00.000Z',
            fileName: 'saved.jpg',
        }),
        {
            id: 'photo-1',
            entryId: 'entry-1',
            createdAt: '2026-03-30T00:00:00.000Z',
            blob: null,
            mimeType: null,
            width: null,
            height: null,
            size: null,
            thumbnailBlob: null,
            thumbnailMimeType: null,
            thumbnailWidth: null,
            thumbnailHeight: null,
            thumbnailSize: null,
            fileName: 'saved.jpg',
            sourceMimeType: null,
        }
    );
});

test('normalizePhotoRecord skips malformed records that are missing required ids', () => {
    assert.equal(
        normalizePhotoRecord({
            id: '',
            entryId: 'entry-1',
            createdAt: '2026-03-30T00:00:00.000Z',
        }),
        null
    );
});
