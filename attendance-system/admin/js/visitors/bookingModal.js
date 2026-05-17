/**
 * Booking Modal Module
 * Handles displaying booking information or available services
 */
export class BookingModal {
    constructor(onTryAgain = null) {
        this.modal = null;
        this.onTryAgain = onTryAgain;
        this.createModal();
    }

    /**
     * Create modal HTML structure
     */
    createModal() {
        // Create modal container
        const modalHTML = `
            <div id="booking-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                        <h2 id="modal-title" class="text-2xl font-bold text-gray-800">Visitor Information</h2>
                        <button id="modal-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <!-- Visitor Info Section -->
                        <div id="visitor-info" class="mb-6 pb-6 border-b border-gray-200">
                            <div class="flex items-center space-x-4">
                                <img id="modal-visitor-photo" src="" alt="Visitor Photo" class="w-20 h-20 rounded-full object-cover border-4 border-blue-500">
                                <div>
                                    <h3 id="modal-visitor-name" class="text-xl font-semibold text-gray-800"></h3>
                                    <p id="modal-visitor-id" class="text-sm text-gray-500"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Info Section -->
                        <div id="booking-info" class="hidden">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center space-x-2 mb-2">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-green-800">You have a booking!</h4>
                                </div>
                                <div class="space-y-2 text-gray-700">
                                    <p><span class="font-medium">Service:</span> <span id="booking-service"></span></p>
                                    <p><span class="font-medium">Date:</span> <span id="booking-date"></span></p>
                                    <p><span class="font-medium">Time:</span> <span id="booking-time"></span></p>
                                    <p><span class="font-medium">Status:</span> <span id="booking-status" class="px-2 py-1 rounded text-sm"></span></p>
                                    <p id="booking-notes" class="text-sm text-gray-600 mt-2"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Completed requests (no new log) -->
                        <div id="completed-requests-section" class="hidden">
                            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                                <h4 class="text-lg font-semibold text-amber-900 mb-2">Request already completed</h4>
                                <p id="completed-requests-message" class="text-amber-900 text-sm leading-relaxed"></p>
                            </div>
                        </div>

                        <!-- Multiple pending requests: pick one -->
                        <div id="pending-choice-section" class="hidden">
                            <p class="text-sm text-gray-600 mb-3">You have more than one pending request. Select the one you are checking in for:</p>
                            <div id="pending-choice-list" class="space-y-3"></div>
                        </div>

                        <!-- Services Section -->
                        <div id="services-section" class="hidden">
                            <div class="mb-4">
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">Available Services</h4>
                                <p class="text-sm text-gray-600">Please select a service you would like to avail:</p>
                            </div>
                            <div id="services-list" class="space-y-3">
                                <!-- Services will be dynamically inserted here -->
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-between items-center">
                        <button id="modal-try-again" class="px-4 py-2 text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors hidden">
                            Try Again (Face Recognition)
                        </button>
                        <div class="flex justify-end space-x-3 ml-auto">
                            <button id="modal-cancel" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert modal into body
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('booking-modal');

        // Add event listeners
        const closeBtn = document.getElementById('modal-close');
        const cancelBtn = document.getElementById('modal-cancel');
        const tryAgainBtn = document.getElementById('modal-try-again');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hide());
        }
        if (tryAgainBtn) {
            tryAgainBtn.addEventListener('click', () => {
                if (this.onTryAgain && typeof this.onTryAgain === 'function') {
                    this.onTryAgain();
                }
            });
        }

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.hide();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.modal.classList.contains('hidden')) {
                this.hide();
            }
        });
    }

    /**
     * Show booking information (Scenario 1: auto-logged, brief confirmation)
     */
    showBooking(residentData, booking) {
        if (!this.modal) return;

        const visitorPhoto = document.getElementById('modal-visitor-photo');
        const visitorName = document.getElementById('modal-visitor-name');
        const visitorId = document.getElementById('modal-visitor-id');

        if (visitorPhoto && residentData?.img) {
            visitorPhoto.src = residentData.img;
        }
        if (visitorName && residentData?.name) {
            visitorName.textContent = residentData.name;
        }
        if (visitorId) {
            visitorId.textContent = `Resident ID: ${residentData?.resident_id || residentData?.id || ''}`;
        }

        const bookingInfo = document.getElementById('booking-info');
        const bookingService = document.getElementById('booking-service');
        const bookingDate = document.getElementById('booking-date');
        const bookingTime = document.getElementById('booking-time');
        const bookingStatus = document.getElementById('booking-status');
        const bookingNotes = document.getElementById('booking-notes');

        const pendingChoiceEl = document.getElementById('pending-choice-section');
        if (pendingChoiceEl) pendingChoiceEl.classList.add('hidden');
        const completedSecBk = document.getElementById('completed-requests-section');
        if (completedSecBk) completedSecBk.classList.add('hidden');

        if (bookingInfo) bookingInfo.classList.remove('hidden');
        if (bookingService) bookingService.textContent = booking.service_name || 'N/A';

        if (bookingDate && booking.appointment_date) {
            const date = new Date(booking.appointment_date);
            bookingDate.textContent = date.toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        if (bookingTime && booking.appointment_time) {
            bookingTime.textContent = booking.appointment_time;
        }

        if (bookingStatus) {
            bookingStatus.textContent = booking.status || 'Pending';
            const st = (booking.status || '').toLowerCase();
            const statusClass = st === 'confirmed' ? 'bg-green-100 text-green-800' :
                              st === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                              'bg-gray-100 text-gray-800';
            bookingStatus.className = `px-2 py-1 rounded text-sm ${statusClass}`;
        }

        if (bookingNotes && booking.notes) {
            bookingNotes.textContent = booking.notes;
            bookingNotes.classList.remove('hidden');
        } else if (bookingNotes) {
            bookingNotes.classList.add('hidden');
        }

        const servicesSection = document.getElementById('services-section');
        if (servicesSection) servicesSection.classList.add('hidden');

        const completedSec = document.getElementById('completed-requests-section');
        if (completedSec) completedSec.classList.add('hidden');
        const pendingChoice = document.getElementById('pending-choice-section');
        if (pendingChoice) pendingChoice.classList.add('hidden');

        const tryAgainBtn = document.getElementById('modal-try-again');
        if (tryAgainBtn) {
            tryAgainBtn.classList.add('hidden');
        }

        const modalTitle = document.getElementById('modal-title');
        if (modalTitle) modalTitle.textContent = 'Visit logged';

        this.show();

        // Auto-close after 5 seconds, then stop camera and close modal
        this._autoCloseTimer = setTimeout(() => {
            this.hide();
        }, 5000);
    }

    /**
     * Show available services
     * @param {Object} residentData - Resident data (null for non-residents)
     * @param {Array} services - Available services
     * @param {Function} onServiceSelect - Callback when service is selected
     */
    showServices(residentData, services, onServiceSelect = null) {
        if (!this.modal) return;

        // Set visitor info
        const visitorPhoto = document.getElementById('modal-visitor-photo');
        const visitorName = document.getElementById('modal-visitor-name');
        const visitorId = document.getElementById('modal-visitor-id');

        if (visitorPhoto && residentData?.img) {
            visitorPhoto.src = residentData.img;
        }
        if (visitorName && residentData?.name) {
            visitorName.textContent = residentData.name;
        }
        if (visitorId) {
            if (residentData?.phil_sys_number) {
                visitorId.textContent = `PhilSys: ${residentData.phil_sys_number}`;
            } else if (residentData?.resident_id != null || residentData?.id != null) {
                visitorId.textContent = `Resident ID: ${residentData.resident_id ?? residentData.id}`;
            } else {
                visitorId.textContent = '';
            }
        }

        // Hide booking info
        const bookingInfo = document.getElementById('booking-info');
        if (bookingInfo) bookingInfo.classList.add('hidden');
        const completedSec = document.getElementById('completed-requests-section');
        if (completedSec) completedSec.classList.add('hidden');
        const pendingChoice = document.getElementById('pending-choice-section');
        if (pendingChoice) pendingChoice.classList.add('hidden');

        // Show services section
        const servicesSection = document.getElementById('services-section');
        const servicesList = document.getElementById('services-list');
        
        if (servicesSection) servicesSection.classList.remove('hidden');
        
        // Show try again button when services are shown (no appointment)
        const tryAgainBtn = document.getElementById('modal-try-again');
        if (tryAgainBtn) {
            tryAgainBtn.classList.remove('hidden');
        }
        
        if (servicesList) {
            servicesList.innerHTML = '';
            
            if (services.length === 0) {
                servicesList.innerHTML = '<p class="text-gray-500 text-center py-4">No services available at this time.</p>';
            } else {
                services.forEach(service => {
                    const serviceCard = document.createElement('div');
                    serviceCard.className = 'border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all cursor-pointer';
                    serviceCard.innerHTML = `
                        <div class="flex justify-between items-center gap-3">
                            <div class="flex-1 min-w-0">
                                <h5 class="font-semibold text-gray-800">${this.escapeHtml(service.service_name || 'Service')}</h5>
                            </div>
                            <button type="button" class="flex-shrink-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                                Select
                            </button>
                        </div>
                    `;
                    
                    // Add click handler
                    const selectBtn = serviceCard.querySelector('button');
                    selectBtn.addEventListener('click', () => {
                        this.handleServiceSelection(service, residentData, onServiceSelect);
                    });
                    
                    servicesList.appendChild(serviceCard);
                });

                const otherCard = document.createElement('div');
                otherCard.className = 'border border-dashed border-gray-300 rounded-lg p-4 space-y-3';
                otherCard.innerHTML = `
                    <h5 class="font-semibold text-gray-800">Other (specify)</h5>
                    <p class="text-sm text-gray-600">If the service you need is not listed, describe it below.</p>
                    <input type="text" id="booking-other-service-input" maxlength="255"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Type the service name">
                    <button type="button" id="booking-other-service-btn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Continue with this service
                    </button>
                `;
                servicesList.appendChild(otherCard);
                const otherBtn = otherCard.querySelector('#booking-other-service-btn');
                const otherInput = otherCard.querySelector('#booking-other-service-input');
                if (otherBtn && otherInput) {
                    otherBtn.addEventListener('click', () => {
                        const custom = (otherInput.value || '').trim();
                        if (!custom) {
                            otherInput.focus();
                            return;
                        }
                        const customService = {
                            service_id: 'other',
                            service_name: custom,
                            description: ''
                        };
                        this.handleServiceSelection(customService, residentData, onServiceSelect);
                    });
                }
            }
        }

        // Update modal title
        const modalTitle = document.getElementById('modal-title');
        if (modalTitle) modalTitle.textContent = 'Available Services';

        // Show modal
        this.show();
    }

    /**
     * No pending requests — existing records are already completed (no log created)
     */
    showRequestsCompleted(residentData, message) {
        if (!this.modal) return;

        const visitorPhoto = document.getElementById('modal-visitor-photo');
        const visitorName = document.getElementById('modal-visitor-name');
        const visitorId = document.getElementById('modal-visitor-id');

        if (visitorPhoto && residentData?.img) {
            visitorPhoto.src = residentData.img;
        }
        if (visitorName && residentData?.name) {
            visitorName.textContent = residentData.name;
        }
        if (visitorId) {
            visitorId.textContent = `Resident ID: ${residentData?.resident_id ?? residentData?.id ?? ''}`;
        }

        const bookingInfo = document.getElementById('booking-info');
        if (bookingInfo) bookingInfo.classList.add('hidden');
        const servicesSection = document.getElementById('services-section');
        if (servicesSection) servicesSection.classList.add('hidden');
        const pendingChoice = document.getElementById('pending-choice-section');
        if (pendingChoice) pendingChoice.classList.add('hidden');

        const completedSec = document.getElementById('completed-requests-section');
        const completedMsg = document.getElementById('completed-requests-message');
        if (completedSec) completedSec.classList.remove('hidden');
        if (completedMsg) {
            completedMsg.textContent = message || 'Your requests are already completed. No log entry was created.';
        }

        const tryAgainBtn = document.getElementById('modal-try-again');
        if (tryAgainBtn) {
            tryAgainBtn.classList.remove('hidden');
        }

        const modalTitle = document.getElementById('modal-title');
        if (modalTitle) modalTitle.textContent = 'Check-in not required';

        this.show();
    }

    /**
     * Two pending requests — visitor picks which one applies to this visit
     */
    showChoosePendingRequest(residentData, pendingRequests, onSelect) {
        if (!this.modal || !Array.isArray(pendingRequests) || pendingRequests.length < 2) return;

        const visitorPhoto = document.getElementById('modal-visitor-photo');
        const visitorName = document.getElementById('modal-visitor-name');
        const visitorId = document.getElementById('modal-visitor-id');

        if (visitorPhoto && residentData?.img) {
            visitorPhoto.src = residentData.img;
        }
        if (visitorName && residentData?.name) {
            visitorName.textContent = residentData.name;
        }
        if (visitorId) {
            visitorId.textContent = `Resident ID: ${residentData?.resident_id ?? residentData?.id ?? ''}`;
        }

        const bookingInfo = document.getElementById('booking-info');
        if (bookingInfo) bookingInfo.classList.add('hidden');
        const servicesSection = document.getElementById('services-section');
        if (servicesSection) servicesSection.classList.add('hidden');
        const completedSec = document.getElementById('completed-requests-section');
        if (completedSec) completedSec.classList.add('hidden');

        const pendingChoice = document.getElementById('pending-choice-section');
        const listEl = document.getElementById('pending-choice-list');
        if (pendingChoice) pendingChoice.classList.remove('hidden');
        if (listEl) {
            listEl.innerHTML = '';
            pendingRequests.forEach((req) => {
                const row = document.createElement('div');
                row.className = 'border border-gray-200 rounded-lg p-4 flex justify-between items-center gap-3';
                const typeLabel = req.type === 'blotter' ? 'Blotter' : 'Certificate';
                row.innerHTML = `
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase">${this.escapeHtml(typeLabel)}</p>
                        <p class="font-semibold text-gray-800 truncate">${this.escapeHtml(req.service_name || 'Request')}</p>
                        <p class="text-sm text-gray-600 truncate">${this.escapeHtml(req.purpose || '')}</p>
                    </div>
                    <button type="button" class="flex-shrink-0 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium">
                        Select
                    </button>
                `;
                const btn = row.querySelector('button');
                btn.addEventListener('click', async () => {
                    if (typeof onSelect === 'function') {
                        await onSelect(req);
                    }
                });
                listEl.appendChild(row);
            });
        }

        const tryAgainBtn = document.getElementById('modal-try-again');
        if (tryAgainBtn) {
            tryAgainBtn.classList.remove('hidden');
        }

        const modalTitle = document.getElementById('modal-title');
        if (modalTitle) modalTitle.textContent = 'Select pending request';

        this.show();
    }

    /**
     * Handle service selection
     */
    async handleServiceSelection(service, residentData, onServiceSelect) {
        if (onServiceSelect && typeof onServiceSelect === 'function') {
            await onServiceSelect(service);
        }
        this.hide();
    }

    /**
     * Show modal
     */
    show() {
        if (this.modal) {
            this.modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            document.dispatchEvent(new CustomEvent('visitor:modal-opened', { detail: { id: 'booking-modal' } }));
        }
    }

    /**
     * Hide modal
     */
    hide() {
        if (this._autoCloseTimer) {
            clearTimeout(this._autoCloseTimer);
            this._autoCloseTimer = null;
        }
        if (this.modal) {
            this.modal.classList.add('hidden');
            document.body.style.overflow = '';
            document.dispatchEvent(new CustomEvent('visitor:modal-closed', { detail: { id: 'booking-modal' } }));
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
