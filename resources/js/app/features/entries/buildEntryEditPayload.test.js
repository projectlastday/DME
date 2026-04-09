import test from 'node:test';
import assert from 'node:assert/strict';

import { buildEntryEditPayload } from './buildEntryEditPayload.js';

test('builds an edit payload that retains existing photos and new pending photos', () => {
    assert.deepEqual(
        buildEntryEditPayload({
            draftEntry: { note: 'Updated note' },
            retainedPhotos: [{ id: 'saved-1' }, { id: 'saved-2' }],
            pendingPhotos: [{ id: 'pending-1' }],
        }),
        {
            isValid: true,
            message: null,
            note: 'Updated note',
            retainedPhotoIds: ['saved-1', 'saved-2'],
            pendingPhotos: [{ id: 'pending-1' }],
        }
    );
});

test('allows an edited payload with blank note when at least one existing photo is retained', () => {
    assert.equal(
        buildEntryEditPayload({
            draftEntry: { note: '   ' },
            retainedPhotos: [{ id: 'saved-1' }],
            pendingPhotos: [],
        }).isValid,
        true
    );
});

test('blocks an edited payload when note, retained photos, and pending photos are all empty', () => {
    assert.equal(
        buildEntryEditPayload({
            draftEntry: { note: '   ' },
            retainedPhotos: [],
            pendingPhotos: [],
        }).isValid,
        false
    );
});
