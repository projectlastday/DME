export function validateEntryDraft({ draftEntry, pendingPhotos, retainedPhotoCount = 0 }) {
    const note = typeof draftEntry?.note === 'string' ? draftEntry.note : '';
    const trimmedNote = note.trim();
    const photoCount = (Array.isArray(pendingPhotos) ? pendingPhotos.length : 0) + retainedPhotoCount;

    if (note.length > 5000) {
        return {
            isValid: false,
            message: 'Catatan maksimal 5000 karakter.',
            normalizedNote: note,
        };
    }

    if (!trimmedNote && photoCount === 0) {
        return {
            isValid: false,
            message: 'Tambahkan catatan, foto, atau keduanya sebelum menyimpan entri.',
            normalizedNote: '',
        };
    }

    return {
        isValid: true,
        message: null,
        normalizedNote: trimmedNote ? note : '',
    };
}
