export function describeDbInitializationError(error) {
    if (!error) {
        return 'Penyimpanan lokal gagal disiapkan. Coba lagi untuk membuka database aplikasi.';
    }

    if (typeof error === 'object' && error !== null && error.isSupported === false) {
        return 'IndexedDB tidak tersedia pada sesi browser ini. Coba lagi setelah aplikasi dibuka ulang atau gunakan browser yang didukung.';
    }

    if (error instanceof Error && error.message) {
        return error.message;
    }

    return 'Penyimpanan lokal gagal disiapkan. Coba lagi untuk membuka database aplikasi.';
}

export function describeEntryWriteError(error) {
    if (isQuotaExceededError(error)) {
        return 'Penyimpanan perangkat penuh. Hapus beberapa entri atau foto, lalu coba simpan lagi.';
    }

    if (error instanceof Error && error.message) {
        return error.message;
    }

    return 'Entri gagal disimpan.';
}

export function isQuotaExceededError(error) {
    if (!error || typeof error !== 'object') {
        return false;
    }

    const name = typeof error.name === 'string' ? error.name : '';
    const message = typeof error.message === 'string' ? error.message.toLowerCase() : '';

    return (
        name === 'QuotaExceededError' ||
        name === 'NS_ERROR_DOM_QUOTA_REACHED' ||
        message.includes('quota') ||
        message.includes('storage full')
    );
}
