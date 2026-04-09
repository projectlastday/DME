import { initAppDb } from '../db/initAppDb.js';
import { describeDbInitializationError, describeEntryWriteError } from '../db/describeStorageError.js';
import { buildStudentDetailHash, buildStudentsHash } from '../router/buildHashRoute.js';
import { parseHashRoute } from '../router/parseHashRoute.js';
import { resolveRoute } from '../router/resolveRoute.js';
import { renderApp } from '../../features/app/renderApp.js';
import { buildEntryEditPayload } from '../../features/entries/buildEntryEditPayload.js';
import { createEntryWithPhotosTransaction } from '../../features/entries/createEntryWithPhotosTransaction.js';
import { updateEntryWithPhotosTransaction } from '../../features/entries/updateEntryWithPhotosTransaction.js';
import { validateEntryDraft } from '../../features/entries/validateEntryDraft.js';
import { createBrowserPhotoProcessorAdapter } from '../../features/photos/browserPhotoProcessor.js';
import { processSelectedPhotos } from '../../features/photos/processSelectedPhotos.js';
import { validateStudentName } from '../../features/students/validateStudentName.js';

export function createAppController({ shell, store, windowObject }) {
    const photoProcessorAdapter = createBrowserPhotoProcessorAdapter();
    let activeStoredPhotoUrls = [];
    const handleHashChange = () => {
        syncRoute(windowObject.location.hash);
    };

    const handleClick = (event) => {
        const navigationTarget = event.target.closest('[data-route-hash]');

        if (!navigationTarget) {
            return;
        }

        event.preventDefault();

        navigateToHash(navigationTarget.dataset.routeHash, {
            replace: navigationTarget.dataset.routeHistory === 'replace',
        });
    };

    const handleInput = (event) => {
        const searchInput = event.target.closest('[data-search-input]');
        const entryNoteInput = event.target.closest('[data-entry-note-input]');

        // Search gets immediate dispatch because we want live filtering as you type
        if (searchInput) {
            store.dispatch({
                type: 'SEARCH_QUERY_CHANGED',
                payload: searchInput.value,
            });
        }
        
        // Note gets immediate dispatch because we need character counts to be live
        if (entryNoteInput) {
            store.dispatch({
                type: 'DRAFT_ENTRY_NOTE_CHANGED',
                payload: entryNoteInput.value,
            });
        }
        
        // NO DISPATCH FOR student inputs. They are uncontrolled. We will read the DOM value on form submit!
    };

    const handleChange = (event) => {
        const photoInput = event.target.closest('[data-photo-input]');

        if (!photoInput) {
            return;
        }

        const files = [...(photoInput.files ?? [])];
        processPhotos(files);
        photoInput.value = '';
    };

    const handleSubmit = (event) => {
        const addStudentForm = event.target.closest('[data-add-student-form]');
        const renameStudentForm = event.target.closest('[data-rename-student-form]');
        const deleteStudentForm = event.target.closest('[data-delete-student-form]');
        const entryComposerForm = event.target.closest('[data-entry-composer-form]');
        const deleteEntryForm = event.target.closest('[data-delete-entry-form]');

        if (!addStudentForm) {
            if (!renameStudentForm && !deleteStudentForm && !entryComposerForm && !deleteEntryForm) {
                return;
            }
        }

        event.preventDefault();

        if (addStudentForm) {
            createStudent(addStudentForm);
        }

        if (renameStudentForm) {
            renameStudent(renameStudentForm);
        }

        if (deleteStudentForm) {
            deleteStudent();
        }

        if (entryComposerForm) {
            submitEntryComposer();
        }

        if (deleteEntryForm) {
            deleteEntry();
        }
    };

    const handleActionClick = (event) => {
        const actionTarget = event.target.closest('[data-action]');

        if (!actionTarget) {
            return;
        }

        const action = actionTarget.dataset.action;

        if (action === 'open-add-student-dialog') {
            store.dispatch({
                type: 'ADD_STUDENT_DIALOG_OPENED',
            });
        }

        if (action === 'close-add-student-dialog') {
            store.dispatch({
                type: 'ADD_STUDENT_DIALOG_CLOSED',
            });
        }

        if (action === 'open-rename-student-dialog') {
            store.dispatch({
                type: 'RENAME_STUDENT_DIALOG_OPENED',
                payload: {
                    studentId: actionTarget.dataset.studentId,
                    studentName: actionTarget.dataset.studentName ?? '',
                },
            });
        }

        if (action === 'close-rename-student-dialog') {
            store.dispatch({
                type: 'RENAME_STUDENT_DIALOG_CLOSED',
            });
        }

        if (action === 'open-delete-student-dialog') {
            store.dispatch({
                type: 'DELETE_STUDENT_DIALOG_OPENED',
                payload: {
                    studentId: actionTarget.dataset.studentId,
                    studentName: actionTarget.dataset.studentName ?? '',
                },
            });
        }

        if (action === 'close-delete-student-dialog') {
            store.dispatch({
                type: 'DELETE_STUDENT_DIALOG_CLOSED',
            });
        }

        if (action === 'remove-pending-photo') {
            const photoId = actionTarget.dataset.photoId;
            const photo = store.getState().pendingPhotos.find((item) => item.id === photoId);

            if (photo?.thumbnail?.previewUrl) {
                photoProcessorAdapter.revokeObjectUrl(photo.thumbnail.previewUrl);
            }

            store.dispatch({
                type: 'PENDING_PHOTO_REMOVED',
                payload: photoId,
            });
        }

        if (action === 'start-entry-edit') {
            startEntryEdit(actionTarget.dataset.entryId);
        }

        if (action === 'cancel-entry-edit') {
            cancelEntryEdit();
        }

        if (action === 'remove-retained-entry-photo') {
            const photo = store
                .getState()
                .editState.retainedEntryPhotos.find((item) => item.id === actionTarget.dataset.photoId);

            if (photo?.previewUrl) {
                photoProcessorAdapter.revokeObjectUrl(photo.previewUrl);
            }

            store.dispatch({
                type: 'RETAINED_ENTRY_PHOTO_REMOVED',
                payload: actionTarget.dataset.photoId,
            });
        }

        if (action === 'open-delete-entry-dialog') {
            store.dispatch({
                type: 'DELETE_ENTRY_DIALOG_OPENED',
                payload: {
                    entryId: actionTarget.dataset.entryId,
                    entryDate: actionTarget.dataset.entryDate,
                },
            });
        }

        if (action === 'close-delete-entry-dialog') {
            store.dispatch({
                type: 'DELETE_ENTRY_DIALOG_CLOSED',
            });
        }

        if (action === 'expand-composer') {
            expandComposer(actionTarget.dataset.mode);
        }

        if (action === 'close-composer') {
            store.dispatch({
                type: 'COMPOSER_COLLAPSED',
            });
        }

        if (action === 'retry-db-initialization') {
            retryDbInitialization();
        }

        if (action === 'open-photo-viewer') {
            store.dispatch({
                type: 'PHOTO_VIEWER_OPENED',
                payload: actionTarget.dataset.photoUrl,
            });
        }

        if (action === 'close-photo-viewer') {
            store.dispatch({
                type: 'PHOTO_VIEWER_CLOSED',
            });
        }
    };

    async function start() {
        shell.root.addEventListener('click', handleClick);
        shell.root.addEventListener('click', handleActionClick);
        shell.root.addEventListener('input', handleInput);
        shell.root.addEventListener('change', handleChange);
        shell.root.addEventListener('submit', handleSubmit);
        windowObject.addEventListener('hashchange', handleHashChange);

        store.subscribe((state) => {
            renderApp({
                shell,
                state,
            });
        });

        store.dispatch({
            type: 'BOOT_STARTED',
        });

        await initializeDbAndRoute();
    }

    async function syncRoute(hash) {
        try {
            store.dispatch({
                type: 'ROUTE_LOADING',
            });

            const parsedRoute = parseHashRoute(hash);
            const resolution = await resolveRoute(parsedRoute, store.getState().db.repository);

            if (resolution.type === 'repair') {
                navigateToHash(resolution.hash, {
                    replace: true,
                });
                return;
            }

            if (resolution.type === 'resolved' && resolution.routeData?.selectedEntries) {
                // Cleanup old stored photo URLs first to prevent leaks
                activeStoredPhotoUrls.forEach((url) => {
                    if (url) photoProcessorAdapter.revokeObjectUrl(url);
                });
                activeStoredPhotoUrls = [];

                // Generate thumbnail and full-image URLs for all photos in the current timeline.
                resolution.routeData.selectedEntries.forEach((entry) => {
                    (entry.photos || []).forEach((photo) => {
                        const previewUrl = createStoredPhotoPreviewUrl(photo);
                        const fullPhotoUrl = createStoredPhotoFullUrl(photo);

                        if (previewUrl) {
                            photo.previewUrl = previewUrl;
                            activeStoredPhotoUrls.push(previewUrl);
                        }

                        if (fullPhotoUrl) {
                            photo.fullPhotoUrl = fullPhotoUrl;
                            activeStoredPhotoUrls.push(fullPhotoUrl);
                        }
                    });
                });
            }

            store.dispatch({
                type: 'ROUTE_RESOLVED',
                payload: resolution,
            });
        } catch (error) {
            handleFatalDbError(error);
        }
    }

    function navigateToHash(hash, { replace = false } = {}) {
        if (!hash) {
            return;
        }

        const currentHash = windowObject.location.hash;

        if (replace) {
            windowObject.history.replaceState(null, '', hash);
            syncRoute(hash);
            return;
        }

        if (currentHash === hash) {
            syncRoute(hash);
            return;
        }

        windowObject.location.hash = hash;
    }

    async function createStudent(addStudentForm) {
        const { db } = store.getState();
        
        // Retrieve the uncontrolled input value
        const inputRawValue = addStudentForm.querySelector('[data-add-student-name-input]')?.value || '';
        
        // Commit to state before proceeding so that errors don't wipe the DOM string
        store.dispatch({
            type: 'ADD_STUDENT_NAME_CHANGED',
            payload: inputRawValue,
        });
        
        const validation = validateStudentName(inputRawValue);

        if (!validation.isValid) {
            store.dispatch({
                type: 'CREATE_STUDENT_FAILED',
                payload: validation.message,
            });
            return;
        }

        if (!db.repository) {
            store.dispatch({
                type: 'CREATE_STUDENT_FAILED',
                payload: 'Penyimpanan murid tidak tersedia.',
            });
            return;
        }

        store.dispatch({
            type: 'CREATE_STUDENT_STARTED',
        });

        try {
            const student = await db.repository.createStudent({
                name: validation.normalizedName,
            });

            store.dispatch({
                type: 'CREATE_STUDENT_SUCCEEDED',
            });

            if (store.getState().route?.kind === 'student-list') {
                await syncRoute(buildStudentsHash());
            }
        } catch (error) {
            store.dispatch({
                type: 'CREATE_STUDENT_FAILED',
                payload: error instanceof Error ? error.message : 'Murid gagal dibuat.',
            });

            await recoverAffectedScope(buildStudentsHash());
        }
    }

    async function renameStudent(renameStudentForm) {
        const { db, editState, route } = store.getState();
        
        // Retrieve the uncontrolled input value
        const inputRawValue = renameStudentForm.querySelector('[data-rename-student-name-input]')?.value || '';
        
        // Commit to state before proceeding so that errors don't wipe the DOM string
        store.dispatch({
            type: 'RENAME_STUDENT_NAME_CHANGED',
            payload: inputRawValue,
        });

        const validation = validateStudentName(inputRawValue);

        if (!validation.isValid) {
            store.dispatch({
                type: 'RENAME_STUDENT_FAILED',
                payload: validation.message,
            });
            return;
        }

        if (!db.repository || !editState.renameStudentId) {
            store.dispatch({
                type: 'RENAME_STUDENT_FAILED',
                payload: 'Penyimpanan murid tidak tersedia.',
            });
            return;
        }

        store.dispatch({
            type: 'RENAME_STUDENT_STARTED',
        });

        try {
            await db.repository.updateStudent({
                id: editState.renameStudentId,
                name: validation.normalizedName,
            });

            store.dispatch({
                type: 'RENAME_STUDENT_SUCCEEDED',
            });

            if (route?.kind === 'student-detail' && route.studentId === editState.renameStudentId) {
                await syncRoute(route.hash);
                return;
            }

            await syncRoute(buildStudentsHash());
        } catch (error) {
            store.dispatch({
                type: 'RENAME_STUDENT_FAILED',
                payload: error instanceof Error ? error.message : 'Nama murid gagal diubah.',
            });

            await recoverAffectedScope(route?.hash ?? buildStudentsHash());
        }
    }

    async function deleteStudent() {
        const { db, editState, route, selectedStudentId } = store.getState();
        const studentId = editState.deleteStudentId;

        if (!db.repository || !studentId) {
            store.dispatch({
                type: 'DELETE_STUDENT_FAILED',
                payload: 'Penyimpanan murid tidak tersedia.',
            });
            return;
        }

        store.dispatch({
            type: 'DELETE_STUDENT_STARTED',
        });

        try {
            await db.repository.deleteStudent(studentId);

            store.dispatch({
                type: 'DELETE_STUDENT_SUCCEEDED',
            });

            if (selectedStudentId === studentId && route?.kind === 'student-detail') {
                cleanupEditArtifacts(store.getState());

                store.dispatch({
                    type: 'CURRENT_STUDENT_DELETED',
                    payload: {
                        studentId,
                    },
                });

                navigateToHash(buildStudentsHash(), {
                    replace: true,
                });

                return;
            }

            await syncRoute(buildStudentsHash());
        } catch (error) {
            store.dispatch({
                type: 'DELETE_STUDENT_FAILED',
                payload: error instanceof Error ? error.message : 'Murid gagal dihapus.',
            });

            await recoverAffectedScope(route?.hash ?? buildStudentsHash());
        }
    }

    async function processPhotos(files) {
        if (files.length === 0) {
            return;
        }

        store.dispatch({
            type: 'PHOTO_PROCESSING_STARTED',
        });

        try {
            const result = await processSelectedPhotos({
                files,
                existingPendingPhotos: [
                    ...store.getState().pendingPhotos,
                    ...store.getState().editState.retainedEntryPhotos,
                ],
                adapter: photoProcessorAdapter,
            });

            store.dispatch({
                type: 'PHOTO_PROCESSING_COMPLETED',
                payload: result,
            });
        } catch (error) {
            store.dispatch({
                type: 'PHOTO_PROCESSING_FAILED',
                payload: [
                    {
                        fileName: '',
                        message: error instanceof Error ? error.message : 'Foto gagal diproses.',
                    },
                ],
            });
        }
    }

    async function submitEntryComposer() {
        if (store.getState().editState.isEditing) {
            await updateEntry();
            return;
        }

        await createEntry();
    }

    async function createEntry() {
        const { db, draftEntry, pendingPhotos, selectedStudentId } = store.getState();
        const validation = validateEntryDraft({
            draftEntry,
            pendingPhotos,
        });

        if (!validation.isValid) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: validation.message,
            });
            return;
        }

        if (!db.connection || !selectedStudentId) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: 'Penyimpanan entri tidak tersedia.',
            });
            return;
        }

        store.dispatch({
            type: 'ENTRY_CREATE_STARTED',
        });

        try {
            const previewUrls = pendingPhotos
                .map((photo) => photo.thumbnail?.previewUrl)
                .filter(Boolean);
            const result = await createEntryWithPhotosTransaction({
                db: db.connection,
                studentId: selectedStudentId,
                note: validation.normalizedNote,
                pendingPhotos,
            });

            previewUrls.forEach((url) => {
                photoProcessorAdapter.revokeObjectUrl(url);
            });

            store.dispatch({
                type: 'ENTRY_CREATE_SUCCEEDED',
            });

            navigateToHash(buildStudentDetailHash(selectedStudentId, result.entryRecord.date), {
                replace: true,
            });
        } catch (error) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: describeEntryWriteError(error),
            });

            await recoverAffectedScope(store.getState().route?.hash ?? buildStudentDetailHash(selectedStudentId));
        }
    }

    function startEntryEdit(entryId) {
        const state = store.getState();
        const entry = state.routeData.selectedEntries.find((item) => item.id === entryId);

        if (!entry) {
            return;
        }

        cleanupEditArtifacts(state);

        store.dispatch({
            type: 'ENTRY_EDIT_STARTED',
            payload: {
                entryId: entry.id,
                entryDate: entry.date,
                note: entry.note ?? '',
                retainedPhotos: (entry.photos ?? []).map((photo) => ({
                    ...photo,
                    previewUrl: createStoredPhotoPreviewUrl(photo),
                })),
            },
        });
    }


    function expandComposer(mode) {
        store.dispatch({
            type: 'COMPOSER_EXPANDED',
        });

        if (mode === 'photo') {
            // Wait for render to finish so the input exists, then trigger it
            setTimeout(() => {
                const photoInput = shell.root.querySelector('[data-photo-input]');
                if (photoInput) {
                    photoInput.click();
                }
            }, 0);
        } else {
            // Focus the textarea for notes
            setTimeout(() => {
                const noteInput = shell.root.querySelector('[data-entry-note-input]');
                if (noteInput) {
                    noteInput.focus();
                }
            }, 0);
        }
    }

    function cancelEntryEdit() {
        cleanupEditArtifacts(store.getState());

        store.dispatch({
            type: 'ENTRY_EDIT_CANCELLED',
        });
    }

    async function updateEntry() {
        const { db, draftEntry, pendingPhotos, editState, route, selectedStudentId } = store.getState();
        const payload = buildEntryEditPayload({
            draftEntry,
            retainedPhotos: editState.retainedEntryPhotos,
            pendingPhotos,
        });

        if (!payload.isValid) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: payload.message,
            });
            return;
        }

        if (!db.connection || !editState.editingEntryId || !selectedStudentId) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: 'Penyimpanan entri tidak tersedia.',
            });
            return;
        }

        store.dispatch({
            type: 'ENTRY_CREATE_STARTED',
        });

        try {
            await updateEntryWithPhotosTransaction({
                db: db.connection,
                entryId: editState.editingEntryId,
                note: payload.note,
                retainedPhotoIds: payload.retainedPhotoIds,
                pendingPhotos: payload.pendingPhotos,
            });

            cleanupEditArtifacts(store.getState());

            store.dispatch({
                type: 'ENTRY_CREATE_SUCCEEDED',
            });

            navigateToHash(route?.hash ?? buildStudentDetailHash(selectedStudentId, editState.editingEntryDate), {
                replace: true,
            });
        } catch (error) {
            store.dispatch({
                type: 'ENTRY_CREATE_FAILED',
                payload: describeEntryWriteError(error),
            });

            await recoverAffectedScope(route?.hash ?? buildStudentDetailHash(selectedStudentId, editState.editingEntryDate));
        }
    }

    async function deleteEntry() {
        const { db, editState, route, selectedStudentId } = store.getState();

        if (!db.repository || !editState.deleteEntryId || !selectedStudentId) {
            store.dispatch({
                type: 'DELETE_ENTRY_FAILED',
                payload: 'Penyimpanan entri tidak tersedia.',
            });
            return;
        }

        try {
            await db.repository.deleteEntry(editState.deleteEntryId);

            cleanupEditArtifacts(store.getState());

            store.dispatch({
                type: 'DELETE_ENTRY_SUCCEEDED',
            });

            navigateToHash(route?.hash ?? buildStudentDetailHash(selectedStudentId, editState.deleteEntryDate), {
                replace: true,
            });
        } catch (error) {
            store.dispatch({
                type: 'DELETE_ENTRY_FAILED',
                payload: error instanceof Error ? error.message : 'Entri gagal dihapus.',
            });

            await recoverAffectedScope(route?.hash ?? buildStudentDetailHash(selectedStudentId, editState.deleteEntryDate));
        }
    }

    function cleanupEditArtifacts(state) {
        const retainedPreviewUrls = state.editState.retainedEntryPhotos
            .map((photo) => photo.previewUrl)
            .filter(Boolean);
        const pendingPreviewUrls = state.pendingPhotos
            .map((photo) => photo.thumbnail?.previewUrl)
            .filter(Boolean);

        revokePreviewUrls([...retainedPreviewUrls, ...pendingPreviewUrls]);
    }

    function createStoredPhotoPreviewUrl(photo) {
        const sourceBlob = photo.thumbnailBlob ?? photo.blob;

        if (!sourceBlob) {
            return '';
        }

        try {
            return photoProcessorAdapter.createObjectUrl(sourceBlob);
        } catch {
            return '';
        }
    }

    function createStoredPhotoFullUrl(photo) {
        if (!photo.blob) {
            return '';
        }

        try {
            return photoProcessorAdapter.createObjectUrl(photo.blob);
        } catch {
            return '';
        }
    }

    function revokePreviewUrls(urls) {
        urls.forEach((url) => {
            if (url) {
                photoProcessorAdapter.revokeObjectUrl(url);
            }
        });
    }

    async function initializeDbAndRoute() {
        try {
            const db = await initAppDb({
                indexedDb: windowObject.indexedDB,
            });

            if (!db.isReady) {
                store.dispatch({
                    type: 'DB_INITIALIZATION_FAILED',
                    payload: describeDbInitializationError(db),
                });
                return;
            }

            store.dispatch({
                type: 'DB_INITIALIZED',
                payload: {
                    ...db,
                    error: null,
                },
            });

            const initialHash = windowObject.location.hash || buildStudentsHash();

            if (!windowObject.location.hash) {
                navigateToHash(initialHash, {
                    replace: true,
                });
                return;
            }

            await syncRoute(initialHash);
        } catch (error) {
            handleFatalDbError(error);
        }
    }

    async function retryDbInitialization() {
        cleanupEditArtifacts(store.getState());

        store.dispatch({
            type: 'DB_RETRY_REQUESTED',
        });

        await initializeDbAndRoute();
    }

    async function recoverAffectedScope(hash) {
        try {
            await syncRoute(hash);
        } catch (error) {
            handleFatalDbError(error);
        }
    }

    function handleFatalDbError(error) {
        cleanupEditArtifacts(store.getState());

        store.dispatch({
            type: 'DB_INITIALIZATION_FAILED',
            payload: describeDbInitializationError(error),
        });
    }

    return {
        start,
        navigateToStudent(studentId) {
            navigateToHash(buildStudentDetailHash(studentId));
        },
        navigateToStudents() {
            navigateToHash(buildStudentsHash());
        },
    };
}
