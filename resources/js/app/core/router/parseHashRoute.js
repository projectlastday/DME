import { buildStudentsHash } from './buildHashRoute.js';

const STUDENT_ID_PATTERN = /^[A-Za-z0-9_-]+$/;
const DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

export function parseHashRoute(hash) {
    if (!hash || hash === '#' || hash === '#/') {
        return invalidRoute('missing-hash');
    }

    const normalizedHash = hash.startsWith('#') ? hash : `#${hash}`;
    const path = normalizedHash.slice(1);
    const segments = path.split('/').filter(Boolean);

    if (segments.length === 1 && segments[0] === 'students') {
        return {
            kind: 'student-list',
            hash: buildStudentsHash(),
        };
    }

    if (segments.length === 2 && segments[0] === 'students' && isValidStudentId(segments[1])) {
        return {
            kind: 'student-detail',
            hash: `#/students/${segments[1]}`,
            studentId: segments[1],
            date: null,
        };
    }

    if (
        segments.length === 4 &&
        segments[0] === 'students' &&
        isValidStudentId(segments[1]) &&
        segments[2] === 'date' &&
        isValidDate(segments[3])
    ) {
        return {
            kind: 'student-detail',
            hash: `#/students/${segments[1]}/date/${segments[3]}`,
            studentId: segments[1],
            date: segments[3],
        };
    }

    return invalidRoute('unmatched-route');
}

function invalidRoute(reason) {
    return {
        kind: 'invalid',
        reason,
        hash: buildStudentsHash(),
    };
}

function isValidStudentId(studentId) {
    return STUDENT_ID_PATTERN.test(studentId);
}

function isValidDate(value) {
    if (!DATE_PATTERN.test(value)) {
        return false;
    }

    const normalized = new Date(`${value}T00:00:00Z`);

    return normalized.toISOString().slice(0, 10) === value;
}
