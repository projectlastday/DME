import test from 'node:test';
import assert from 'node:assert/strict';

import { validateEntryDraft } from './validateEntryDraft.js';

test('blocks an empty entry with no note and no photos', () => {
    assert.deepEqual(
        validateEntryDraft({
            draftEntry: { note: '   ' },
            pendingPhotos: [],
        }),
        {
            isValid: false,
            message: 'Tambahkan catatan, foto, atau keduanya sebelum menyimpan entri.',
            normalizedNote: '',
        }
    );
});

test('allows note-only, photo-only, and mixed entries', () => {
    assert.equal(
        validateEntryDraft({
            draftEntry: { note: 'Vocabulary practice' },
            pendingPhotos: [],
        }).isValid,
        true
    );

    assert.equal(
        validateEntryDraft({
            draftEntry: { note: '' },
            pendingPhotos: [{ id: 'photo-1' }],
        }).isValid,
        true
    );

    assert.equal(
        validateEntryDraft({
            draftEntry: { note: 'Sentence building' },
            pendingPhotos: [{ id: 'photo-1' }],
        }).isValid,
        true
    );
});

test('allows an edited draft with retained existing photos and no new note', () => {
    assert.equal(
        validateEntryDraft({
            draftEntry: { note: '   ' },
            pendingPhotos: [],
            retainedPhotoCount: 1,
        }).isValid,
        true
    );
});

test('enforces the 5000 character note limit', () => {
    const tooLong = 'a'.repeat(5001);

    assert.deepEqual(
        validateEntryDraft({
            draftEntry: { note: tooLong },
            pendingPhotos: [],
        }),
        {
            isValid: false,
            message: 'Catatan maksimal 5000 karakter.',
            normalizedNote: tooLong,
        }
    );
});
