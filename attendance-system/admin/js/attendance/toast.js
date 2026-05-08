/**
 * Toast Notification Module
 * Handles success and error toast notifications
 */
import { speakAttendanceToast } from './attendanceSpeech.js';

export class Toast {
    constructor(toastId = 'attendance-toast') {
        this.toast = document.getElementById(toastId);
        this.toastTitle = document.getElementById('toast-title');
        this.toastMessage = document.getElementById('toast-message');
        this.toastIcon = this.toast?.querySelector('.toast-icon');
    }

    /**
     * @param {string} title
     * @param {string} message
     * @param {'success'|'error'} [type]
     * @param {{ employeeName?: string }} [speechContext] Optional; success TTS uses title + employeeName only.
     */
    show(title, message, type = 'success', speechContext = null) {
        if (!this.toast) return;

        // Update toast classes based on type
        this.toast.classList.remove("success", "error");
        this.toast.classList.add(type);
        
        if (this.toastIcon) {
            this.toastIcon.classList.remove("success", "error");
            this.toastIcon.classList.add(type);
        }

        // Update toast content
        if (type === 'success') {
            this.toastTitle.textContent = title || "Attendance Logged Successfully";
            this.toastMessage.textContent = message || "Attendance logged successfully";
            
            // Update icon for success
            if (this.toastIcon) {
                this.toastIcon.innerHTML = `
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                `;
            }
        } else {
            this.toastTitle.textContent = title || "Attendance Failed";
            this.toastMessage.textContent = message || "Unable to log attendance";
            
            // Update icon for error
            if (this.toastIcon) {
                this.toastIcon.innerHTML = `
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
            }
        }

        if (this.toastTitle) {
            if (type === 'success') {
                speakAttendanceToast({
                    type: 'success',
                    title: this.toastTitle.textContent,
                    employeeName: speechContext?.employeeName,
                });
            } else {
                speakAttendanceToast({
                    type: 'error',
                    title: this.toastTitle.textContent,
                });
            }
        }

        // Remove any existing hide class and show the toast
        this.toast.classList.remove("hide");
        this.toast.classList.add("show");

        // Auto-hide after 3 seconds
        setTimeout(() => {
            this.toast.classList.remove("show");
            this.toast.classList.add("hide");
            
            // Remove hide class after animation completes
            setTimeout(() => {
                this.toast.classList.remove("hide");
            }, 300);
        }, 3000);
    }
}
