const DB_NAME = 'kawi-pos-offline';
const DB_VERSION = 1;
const SALES_STORE = 'salesQueue';
const CATALOG_STORE = 'catalogCache';

function openDb() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(DB_NAME, DB_VERSION);

        request.onupgradeneeded = () => {
            const db = request.result;

            if (!db.objectStoreNames.contains(SALES_STORE)) {
                db.createObjectStore(SALES_STORE, { keyPath: 'client_uuid' });
            }

            if (!db.objectStoreNames.contains(CATALOG_STORE)) {
                db.createObjectStore(CATALOG_STORE, { keyPath: 'key' });
            }
        };

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

async function transaction(storeName, mode, callback) {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(storeName, mode);
        const store = tx.objectStore(storeName);
        const result = callback(store);

        tx.oncomplete = () => resolve(result);
        tx.onerror = () => reject(tx.error);
    });
}

export async function enqueueSale(sale) {
    return transaction(SALES_STORE, 'readwrite', (store) => store.put(sale));
}

export async function listQueuedSales() {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(SALES_STORE, 'readonly');
        const request = tx.objectStore(SALES_STORE).getAll();

        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

export async function removeQueuedSale(clientUuid) {
    return transaction(SALES_STORE, 'readwrite', (store) => store.delete(clientUuid));
}

export async function cacheCatalog(key, payload) {
    return transaction(CATALOG_STORE, 'readwrite', (store) => store.put({ key, payload, cached_at: new Date().toISOString() }));
}

export async function getCachedCatalog(key) {
    const db = await openDb();

    return new Promise((resolve, reject) => {
        const tx = db.transaction(CATALOG_STORE, 'readonly');
        const request = tx.objectStore(CATALOG_STORE).get(key);

        request.onsuccess = () => resolve(request.result?.payload ?? null);
        request.onerror = () => reject(request.error);
    });
}
