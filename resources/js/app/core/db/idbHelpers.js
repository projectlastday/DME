export function openIndexedDb({ indexedDb, name, version, upgrade }) {
    return new Promise((resolve, reject) => {
        const request = indexedDb.open(name, version);

        request.onerror = () => {
            reject(request.error ?? new Error(`Failed to open IndexedDB database "${name}".`));
        };

        request.onupgradeneeded = (event) => {
            upgrade({
                db: request.result,
                oldVersion: event.oldVersion,
                newVersion: event.newVersion,
                transaction: request.transaction,
            });
        };

        request.onsuccess = () => {
            resolve(request.result);
        };
    });
}

export function requestToPromise(request) {
    return new Promise((resolve, reject) => {
        request.onerror = () => {
            reject(request.error ?? new Error('IndexedDB request failed.'));
        };

        request.onsuccess = () => {
            resolve(request.result);
        };
    });
}

export function transactionToPromise(transaction) {
    return new Promise((resolve, reject) => {
        transaction.oncomplete = () => {
            resolve();
        };

        transaction.onabort = () => {
            reject(transaction.error ?? new Error('IndexedDB transaction aborted.'));
        };

        transaction.onerror = () => {
            reject(transaction.error ?? new Error('IndexedDB transaction failed.'));
        };
    });
}

export async function getRecordByKey(db, storeName, key) {
    const transaction = db.transaction(storeName, 'readonly');
    const store = transaction.objectStore(storeName);
    const result = await requestToPromise(store.get(key));
    await transactionToPromise(transaction);

    return result;
}

export async function getAllRecords(db, storeName) {
    const transaction = db.transaction(storeName, 'readonly');
    const store = transaction.objectStore(storeName);
    const result = await requestToPromise(store.getAll());
    await transactionToPromise(transaction);

    return result;
}

export async function getAllByIndex(db, storeName, indexName, key) {
    const transaction = db.transaction(storeName, 'readonly');
    const store = transaction.objectStore(storeName);
    const result = await requestToPromise(store.index(indexName).getAll(key));
    await transactionToPromise(transaction);

    return result;
}

export async function putRecord(db, storeName, value) {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    await requestToPromise(store.put(value));
    await transactionToPromise(transaction);

    return value;
}

export async function runTransaction(db, storeNames, mode, handler) {
    const transaction = db.transaction(storeNames, mode);
    const stores = Object.fromEntries(
        storeNames.map((storeName) => [storeName, transaction.objectStore(storeName)])
    );

    try {
        const value = handler({
            transaction,
            stores,
        });

        await transactionToPromise(transaction);

        return value;
    } catch (error) {
        try {
            transaction.abort();
        } catch {}

        throw error;
    }
}
