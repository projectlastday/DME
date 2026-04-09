export function deriveStudentList(students, searchQuery) {
    const normalizedQuery = searchQuery.trim().toLocaleLowerCase();
    const sortedStudents = [...students].sort((left, right) => left.name.localeCompare(right.name));

    if (!normalizedQuery) {
        return sortedStudents;
    }

    return sortedStudents.filter((student) => student.name.toLocaleLowerCase().includes(normalizedQuery));
}
