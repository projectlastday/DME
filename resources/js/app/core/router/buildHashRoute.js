export function buildStudentsHash() {
    return '#/students';
}

export function buildStudentDetailHash(studentId, selectedDateTab = null) {
    if (!selectedDateTab) {
        return `#/students/${studentId}`;
    }

    return `#/students/${studentId}/date/${selectedDateTab}`;
}
