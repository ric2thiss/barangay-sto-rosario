/**
 * Face Descriptor Cache
 * 
 * Caches computed face descriptors in IndexedDB so that the expensive
 * neural-net inference (detect face → extract 128-d descriptor) only runs
 * once per resident photo. On subsequent page loads, descriptors are read
 * from the cache in < 50 ms instead of re-processing all images (~500 ms each).
 *
 * Cache key = resident ID + photo URL hash → if photo changes, we recompute.
 */

const DB_NAME = 'FaceDescriptorCache';
const DB_VERSION = 1;
const STORE_NAME = 'descriptors';
const META_STORE = 'meta';

/**
 * Open (or create) the IndexedDB database.
 * @returns {Promise<IDBDatabase>}
 */
function openDB() {
    return new Promise((resolve, reject) => {
        const req = indexedDB.open(DB_NAME, DB_VERSION);
        req.onupgradeneeded = (e) => {
            const db = e.target.result;
            if (!db.objectStoreNames.contains(STORE_NAME)) {
                db.createObjectStore(STORE_NAME, { keyPath: 'key' });
            }
            if (!db.objectStoreNames.contains(META_STORE)) {
                db.createObjectStore(META_STORE, { keyPath: 'key' });
            }
        };
        req.onsuccess = () => resolve(req.result);
        req.onerror = () => reject(req.error);
    });
}

/**
 * Simple hash of a string (for photo URL fingerprinting).
 */
function simpleHash(str) {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        hash = ((hash << 5) - hash + str.charCodeAt(i)) | 0;
    }
    return hash.toString(36);
}

/**
 * Build a cache key for a person + their photo URLs.
 * If photos change, the key changes → forces re-computation.
 */
function cacheKey(personId, imageUrls) {
    const urlsHash = simpleHash(imageUrls.sort().join('|'));
    return `${personId}__${urlsHash}`;
}

/**
 * Get a cached descriptor entry from IndexedDB.
 * @returns {Promise<{key: string, descriptors: number[][]} | null>}
 */
async function getCached(db, key) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readonly');
        const store = tx.objectStore(STORE_NAME);
        const req = store.get(key);
        req.onsuccess = () => resolve(req.result || null);
        req.onerror = () => reject(req.error);
    });
}

/**
 * Store a descriptor entry in IndexedDB.
 */
async function putCached(db, key, descriptorsArrays) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(STORE_NAME, 'readwrite');
        const store = tx.objectStore(STORE_NAME);
        store.put({ key, descriptors: descriptorsArrays, cachedAt: Date.now() });
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

/**
 * Store a version/count fingerprint so we know when to invalidate.
 */
async function setMeta(db, key, value) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(META_STORE, 'readwrite');
        const store = tx.objectStore(META_STORE);
        store.put({ key, value, updatedAt: Date.now() });
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function getMeta(db, key) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction(META_STORE, 'readonly');
        const store = tx.objectStore(META_STORE);
        const req = store.get(key);
        req.onsuccess = () => resolve(req.result?.value ?? null);
        req.onerror = () => reject(req.error);
    });
}

/**
 * Clear all cached descriptors (useful when photos are re-registered).
 */
async function clearAll(db) {
    return new Promise((resolve, reject) => {
        const tx = db.transaction([STORE_NAME, META_STORE], 'readwrite');
        tx.objectStore(STORE_NAME).clear();
        tx.objectStore(META_STORE).clear();
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

export class DescriptorCache {
    constructor() {
        this.db = null;
    }

    async init() {
        try {
            this.db = await openDB();
        } catch (e) {
            console.warn('IndexedDB not available — descriptor caching disabled:', e.message);
            this.db = null;
        }
    }

    /**
     * Check if we have a valid cached descriptor for this person+photos combo.
     * @returns {Float32Array[] | null}  Array of descriptor Float32Arrays, or null if not cached.
     */
    async get(personId, imageUrls) {
        if (!this.db) return null;
        try {
            const key = cacheKey(personId, imageUrls);
            const entry = await getCached(this.db, key);
            if (!entry || !entry.descriptors || entry.descriptors.length === 0) return null;
            // Convert plain arrays back to Float32Array
            return entry.descriptors.map(arr => new Float32Array(arr));
        } catch {
            return null;
        }
    }

    /**
     * Store computed descriptors for this person+photos combo.
     * @param {Float32Array[]} descriptors
     */
    async set(personId, imageUrls, descriptors) {
        if (!this.db) return;
        try {
            const key = cacheKey(personId, imageUrls);
            // Convert Float32Array to plain arrays for IndexedDB storage
            const plain = descriptors.map(d => Array.from(d));
            await putCached(this.db, key, plain);
        } catch (e) {
            console.warn('Failed to cache descriptor:', e.message);
        }
    }

    /**
     * Check if total resident count changed (simple invalidation).
     * Returns true if cache should be considered valid.
     */
    async isCountValid(currentCount) {
        if (!this.db) return false;
        try {
            const cached = await getMeta(this.db, 'residentCount');
            return cached === currentCount;
        } catch {
            return false;
        }
    }

    async setCount(count) {
        if (!this.db) return;
        try {
            await setMeta(this.db, 'residentCount', count);
        } catch { /* ignore */ }
    }

    async clear() {
        if (!this.db) return;
        try {
            await clearAll(this.db);
        } catch { /* ignore */ }
    }
}
