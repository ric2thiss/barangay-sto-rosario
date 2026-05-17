/**
 * Recognition Logic Module
 * Handles face detection and matching logic
 */
export class RecognitionLogic {
    constructor(webcamHandler, faceRecognition, labeledDescriptors, detectionInterval = 1000) {
        this.webcamHandler = webcamHandler;
        this.faceRecognition = faceRecognition;
        this.labeledDescriptors = labeledDescriptors;
        this.detectionInterval = detectionInterval;
        this.detectionIntervalId = null;
        this.loggedToday = new Set(); // To prevent logging the same person multiple times per session
        this.onRecognizedCallback = null;

        this.paused = false;
        this._playListenerAttached = false;
        this._video = null;
        this._overlay = null;
        this._ctx = null;
        this._displaySize = null;
        this._faceapi = null;
    }

    /**
     * Set callback for when a face is recognized
     */
    setOnRecognizedCallback(callback) {
        this.onRecognizedCallback = callback;
    }

    /**
     * Start face detection loop
     */
    start() {
        // Access global faceapi variable from CDN
        const faceapi = window.faceapi || globalThis.faceapi;
        if (!faceapi) {
            console.error('Face-API.js library not loaded');
            return;
        }
        this._faceapi = faceapi;

        const video = this.webcamHandler.getVideo();
        const overlay = this.webcamHandler.getOverlay();
        
        if (!video || !overlay) {
            console.error('Video or overlay element not found');
            return;
        }

        this._video = video;
        this._overlay = overlay;
        this._ctx = overlay.getContext('2d');

        const setupAndRun = () => {
            if (!this._video || !this._overlay || !this._faceapi) return;
            this._displaySize = { width: this._video.clientWidth, height: this._video.clientHeight };
            this._faceapi.matchDimensions(this._overlay, this._displaySize);
            this._startInterval();
        };

        if (!this._playListenerAttached) {
            video.addEventListener('play', setupAndRun);
            this._playListenerAttached = true;
        }

        // If video is already playing, start immediately.
        if (!video.paused && !video.ended && video.readyState >= 2) {
            setupAndRun();
        }
    }

    _startInterval() {
        if (this.detectionIntervalId) return;
        if (this.paused) return;
        if (!this._faceapi || !this._video || !this._overlay || !this._ctx || !this._displaySize) return;

        this.detectionIntervalId = setInterval(async () => {
            if (this.paused) return;

            const faceMatcher = this.faceRecognition.getFaceMatcher();
            if (!faceMatcher) return;

            const faceapi = this._faceapi;
            const video = this._video;
            const overlay = this._overlay;
            const ctx = this._ctx;
            const displaySize = this._displaySize;

            const detections = await faceapi
                .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({
                    inputSize: 416,
                    scoreThreshold: 0.6
                }))
                .withFaceLandmarks()
                .withFaceDescriptors();

            // Clear canvas
            ctx.clearRect(0, 0, overlay.width, overlay.height);

            // Resize detections to match video dimensions
            const resizedDetections = faceapi.resizeResults(detections, displaySize);

            if (resizedDetections.length && faceMatcher) {
                const results = resizedDetections.map(d =>
                    faceMatcher.findBestMatch(d.descriptor)
                );

                results.forEach((result, i) => {
                    const box = resizedDetections[i].detection.box;
                    const label = result.label;
                    const distance = result.distance.toFixed(2);

                    // Draw bounding box
                    ctx.strokeStyle = label === "unknown" ? "red" : "lime";
                    ctx.lineWidth = 3;
                    ctx.strokeRect(box.x, box.y, box.width, box.height);

                    // Draw label
                    ctx.fillStyle = label === "unknown" ? "red" : "lime";
                    ctx.font = "16px Inter, sans-serif";
                    ctx.fillText(`${label} (${distance})`, box.x, box.y - 8);

                    if (label !== "unknown") {
                        const person = this.labeledDescriptors.find(p => p.name === label);
                        if (person && this.onRecognizedCallback) {
                            // Pass person data to callback
                            this.onRecognizedCallback(person.id, person.name, person);
                        }
                    }
                });
            }
        }, this.detectionInterval);
    }

    /**
     * Stop face detection loop
     */
    stop() {
        if (this.detectionIntervalId) {
            clearInterval(this.detectionIntervalId);
            this.detectionIntervalId = null;
        }
    }

    /**
     * Pause face detection loop (used while a modal/transaction is active)
     */
    pause() {
        this.paused = true;
        this.stop();
        if (this._ctx && this._overlay) {
            this._ctx.clearRect(0, 0, this._overlay.width, this._overlay.height);
        }
    }

    /**
     * Resume face detection loop
     */
    resume() {
        this.paused = false;
        this._startInterval();
    }

    /**
     * Check if recognition is currently running
     */
    isRunning() {
        return this.detectionIntervalId !== null;
    }

    /**
     * Check if person is already logged today
     */
    isLoggedToday(id) {
        return this.loggedToday.has(id);
    }

    /**
     * Mark person as logged
     */
    markAsLogged(id, duration = 300000) { // 5 minutes default
        this.loggedToday.add(id);
        setTimeout(() => {
            this.loggedToday.delete(id);
            console.log(`Person ${id} is ready for a new log entry.`);
        }, duration);
    }

    /**
     * Reset logged state - clear all logged entries
     */
    resetLoggedState() {
        this.loggedToday.clear();
        console.log('Recognition state reset - ready for new face capture.');
    }
}
