const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

export function normalizeStudentRecord(record) {
    if (!isObject(record)) {
        return null;
    }

    const id = normalizeNonEmptyString(record.id);
    const name = normalizeNonEmptyString(record.name);
    const createdAt = normalizeIsoString(record.createdAt);
    const updatedAt = normalizeIsoString(record.updatedAt);

    if (!id || !name || !createdAt || !updatedAt) {
        return null;
    }

    return {
        id,
        name,
        createdAt,
        updatedAt,
    };
}

export function normalizeEntryRecord(record) {
    if (!isObject(record)) {
        return null;
    }

    const id = normalizeNonEmptyString(record.id);
    const studentId = normalizeNonEmptyString(record.studentId);
    const date = normalizeDateString(record.date);
    const note = typeof record.note === 'string' ? record.note : '';
    const createdAt = normalizeIsoString(record.createdAt);
    const updatedAt = normalizeIsoString(record.updatedAt);

    if (!id || !studentId || !date || !createdAt || !updatedAt) {
        return null;
    }

    return {
        id,
        studentId,
        date,
        note,
        createdAt,
        updatedAt,
    };
}

export function normalizePhotoRecord(record) {
    if (!isObject(record)) {
        return null;
    }

    const id = normalizeNonEmptyString(record.id);
    const entryId = normalizeNonEmptyString(record.entryId);
    const createdAt = normalizeIsoString(record.createdAt);

    if (!id || !entryId || !createdAt) {
        return null;
    }

    return {
        id,
        entryId,
        createdAt,
        blob: record.blob ?? null,
        mimeType: typeof record.mimeType === 'string' ? record.mimeType : null,
        width: typeof record.width === 'number' ? record.width : null,
        height: typeof record.height === 'number' ? record.height : null,
        size: typeof record.size === 'number' ? record.size : null,
        thumbnailBlob: record.thumbnailBlob ?? null,
        thumbnailMimeType: typeof record.thumbnailMimeType === 'string' ? record.thumbnailMimeType : null,
        thumbnailWidth: typeof record.thumbnailWidth === 'number' ? record.thumbnailWidth : null,
        thumbnailHeight: typeof record.thumbnailHeight === 'number' ? record.thumbnailHeight : null,
        thumbnailSize: typeof record.thumbnailSize === 'number' ? record.thumbnailSize : null,
        fileName: typeof record.fileName === 'string' ? record.fileName : '',
        sourceMimeType: typeof record.sourceMimeType === 'string' ? record.sourceMimeType : null,
    };
}

export function createStudentWriteRecord(input, now = createNowIsoString()) {
    const id = normalizeNonEmptyString(input.id) ?? createId();
    const name = normalizeNonEmptyString(input.name);

    if (!name) {
        throw new Error('Nama murid wajib diisi.');
    }

    return {
        id,
        name,
        createdAt: normalizeIsoString(input.createdAt) ?? now,
        updatedAt: normalizeIsoString(input.updatedAt) ?? now,
    };
}

export function createEntryWriteRecord(input, now = createNowIsoString()) {
    const id = normalizeNonEmptyString(input.id) ?? createId();
    const studentId = normalizeNonEmptyString(input.studentId);
    const date = normalizeDateString(input.date);

    if (!studentId) {
        throw new Error('ID murid untuk entri wajib ada.');
    }

    if (!date) {
        throw new Error('Tanggal entri harus memakai format yyyy-mm-dd.');
    }

    return {
        id,
        studentId,
        date,
        note: typeof input.note === 'string' ? input.note : '',
        createdAt: normalizeIsoString(input.createdAt) ?? now,
        updatedAt: normalizeIsoString(input.updatedAt) ?? now,
    };
}

export function createNowIsoString() {
    return new Date().toISOString();
}

function normalizeNonEmptyString(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const normalized = value.trim();

    return normalized.length > 0 ? normalized : null;
}

function normalizeIsoString(value) {
    if (typeof value !== 'string') {
        return null;
    }

    const normalizedDate = new Date(value);

    if (Number.isNaN(normalizedDate.getTime())) {
        return null;
    }

    return normalizedDate.toISOString();
}

function normalizeDateString(value) {
    if (typeof value !== 'string' || !DATE_PATTERN.test(value)) {
        return null;
    }

    const normalizedDate = new Date(`${value}T00:00:00Z`);

    return normalizedDate.toISOString().slice(0, 10) === value ? value : null;
}

function isObject(value) {
    return typeof value === 'object' && value !== null;
}

function createId() {
    if (typeof globalThis.crypto?.randomUUID === 'function') {
        return globalThis.crypto.randomUUID();
    }

    return `id-${Date.now()}-${Math.random().toString(16).slice(2)}`;
}
