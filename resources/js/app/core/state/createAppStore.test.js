import test from 'node:test';
import assert from 'node:assert/strict';

import { createAppStore } from './createAppStore.js';

test('route resolution sets the student list view state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'DB_INITIALIZED',
        payload: {
            isReady: true,
            isSupported: true,
        },
    });

    store.dispatch({
        type: 'ROUTE_RESOLVED',
        payload: {
            route: {
                kind: 'student-list',
                hash: '#/students',
            },
            routeData: {
                students: [{ id: 'mei-lin' }],
                selectedStudent: null,
                availableDates: [],
                dateGroups: [],
                selectedEntries: [],
            },
        },
    });

    assert.equal(store.getState().currentView, 'students');
    assert.equal(store.getState().selectedStudentId, null);
    assert.equal(store.getState().selectedDateTab, null);
});

test('db initialization failure moves the app into the fatal retry screen', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'DB_INITIALIZATION_FAILED',
        payload: 'IndexedDB tidak tersedia.',
    });

    assert.equal(store.getState().currentView, 'fatal-db-error');
    assert.equal(store.getState().fatalError, 'IndexedDB tidak tersedia.');
    assert.equal(store.getState().db.repository, null);
});

test('db retry request clears the fatal db state and returns to boot', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'DB_INITIALIZATION_FAILED',
        payload: 'IndexedDB tidak tersedia.',
    });
    store.dispatch({
        type: 'DB_RETRY_REQUESTED',
    });

    assert.equal(store.getState().currentView, 'boot');
    assert.equal(store.getState().fatalError, null);
    assert.equal(store.getState().asyncFlags.isBooting, true);
});

test('route resolution sets the student detail view state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'ROUTE_RESOLVED',
        payload: {
            route: {
                kind: 'student-detail',
                hash: '#/students/mei-lin/date/2026-03-29',
                studentId: 'mei-lin',
                date: '2026-03-29',
            },
            routeData: {
                students: [{ id: 'mei-lin' }],
                selectedStudent: { id: 'mei-lin', name: 'Mei Lin' },
                availableDates: ['2026-03-29'],
                dateGroups: [],
                selectedEntries: [],
            },
        },
    });

    assert.equal(store.getState().currentView, 'student-detail');
    assert.equal(store.getState().selectedStudentId, 'mei-lin');
    assert.equal(store.getState().selectedDateTab, '2026-03-29');
    assert.equal(store.getState().routeData.selectedStudent.name, 'Mei Lin');
});

test('moving back to the students list clears selected detail state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'ROUTE_RESOLVED',
        payload: {
            route: {
                kind: 'student-detail',
                hash: '#/students/mei-lin/date/2026-03-29',
                studentId: 'mei-lin',
                date: '2026-03-29',
            },
            routeData: {
                students: [{ id: 'mei-lin' }],
                selectedStudent: { id: 'mei-lin', name: 'Mei Lin' },
                availableDates: ['2026-03-29'],
                dateGroups: [],
                selectedEntries: [],
            },
        },
    });

    store.dispatch({
        type: 'ROUTE_RESOLVED',
        payload: {
            route: {
                kind: 'student-list',
                hash: '#/students',
            },
            routeData: {
                students: [{ id: 'mei-lin' }],
                selectedStudent: null,
                availableDates: [],
                dateGroups: [],
                selectedEntries: [],
            },
        },
    });

    assert.equal(store.getState().currentView, 'students');
    assert.equal(store.getState().selectedStudentId, null);
    assert.equal(store.getState().selectedDateTab, null);
});

test('search query updates without mutating route state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'SEARCH_QUERY_CHANGED',
        payload: 'mei',
    });

    assert.equal(store.getState().searchQuery, 'mei');
    assert.equal(store.getState().route, null);
});

test('create student validation error is stored in centralized edit state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'CREATE_STUDENT_FAILED',
        payload: 'Nama murid wajib diisi.',
    });

    assert.equal(store.getState().editState.addStudentError, 'Nama murid wajib diisi.');
    assert.equal(store.getState().asyncFlags.isCreatingStudent, false);
});

test('current student delete resets detail selection state', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'ROUTE_RESOLVED',
        payload: {
            route: {
                kind: 'student-detail',
                hash: '#/students/mei-lin',
                studentId: 'mei-lin',
                date: null,
            },
            routeData: {
                students: [{ id: 'mei-lin' }, { id: 'sam-wei' }],
                selectedStudent: { id: 'mei-lin', name: 'Mei Lin' },
                availableDates: [],
                dateGroups: [],
                selectedEntries: [],
            },
        },
    });

    store.dispatch({
        type: 'CURRENT_STUDENT_DELETED',
        payload: {
            studentId: 'mei-lin',
        },
    });

    assert.equal(store.getState().currentView, 'students');
    assert.equal(store.getState().selectedStudentId, null);
    assert.equal(store.getState().selectedDateTab, null);
    assert.equal(store.getState().route.hash, '#/students');
    assert.deepEqual(
        store.getState().routeData.students.map((student) => student.id),
        ['sam-wei']
    );
});

test('rename dialog state stores student context and clears after success', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'RENAME_STUDENT_DIALOG_OPENED',
        payload: {
            studentId: 'mei-lin',
            studentName: 'Mei Lin',
        },
    });

    store.dispatch({
        type: 'RENAME_STUDENT_STARTED',
    });

    store.dispatch({
        type: 'RENAME_STUDENT_SUCCEEDED',
    });

    assert.equal(store.getState().editState.renameStudentDialogOpen, false);
    assert.equal(store.getState().editState.renameStudentId, null);
    assert.equal(store.getState().asyncFlags.isRenamingStudent, false);
});

test('photo processing appends pending photos and stores file-specific errors', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'PHOTO_PROCESSING_STARTED',
    });

    store.dispatch({
        type: 'PHOTO_PROCESSING_COMPLETED',
        payload: {
            pendingPhotos: [{ id: 'photo-1' }],
            errors: [{ fileName: 'bad.gif', message: 'Hanya file JPEG, PNG, dan WebP yang didukung.' }],
        },
    });

    assert.equal(store.getState().pendingPhotos.length, 1);
    assert.equal(store.getState().editState.photoProcessingErrors.length, 1);
    assert.equal(store.getState().asyncFlags.isProcessingPhotos, false);
});

test('successful entry create clears draft and pending photos', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'DRAFT_ENTRY_NOTE_CHANGED',
        payload: 'Sentence building',
    });
    store.dispatch({
        type: 'PHOTO_PROCESSING_COMPLETED',
        payload: {
            pendingPhotos: [{ id: 'photo-1' }],
            errors: [],
        },
    });
    store.dispatch({
        type: 'ENTRY_CREATE_SUCCEEDED',
    });

    assert.equal(store.getState().draftEntry.note, '');
    assert.equal(store.getState().pendingPhotos.length, 0);
    assert.equal(store.getState().asyncFlags.isSavingEntry, false);
});

test('failed entry create preserves draft and pending photos', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'DRAFT_ENTRY_NOTE_CHANGED',
        payload: 'Keep me',
    });
    store.dispatch({
        type: 'PHOTO_PROCESSING_COMPLETED',
        payload: {
            pendingPhotos: [{ id: 'photo-1' }],
            errors: [],
        },
    });
    store.dispatch({
        type: 'ENTRY_CREATE_FAILED',
        payload: 'Entri gagal disimpan.',
    });

    assert.equal(store.getState().draftEntry.note, 'Keep me');
    assert.equal(store.getState().pendingPhotos.length, 1);
    assert.equal(store.getState().editState.entryCreateError, 'Entri gagal disimpan.');
});

test('entry edit state populates from a persisted entry and clears on cancel', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'ENTRY_EDIT_STARTED',
        payload: {
            entryId: 'entry-1',
            entryDate: '2026-03-30',
            note: 'Existing note',
            retainedPhotos: [{ id: 'photo-1', previewUrl: 'blob:photo-1' }],
        },
    });

    assert.equal(store.getState().editState.isEditing, true);
    assert.equal(store.getState().draftEntry.note, 'Existing note');
    assert.equal(store.getState().editState.retainedEntryPhotos.length, 1);

    store.dispatch({
        type: 'ENTRY_EDIT_CANCELLED',
    });

    assert.equal(store.getState().editState.isEditing, false);
    assert.equal(store.getState().draftEntry.note, '');
    assert.equal(store.getState().editState.retainedEntryPhotos.length, 0);
});

test('entry delete success clears edit state and pending photos', () => {
    const store = createAppStore();

    store.dispatch({
        type: 'ENTRY_EDIT_STARTED',
        payload: {
            entryId: 'entry-1',
            entryDate: '2026-03-30',
            note: 'Existing note',
            retainedPhotos: [{ id: 'photo-1', previewUrl: 'blob:photo-1' }],
        },
    });
    store.dispatch({
        type: 'PHOTO_PROCESSING_COMPLETED',
        payload: {
            pendingPhotos: [{ id: 'pending-1' }],
            errors: [],
        },
    });
    store.dispatch({
        type: 'DELETE_ENTRY_DIALOG_OPENED',
        payload: {
            entryId: 'entry-1',
            entryDate: '2026-03-30',
        },
    });
    store.dispatch({
        type: 'DELETE_ENTRY_SUCCEEDED',
    });

    assert.equal(store.getState().editState.isEditing, false);
    assert.equal(store.getState().editState.deleteEntryDialogOpen, false);
    assert.equal(store.getState().pendingPhotos.length, 0);
});
