import test from 'node:test';
import assert from 'node:assert/strict';

import { validatePhotoSelection } from './validatePhotoSelection.js';

test('accepts jpeg, png, and webp files within limits', () => {
    const result = validatePhotoSelection(
        [
            { name: 'a.jpg', type: 'image/jpeg', size: 1024 },
            { name: 'b.png', type: 'image/png', size: 2048 },
            { name: 'c.webp', type: 'image/webp', size: 4096 },
        ],
        0
    );

    assert.equal(result.acceptedFiles.length, 3);
    assert.deepEqual(result.rejectedFiles, []);
});

test('rejects unsupported mime types, oversized files, and overflow beyond six photos', () => {
    const result = validatePhotoSelection(
        [
            { name: 'bad.gif', type: 'image/gif', size: 1024 },
            { name: 'huge.jpg', type: 'image/jpeg', size: 20 * 1024 * 1024 },
            { name: 'ok1.jpg', type: 'image/jpeg', size: 1024 },
            { name: 'ok2.jpg', type: 'image/jpeg', size: 1024 },
        ],
        5
    );

    assert.deepEqual(result.acceptedFiles.map((file) => file.name), ['ok1.jpg']);
    assert.deepEqual(result.rejectedFiles, [
        { fileName: 'bad.gif', message: 'Hanya file JPEG, PNG, dan WebP yang didukung.' },
        { fileName: 'huge.jpg', message: 'Ukuran file maksimal 15 MB.' },
        { fileName: 'ok2.jpg', message: 'Maksimal 6 foto untuk setiap entri.' },
    ]);
});
