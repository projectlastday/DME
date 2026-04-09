export function validateStudentName(name) {
    const normalizedName = typeof name === 'string' ? name.trim() : '';

    if (!normalizedName) {
        return {
            isValid: false,
            message: 'Nama murid wajib diisi.',
            normalizedName,
        };
    }

    return {
        isValid: true,
        message: null,
        normalizedName,
    };
}
