/**
 * Face Recognition Module
 * Handles Face-API.js initialization and model loading
 *
 * Performance optimization: uses IndexedDB descriptor cache so that
 * the expensive per-photo neural-net inference only runs once.
 * Subsequent page loads read cached 128-d descriptors in < 50 ms.
 */
import { DescriptorCache } from './descriptorCache.js';

export class FaceRecognition {
    constructor(modelsPath = './models') {
        this.modelsPath = modelsPath;
        this.faceMatcher = null;
        this.isModelsLoaded = false;
        this.descriptorCache = new DescriptorCache();
    }

    /**
     * Load Face-API models
     * Note: faceapi is a global variable from the CDN script
     */
    async loadModels() {
        try {
            // Access global faceapi variable from CDN
            const faceapi = window.faceapi || globalThis.faceapi;
            if (!faceapi) {
                throw new Error('Face-API.js library not loaded. Please ensure the CDN script is included.');
            }

            // Load all models in parallel for faster init
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(this.modelsPath),
                faceapi.nets.faceLandmark68Net.loadFromUri(this.modelsPath),
                faceapi.nets.ssdMobilenetv1.loadFromUri(this.modelsPath),
                faceapi.nets.faceRecognitionNet.loadFromUri(this.modelsPath),
            ]);
            this.isModelsLoaded = true;
            return true;
        } catch (error) {
            console.error("Error loading face-api models:", error);
            this.isModelsLoaded = false;
            return false;
        }
    }

    /**
     * Extract face descriptor(s) from a single image URL.
     * Tries TinyFaceDetector first (fast), then SsdMobilenetv1 (more robust).
     *
     * @param {string} imgUrl
     * @returns {Promise<Float32Array|null>}
     */
    async _extractDescriptor(imgUrl) {
        const faceapi = window.faceapi || globalThis.faceapi;
        const img = await faceapi.fetchImage(imgUrl);

        // Fast detector first
        let detection = await faceapi
            .detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.5 }))
            .withFaceLandmarks()
            .withFaceDescriptor();

        if (!detection) {
            // Fallback to heavier but more accurate detector
            detection = await faceapi
                .detectSingleFace(img, new faceapi.SsdMobilenetv1Options({ minConfidence: 0.5 }))
                .withFaceLandmarks()
                .withFaceDescriptor();
        }

        return detection ? detection.descriptor : null;
    }

    /**
     * Load labeled face descriptors from images.
     * Uses IndexedDB cache to skip neural-net inference for already-processed photos.
     * Supports multiple photos per person (3 angles) for better recognition accuracy.
     *
     * @param {Function|null} onProgress  Optional callback(current, total, name) for progress UI.
     */
    async loadLabeledImages(labeledDescriptors, onProgress = null) {
        // Access global faceapi variable from CDN
        const faceapi = window.faceapi || globalThis.faceapi;
        if (!faceapi) {
            throw new Error('Face-API.js library not loaded');
        }

        // Initialize cache
        await this.descriptorCache.init();

        const total = labeledDescriptors.length;
        let processed = 0;
        let cacheHits = 0;

        const results = [];

        // Process in small batches to avoid overwhelming the browser
        const BATCH_SIZE = 5;
        for (let i = 0; i < labeledDescriptors.length; i += BATCH_SIZE) {
            const batch = labeledDescriptors.slice(i, i + BATCH_SIZE);
            const batchResults = await Promise.all(
                batch.map(async (person) => {
                    processed++;
                    try {
                        const imageUrls = person.imgs || (person.img ? [person.img] : []);
                        if (imageUrls.length === 0) {
                            return null;
                        }

                        // Check cache first
                        const cached = await this.descriptorCache.get(person.id, imageUrls);
                        if (cached && cached.length > 0) {
                            cacheHits++;
                            if (onProgress) onProgress(processed, total, person.name, true);
                            return new faceapi.LabeledFaceDescriptors(person.name, cached);
                        }

                        // Cache miss — compute descriptors
                        if (onProgress) onProgress(processed, total, person.name, false);

                        const descriptors = [];
                        for (const imgUrl of imageUrls) {
                            try {
                                const descriptor = await this._extractDescriptor(imgUrl);
                                if (descriptor) {
                                    descriptors.push(descriptor);
                                }
                            } catch (error) {
                                console.error(`Error processing image ${imgUrl} for ${person.name}:`, error);
                            }
                        }

                        if (descriptors.length === 0) {
                            return null;
                        }

                        // Store in cache for next time
                        await this.descriptorCache.set(person.id, imageUrls, descriptors);

                        return new faceapi.LabeledFaceDescriptors(person.name, descriptors);
                    } catch (error) {
                        console.error(`Error loading images for ${person.name}:`, error);
                        return null;
                    }
                })
            );
            results.push(...batchResults);
        }

        console.log(`Face descriptors: ${cacheHits}/${total} from cache, ${total - cacheHits} computed fresh`);
        return results;
    }

    /**
     * Initialize face matcher
     *
     * @param {Function|null} onProgress  Optional callback(current, total, name) for progress UI.
     */
    async initializeFaceMatcher(labeledDescriptors, recognitionThreshold = 0.4, onProgress = null) {
        // Access global faceapi variable from CDN
        const faceapi = window.faceapi || globalThis.faceapi;
        if (!faceapi) {
            throw new Error('Face-API.js library not loaded');
        }

        if (!this.isModelsLoaded) {
            const loaded = await this.loadModels();
            if (!loaded) {
                throw new Error('Failed to load Face-API models');
            }
        }

        const labeledFaceDescriptors = await this.loadLabeledImages(labeledDescriptors, onProgress);
        const validDescriptors = labeledFaceDescriptors.filter(d => d !== null);

        if (validDescriptors.length === 0) {
            throw new Error('No valid face descriptors found');
        }

        // Update the resident count in the cache for future invalidation checks
        await this.descriptorCache.setCount(labeledDescriptors.length);

        this.faceMatcher = new faceapi.FaceMatcher(validDescriptors, recognitionThreshold);
        return this.faceMatcher;
    }

    /**
     * Get face matcher instance
     */
    getFaceMatcher() {
        return this.faceMatcher;
    }

    /**
     * Check if models are loaded
     */
    areModelsLoaded() {
        return this.isModelsLoaded;
    }

    /**
     * Clear the descriptor cache (e.g. after re-registering photos).
     */
    async clearCache() {
        await this.descriptorCache.clear();
    }
}
