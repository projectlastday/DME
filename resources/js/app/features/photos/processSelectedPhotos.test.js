import test from 'node:test';
import assert from 'node:assert/strict';

import { processImageFile, processSelectedPhotos } from './processSelectedPhotos.js';

test('processes photos sequentially and preserves input order for accepted files', async () => {
    const calls = [];
    const adapter = createMockAdapter({
        renderResultFactory({ quality, maxWidth }) {
            calls.push(`${maxWidth}:${quality}`);
            return {
                blob: { size: 1000, type: 'image/jpeg' },
                width: maxWidth,
                height: maxWidth,
            };
        },
    });

    const result = await processSelectedPhotos({
        files: [
            { name: 'first.jpg', type: 'image/jpeg', size: 1000 },
            { name: 'second.jpg', type: 'image/jpeg', size: 1000 },
        ],
        existingPendingPhotos: [],
        adapter,
    });

    assert.deepEqual(result.pendingPhotos.map((photo) => photo.fileName), ['first.jpg', 'second.jpg']);
    assert.deepEqual(calls.slice(0, 2), ['320:0.72', '320:0.72']);
});

test('keeps the original full image and retries thumbnail compression when primary output exceeds limits', async () => {
    const adapter = createMockAdapter({
        renderResultFactory({ quality, maxWidth }) {
            if (maxWidth === 320 && quality === 0.72) {
                return { blob: { size: 140_000, type: 'image/jpeg', name: 'retry.jpg' }, width: 320, height: 240 };
            }

            return {
                blob: {
                    size: 80_000,
                    type: 'image/jpeg',
                    name: 'retry.jpg',
                },
                width: maxWidth,
                height: 240,
            };
        },
        objectUrlFactory(fileName) {
            return `blob:${fileName}`;
        },
    });

    const result = await processImageFile({
        file: { name: 'retry.jpg', type: 'image/jpeg', size: 1_500_000 },
        adapter,
    });

    assert.equal(result.fullImage.size, 1_500_000);
    assert.equal(result.fullImage.width, 2000);
    assert.equal(result.fullImage.height, 1500);
    assert.equal(result.thumbnail.size, 80_000);
    assert.equal(result.thumbnail.previewUrl, 'blob:retry.jpg');
});

test('returns photo-specific errors for processing failures and selection rejections', async () => {
    const adapter = createMockAdapter({
        decodeShouldFailFor: 'bad.jpg',
    });

    const result = await processSelectedPhotos({
        files: [
            { name: 'bad.jpg', type: 'image/jpeg', size: 1000 },
            { name: 'wrong.gif', type: 'image/gif', size: 1000 },
        ],
        existingPendingPhotos: [],
        adapter,
    });

    assert.equal(result.pendingPhotos.length, 0);
    assert.deepEqual(result.errors, [
        { fileName: 'wrong.gif', message: 'Hanya file JPEG, PNG, dan WebP yang didukung.' },
        { fileName: 'bad.jpg', message: 'Gagal membaca gambar "bad.jpg".' },
    ]);
});

function createMockAdapter({ renderResultFactory = defaultRenderResultFactory, decodeShouldFailFor = null, objectUrlFactory = () => 'blob:photo' } = {}) {
    return {
        async decodeFile(file) {
            if (decodeShouldFailFor === file.name) {
                throw new Error(`Gagal membaca gambar "${file.name}".`);
            }

            return {
                width: 2000,
                height: 1500,
                source: file.name,
                close() {},
            };
        },
        async renderJpeg(options) {
            return renderResultFactory(options);
        },
        createObjectUrl(blob) {
            return objectUrlFactory(blob.name ?? 'photo');
        },
    };
}

function defaultRenderResultFactory({ maxWidth }) {
    return {
        blob: { size: maxWidth === 1600 ? 800_000 : 60_000, type: 'image/jpeg', name: 'photo' },
        width: maxWidth,
        height: maxWidth === 1600 ? 1200 : 240,
    };
}
