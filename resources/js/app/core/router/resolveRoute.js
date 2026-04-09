import { buildStudentDetailHash, buildStudentsHash } from './buildHashRoute.js';
import { deriveStudentDetailReadModel } from '../../features/students/deriveStudentDetailReadModel.js';

export async function resolveRoute(parsedRoute, repository) {
    if (parsedRoute.kind === 'invalid') {
        return {
            type: 'repair',
            hash: buildStudentsHash(),
        };
    }

    if (parsedRoute.kind === 'student-list') {
        const students = repository ? await repository.loadAllStudents() : [];

        return {
            type: 'resolved',
            route: parsedRoute,
            routeData: {
                students,
                selectedStudent: null,
                availableDates: [],
                dateGroups: [],
                selectedEntries: [],
            },
        };
    }

    if (!repository) {
        return {
            type: 'repair',
            hash: buildStudentsHash(),
        };
    }

    const selectedStudent = await repository.loadStudentById(parsedRoute.studentId);
    const entries = selectedStudent ? await repository.loadEntriesByStudent(parsedRoute.studentId) : [];
    const entryPhotos = selectedStudent
        ? await repository.loadPhotosByEntryIds(entries.map((entry) => entry.id))
        : [];
    const photoMap = groupPhotosByEntryId(entryPhotos);
    const entriesWithPhotos = entries.map((entry) => ({
        ...entry,
        photos: photoMap.get(entry.id) ?? [],
    }));
    const detailReadModel = deriveStudentDetailReadModel(entriesWithPhotos, parsedRoute.date);

    if (!selectedStudent) {
        return {
            type: 'repair',
            hash: buildStudentsHash(),
        };
    }

    if (parsedRoute.date && parsedRoute.date !== detailReadModel.selectedDateTab) {
        return {
            type: 'repair',
            hash: buildStudentDetailHash(selectedStudent.id, detailReadModel.selectedDateTab),
        };
    }

    return {
        type: 'resolved',
        route: {
            ...parsedRoute,
            date: detailReadModel.selectedDateTab,
            hash: buildStudentDetailHash(selectedStudent.id, detailReadModel.selectedDateTab),
        },
        routeData: {
            students: await repository.loadAllStudents(),
            selectedStudent,
            availableDates: detailReadModel.availableDates,
            dateGroups: detailReadModel.dateGroups,
            selectedEntries: detailReadModel.selectedEntries,
        },
    };
}

function groupPhotosByEntryId(photos) {
    return photos.reduce((map, photo) => {
        const entryPhotos = map.get(photo.entryId) ?? [];
        entryPhotos.push(photo);
        map.set(photo.entryId, entryPhotos);
        return map;
    }, new Map());
}
