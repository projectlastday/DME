import {
    FULL_IMAGE_MAX_HEIGHT,
    FULL_IMAGE_MAX_WIDTH,
    THUMBNAIL_MAX_HEIGHT,
    THUMBNAIL_MAX_WIDTH,
} from './photoConstants.js';

export function createBrowserPhotoProcessorAdapter() {
    return {
        async decodeFile(file) {
            const imageBitmap = typeof createImageBitmap === 'function' ? await createImageBitmap(file) : await loadImage(file);

            return {
                width: imageBitmap.width,
                height: imageBitmap.height,
                source: imageBitmap,
                close() {
                    if (typeof imageBitmap.close === 'function') {
                        imageBitmap.close();
                    }
                },
            };
        },

        async renderJpeg({ image, maxWidth, maxHeight, quality }) {
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            if (!context) {
                throw new Error('Kanvas 2D tidak tersedia.');
            }

            const dimensions = fitWithinBounds(image.width, image.height, maxWidth, maxHeight);
            canvas.width = dimensions.width;
            canvas.height = dimensions.height;
            context.drawImage(image.source, 0, 0, dimensions.width, dimensions.height);

            const blob = await canvasToBlob(canvas, quality);

            return {
                blob,
                width: dimensions.width,
                height: dimensions.height,
            };
        },

        createObjectUrl(blob) {
            return URL.createObjectURL(blob);
        },

        revokeObjectUrl(url) {
            URL.revokeObjectURL(url);
        },
    };
}

function canvasToBlob(canvas, quality) {
    return new Promise((resolve, reject) => {
        canvas.toBlob(
            (blob) => {
                if (!blob) {
                    reject(new Error('Blob JPEG gagal dibuat.'));
                    return;
                }

                resolve(blob);
            },
            'image/jpeg',
            quality
        );
    });
}

function loadImage(file) {
    const objectUrl = URL.createObjectURL(file);

    return new Promise((resolve, reject) => {
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(objectUrl);
            resolve(image);
        };

        image.onerror = () => {
            URL.revokeObjectURL(objectUrl);
            reject(new Error(`Gagal membaca gambar "${file.name}".`));
        };

        image.src = objectUrl;
    });
}

function fitWithinBounds(width, height, maxWidth, maxHeight) {
    const widthRatio = maxWidth / width;
    const heightRatio = maxHeight / height;
    const ratio = Math.min(1, widthRatio, heightRatio);

    return {
        width: Math.max(1, Math.round(width * ratio)),
        height: Math.max(1, Math.round(height * ratio)),
    };
}

export const PHOTO_DIMENSION_LIMITS = {
    full: {
        maxWidth: FULL_IMAGE_MAX_WIDTH,
        maxHeight: FULL_IMAGE_MAX_HEIGHT,
    },
    thumbnail: {
        maxWidth: THUMBNAIL_MAX_WIDTH,
        maxHeight: THUMBNAIL_MAX_HEIGHT,
    },
};
