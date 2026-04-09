export const APP_DB_NAME = 'dianas-mandarin-pwa';
export const APP_DB_VERSION = 1;

export const APP_DB_STORES = {
    students: 'students',
    entries: 'entries',
    photos: 'photos',
    meta: 'meta',
};

export const APP_DB_INDEXES = {
    studentsByName: 'by_name',
    entriesByStudent: 'by_student',
    entriesByStudentDate: 'by_student_date',
    entriesByStudentCreated: 'by_student_created',
    photosByEntry: 'by_entry',
};
