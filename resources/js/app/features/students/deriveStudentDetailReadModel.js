export function deriveStudentDetailReadModel(entries, requestedDate = null) {
    const groupedEntries = new Map();

    entries.forEach((entry) => {
        if (!groupedEntries.has(entry.date)) {
            groupedEntries.set(entry.date, []);
        }

        groupedEntries.get(entry.date).push(entry);
    });

    const dateGroups = [...groupedEntries.entries()]
        .map(([date, dateEntries]) => ({
            date,
            entries: [...dateEntries].sort((left, right) => right.createdAt.localeCompare(left.createdAt)),
        }))
        .sort((left, right) => right.date.localeCompare(left.date));

    const availableDates = dateGroups.map((group) => group.date);
    const selectedDateTab =
        requestedDate && availableDates.includes(requestedDate) ? requestedDate : availableDates[0] ?? null;
    const selectedGroup = dateGroups.find((group) => group.date === selectedDateTab) ?? null;

    return {
        availableDates,
        dateGroups,
        selectedDateTab,
        selectedEntries: selectedGroup ? selectedGroup.entries : [],
    };
}
