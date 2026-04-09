import { createInitialState } from './createAppState.js';

export function createAppStore(initialState = createInitialState()) {
    let state = initialState;
    const listeners = new Set();

    return {
        getState() {
            return state;
        },
        subscribe(listener) {
            listeners.add(listener);
            listener(state);

            return () => {
                listeners.delete(listener);
            };
        },
        dispatch(action) {
            state = reduceState(state, action);
            listeners.forEach((listener) => listener(state));
        },
    };
}

export function reduceState(state, action) {
    switch (action.type) {
        case 'BOOT_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isBooting: true,
                },
            };

        case 'DB_INITIALIZED':
            return {
                ...state,
                currentView: action.payload.isReady ? state.currentView : 'fatal-db-error',
                fatalError: action.payload.isReady ? null : action.payload.error ?? null,
                db: action.payload,
                asyncFlags: {
                    ...state.asyncFlags,
                    isBooting: false,
                    isDbReady: action.payload.isReady,
                },
            };

        case 'DB_INITIALIZATION_FAILED':
            return {
                ...state,
                currentView: 'fatal-db-error',
                fatalError: action.payload,
                db: {
                    ...state.db,
                    isReady: false,
                    error: action.payload,
                    connection: null,
                    repository: null,
                },
                asyncFlags: {
                    ...state.asyncFlags,
                    isBooting: false,
                    isDbReady: false,
                    isRouteLoading: false,
                },
            };

        case 'DB_RETRY_REQUESTED':
            return {
                ...state,
                currentView: 'boot',
                fatalError: null,
                db: {
                    ...state.db,
                    error: null,
                    connection: null,
                    repository: null,
                },
                asyncFlags: {
                    ...state.asyncFlags,
                    isBooting: true,
                    isDbReady: false,
                    isRouteLoading: false,
                },
            };

        case 'ROUTE_LOADING':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isRouteLoading: true,
                },
            };

        case 'ROUTE_RESOLVED':
            return {
                ...state,
                currentView: action.payload.route.kind === 'student-list' ? 'students' : 'student-detail',
                selectedStudentId: action.payload.route.studentId ?? null,
                selectedDateTab: action.payload.route.date ?? null,
                route: action.payload.route,
                routeData: action.payload.routeData,
                asyncFlags: {
                    ...state.asyncFlags,
                    isRouteLoading: false,
                },
            };

        case 'SEARCH_QUERY_CHANGED':
            return {
                ...state,
                searchQuery: action.payload,
            };

        case 'ADD_STUDENT_DIALOG_OPENED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    addStudentDialogOpen: true,
                    addStudentError: null,
                },
            };

        case 'ADD_STUDENT_DIALOG_CLOSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    addStudentDialogOpen: false,
                    addStudentName: '',
                    addStudentError: null,
                },
                asyncFlags: {
                    ...state.asyncFlags,
                    isCreatingStudent: false,
                },
            };

        case 'RENAME_STUDENT_DIALOG_OPENED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    renameStudentDialogOpen: true,
                    renameStudentId: action.payload.studentId,
                    renameStudentName: action.payload.studentName,
                    renameStudentError: null,
                },
            };

        case 'RENAME_STUDENT_DIALOG_CLOSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    renameStudentDialogOpen: false,
                    renameStudentId: null,
                    renameStudentName: '',
                    renameStudentError: null,
                },
                asyncFlags: {
                    ...state.asyncFlags,
                    isRenamingStudent: false,
                },
            };

        case 'RENAME_STUDENT_NAME_CHANGED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    renameStudentName: action.payload,
                    renameStudentError: null,
                },
            };

        case 'RENAME_STUDENT_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isRenamingStudent: true,
                },
                editState: {
                    ...state.editState,
                    renameStudentError: null,
                },
            };

        case 'RENAME_STUDENT_FAILED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isRenamingStudent: false,
                },
                editState: {
                    ...state.editState,
                    renameStudentError: action.payload,
                },
            };

        case 'RENAME_STUDENT_SUCCEEDED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isRenamingStudent: false,
                },
                editState: {
                    ...state.editState,
                    renameStudentDialogOpen: false,
                    renameStudentId: null,
                    renameStudentName: '',
                    renameStudentError: null,
                },
            };

        case 'DELETE_STUDENT_DIALOG_OPENED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    deleteStudentDialogOpen: true,
                    deleteStudentId: action.payload.studentId,
                    deleteStudentName: action.payload.studentName,
                    deleteStudentError: null,
                },
            };

        case 'DELETE_STUDENT_DIALOG_CLOSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    deleteStudentDialogOpen: false,
                    deleteStudentId: null,
                    deleteStudentName: '',
                    deleteStudentError: null,
                },
                asyncFlags: {
                    ...state.asyncFlags,
                    isDeletingStudent: false,
                },
            };

        case 'DELETE_STUDENT_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isDeletingStudent: true,
                },
                editState: {
                    ...state.editState,
                    deleteStudentError: null,
                },
            };

        case 'DELETE_STUDENT_FAILED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isDeletingStudent: false,
                },
                editState: {
                    ...state.editState,
                    deleteStudentError: action.payload,
                },
            };

        case 'DELETE_STUDENT_SUCCEEDED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isDeletingStudent: false,
                },
                editState: {
                    ...state.editState,
                    deleteStudentDialogOpen: false,
                    deleteStudentId: null,
                    deleteStudentName: '',
                    deleteStudentError: null,
                },
            };

        case 'CURRENT_STUDENT_DELETED':
            return {
                ...state,
                currentView: 'students',
                draftEntry: {
                    note: '',
                },
                pendingPhotos: [],
                selectedStudentId: null,
                selectedDateTab: null,
                route: {
                    kind: 'student-list',
                    hash: '#/students',
                },
                routeData: {
                    students: state.routeData.students.filter(
                        (student) => student.id !== action.payload.studentId
                    ),
                    selectedStudent: null,
                    availableDates: [],
                    dateGroups: [],
                    selectedEntries: [],
                },
                editState: {
                    ...state.editState,
                    mode: 'idle',
                    isEditing: false,
                    photoProcessingErrors: [],
                    entryCreateError: null,
                    editingEntryId: null,
                    editingEntryDate: null,
                    retainedEntryPhotos: [],
                    deleteEntryDialogOpen: false,
                    deleteEntryId: null,
                    deleteEntryDate: null,
                    deleteEntryError: null,
                },
            };

        case 'ADD_STUDENT_NAME_CHANGED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    addStudentName: action.payload,
                    addStudentError: null,
                },
            };

        case 'CREATE_STUDENT_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isCreatingStudent: true,
                },
                editState: {
                    ...state.editState,
                    addStudentError: null,
                },
            };

        case 'CREATE_STUDENT_FAILED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isCreatingStudent: false,
                },
                editState: {
                    ...state.editState,
                    addStudentError: action.payload,
                },
            };

        case 'CREATE_STUDENT_SUCCEEDED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isCreatingStudent: false,
                },
                editState: {
                    ...state.editState,
                    addStudentDialogOpen: false,
                    addStudentName: '',
                    addStudentError: null,
                },
            };

        case 'PHOTO_PROCESSING_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isProcessingPhotos: true,
                },
                editState: {
                    ...state.editState,
                    photoProcessingErrors: [],
                },
            };

        case 'PHOTO_PROCESSING_COMPLETED':
            return {
                ...state,
                pendingPhotos: [...state.pendingPhotos, ...action.payload.pendingPhotos],
                asyncFlags: {
                    ...state.asyncFlags,
                    isProcessingPhotos: false,
                },
                editState: {
                    ...state.editState,
                    photoProcessingErrors: action.payload.errors,
                },
            };

        case 'PHOTO_PROCESSING_FAILED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isProcessingPhotos: false,
                },
                editState: {
                    ...state.editState,
                    photoProcessingErrors: action.payload,
                },
            };

        case 'PENDING_PHOTO_REMOVED':
            return {
                ...state,
                pendingPhotos: state.pendingPhotos.filter((photo) => photo.id !== action.payload),
            };

        case 'DRAFT_ENTRY_NOTE_CHANGED':
            return {
                ...state,
                draftEntry: {
                    ...state.draftEntry,
                    note: action.payload,
                },
                editState: {
                    ...state.editState,
                    entryCreateError: null,
                },
            };

        case 'ENTRY_EDIT_STARTED':
            return {
                ...state,
                draftEntry: {
                    note: action.payload.note,
                },
                pendingPhotos: [],
                editState: {
                    ...state.editState,
                    mode: 'entry-edit',
                    isEditing: true,
                    entryCreateError: null,
                    photoProcessingErrors: [],
                    editingEntryId: action.payload.entryId,
                    editingEntryDate: action.payload.entryDate,
                    retainedEntryPhotos: action.payload.retainedPhotos,
                },
            };

        case 'ENTRY_EDIT_CANCELLED':
            return {
                ...state,
                draftEntry: {
                    note: '',
                },
                pendingPhotos: [],
                editState: {
                    ...state.editState,
                    mode: 'idle',
                    isComposerExpanded: false,
                    isEditing: false,
                    entryCreateError: null,
                    photoProcessingErrors: [],
                    editingEntryId: null,
                    editingEntryDate: null,
                    retainedEntryPhotos: [],
                    deleteEntryDialogOpen: false,
                    deleteEntryId: null,
                    deleteEntryDate: null,
                    deleteEntryError: null,
                },
            };

        case 'RETAINED_ENTRY_PHOTO_REMOVED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    retainedEntryPhotos: state.editState.retainedEntryPhotos.filter(
                        (photo) => photo.id !== action.payload
                    ),
                    entryCreateError: null,
                },
            };

        case 'DELETE_ENTRY_DIALOG_OPENED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    deleteEntryDialogOpen: true,
                    deleteEntryId: action.payload.entryId,
                    deleteEntryDate: action.payload.entryDate,
                    deleteEntryError: null,
                },
            };

        case 'DELETE_ENTRY_DIALOG_CLOSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    deleteEntryDialogOpen: false,
                    deleteEntryId: null,
                    deleteEntryDate: null,
                    deleteEntryError: null,
                },
            };

        case 'DELETE_ENTRY_FAILED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    deleteEntryError: action.payload,
                },
            };

        case 'DELETE_ENTRY_SUCCEEDED':
            return {
                ...state,
                draftEntry: {
                    note: '',
                },
                pendingPhotos: [],
                editState: {
                    ...state.editState,
                    mode: 'idle',
                    isComposerExpanded: false,
                    isEditing: false,
                    entryCreateError: null,
                    photoProcessingErrors: [],
                    editingEntryId: null,
                    editingEntryDate: null,
                    retainedEntryPhotos: [],
                    deleteEntryDialogOpen: false,
                    deleteEntryId: null,
                    deleteEntryDate: null,
                    deleteEntryError: null,
                },
            };

        case 'ENTRY_CREATE_STARTED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isSavingEntry: true,
                },
                editState: {
                    ...state.editState,
                    entryCreateError: null,
                },
            };

        case 'ENTRY_CREATE_FAILED':
            return {
                ...state,
                asyncFlags: {
                    ...state.asyncFlags,
                    isSavingEntry: false,
                },
                editState: {
                    ...state.editState,
                    entryCreateError: action.payload,
                },
            };

        case 'ENTRY_CREATE_SUCCEEDED':
            return {
                ...state,
                draftEntry: {
                    note: '',
                },
                pendingPhotos: [],
                asyncFlags: {
                    ...state.asyncFlags,
                    isSavingEntry: false,
                },
                editState: {
                    ...state.editState,
                    mode: 'idle',
                    isComposerExpanded: false,
                    isEditing: false,
                    entryCreateError: null,
                    photoProcessingErrors: [],
                    editingEntryId: null,
                    editingEntryDate: null,
                    retainedEntryPhotos: [],
                    deleteEntryDialogOpen: false,
                    deleteEntryId: null,
                    deleteEntryDate: null,
                    deleteEntryError: null,
                },
            };

        case 'PHOTO_VIEWER_OPENED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    activePhotoUrl: action.payload,
                },
            };

        case 'PHOTO_VIEWER_CLOSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    activePhotoUrl: null,
                },
            };

        case 'COMPOSER_EXPANDED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    isComposerExpanded: true,
                },
            };

        case 'COMPOSER_COLLAPSED':
            return {
                ...state,
                editState: {
                    ...state.editState,
                    isComposerExpanded: false,
                },
            };

        default:
            return state;
    }
}
