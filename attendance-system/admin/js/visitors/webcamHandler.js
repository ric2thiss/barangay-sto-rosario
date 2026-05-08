/**
 * Webcam Handler Module
 * Handles webcam access and video streaming
 */
export class WebcamHandler {
    constructor(videoId = 'webcam-video') {
        this.video = document.getElementById(videoId);
        this.overlay = document.getElementById('video-overlay');
        this.videoPlaceholder = document.getElementById('video-placeholder');
        this.cameraStatus = document.getElementById('camera-status');
        this.stream = null;
    }

    /**
     * Start webcam
     */
    async start() {
        if (!this.video) {
            throw new Error('Video element not found');
        }

        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.video.srcObject = this.stream;
            this.video.classList.remove('hidden');
            
            if (this.overlay) {
                this.overlay.classList.remove('hidden');
            }
            
            if (this.videoPlaceholder) {
                this.videoPlaceholder.classList.add('hidden');
            }
            
            if (this.cameraStatus) {
                this.cameraStatus.textContent = "Status: Live feed active.";
            }
            
            return true;
        } catch (err) {
            console.error("Error accessing the camera:", err);
            if (this.cameraStatus) {
                this.cameraStatus.textContent = "Status: CAMERA ACCESS DENIED. Check permissions.";
            }
            throw err;
        }
    }

    /**
     * Stop webcam
     */
    stop() {
        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }
        
        if (this.video) {
            this.video.srcObject = null;
            this.video.classList.add('hidden');
        }
        
        if (this.overlay) {
            this.overlay.classList.add('hidden');
        }
        
        if (this.videoPlaceholder) {
            this.videoPlaceholder.classList.remove('hidden');
            if (this.cameraStatus) {
                this.cameraStatus.textContent = "Status: Camera stopped.";
            }
        }
    }

    /**
     * Get video element
     */
    getVideo() {
        return this.video;
    }

    /**
     * Get overlay canvas element
     */
    getOverlay() {
        return this.overlay;
    }
}
