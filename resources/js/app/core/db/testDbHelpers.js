import { APP_DB_STORES } from './appDbSchema.js';
import { putRecord, requestToPromise, transactionToPromise } from './idbHelpers.js';

export async function seedPhotoRecord(db, record) {
    await putRecord(db, APP_DB_STORES.photos, record);
}

export async function insertRawRecord(db, storeName, record) {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    store.put(record);
    await transactionToPromise(transaction);
}

export async function clearStore(db, storeName) {
    const transaction = db.transaction(storeName, 'readwrite');
    const store = transaction.objectStore(storeName);
    await requestToPromise(store.clear());
    await transactionToPromise(transaction);
}
