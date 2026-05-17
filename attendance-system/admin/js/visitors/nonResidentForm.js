/**
 * Non-Resident Visitor Form Module
 * Handles form display and submission for non-resident visitors
 */
export class NonResidentForm {
    constructor(visitorAPI, onTryAgain = null, onSubmitSuccess = null) {
        this.visitorAPI = visitorAPI;
        this.onTryAgain = onTryAgain;
        this.onSubmitSuccess = onSubmitSuccess;
        this.formModal = null;
        this._lookupTimer = null;
        this.createFormModal();
    }

    /**
     * Create form modal HTML structure
     */
    createFormModal() {
        const modalHTML = `
            <div id="non-resident-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex justify-between items-center">
                        <h2 class="text-2xl font-bold text-gray-800">Visitor Registration</h2>
                        <button id="non-resident-close" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6">
                        <p class="text-gray-600 mb-6">Please provide your information to proceed.</p>

                        <!-- Visitor Information Form -->
                        <form id="non-resident-form" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- First Name -->
                                <div>
                                    <label for="visitor-first-name" class="block text-sm font-medium text-gray-700 mb-1">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="visitor-first-name" name="first_name" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Middle Name -->
                                <div>
                                    <label for="visitor-middle-name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Middle Name
                                    </label>
                                    <input type="text" id="visitor-middle-name" name="middle_name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Last Name -->
                                <div>
                                    <label for="visitor-last-name" class="block text-sm font-medium text-gray-700 mb-1">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="visitor-last-name" name="last_name" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Birthdate -->
                                <div>
                                    <label for="visitor-birthdate" class="block text-sm font-medium text-gray-700 mb-1">
                                        Birthdate <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" id="visitor-birthdate" name="birthdate" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="visitor-address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Address <span class="text-red-500">*</span>
                                </label>
                                <textarea id="visitor-address" name="address" rows="3" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="House Number, Street, Barangay, City, Province"></textarea>
                            </div>

                            <div id="visitor-lookup-suggestions" class="hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm max-h-52 overflow-y-auto divide-y divide-gray-100"></div>

                            <!-- Services Section (will be populated dynamically) -->
                            <div id="non-resident-services-section" class="hidden">
                                <h3 class="text-lg font-semibold text-gray-800 mb-3">Select Service</h3>
                                <div id="non-resident-services-list" class="space-y-3">
                                    <!-- Services will be dynamically inserted here -->
                                </div>
                            </div>

                            <!-- Error Message -->
                            <div id="non-resident-error" class="hidden bg-red-50 border border-red-200 rounded-lg p-3 text-red-700 text-sm"></div>
                        </form>
                    </div>

                    <!-- Modal Footer -->
                    <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-between items-center">
                        <button id="non-resident-try-again" class="px-4 py-2 text-blue-600 bg-white border border-blue-300 rounded-lg hover:bg-blue-50 transition-colors">
                            Try Again (Face Recognition)
                        </button>
                        <div class="flex justify-end space-x-3 ml-auto">
                            <button id="non-resident-cancel" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button id="non-resident-submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Continue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.formModal = document.getElementById('non-resident-modal');

        // Add event listeners
        const closeBtn = document.getElementById('non-resident-close');
        const cancelBtn = document.getElementById('non-resident-cancel');
        const submitBtn = document.getElementById('non-resident-submit');
        const tryAgainBtn = document.getElementById('non-resident-try-again');
        const form = document.getElementById('non-resident-form');

        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.hide());
        }
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => this.hide());
        }
        if (submitBtn) {
            submitBtn.addEventListener('click', () => this.handleSubmit());
        }
        if (tryAgainBtn) {
            tryAgainBtn.addEventListener('click', () => {
                if (this.onTryAgain && typeof this.onTryAgain === 'function') {
                    this.onTryAgain();
                }
            });
        }

        // Close on backdrop click
        this.formModal.addEventListener('click', (e) => {
            if (e.target === this.formModal) {
                this.hide();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !this.formModal.classList.contains('hidden')) {
                this.hide();
            }
        });

        // Prevent form submission on Enter (we'll handle it manually)
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleSubmit();
            });
        }

        this._bindNameLookup();
    }

    _bindNameLookup() {
        const fn = document.getElementById('visitor-first-name');
        const ln = document.getElementById('visitor-last-name');
        const mn = document.getElementById('visitor-middle-name');
        const addr = document.getElementById('visitor-address');

        // Auto-capitalize first letter of each word
        const capitalize = (e) => {
            const input = e.target;
            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = input.value.replace(/\b[a-z]/g, char => char.toUpperCase());
            input.setSelectionRange(start, end);
        };

        if (fn) {
            fn.addEventListener('input', capitalize);
            fn.addEventListener('input', schedule);
        }
        if (ln) {
            ln.addEventListener('input', capitalize);
            ln.addEventListener('input', schedule);
        }
        if (mn) mn.addEventListener('input', capitalize);
        if (addr) addr.addEventListener('input', capitalize);

        let lookupTimer = null;
        function schedule() {
            if (lookupTimer) clearTimeout(lookupTimer);
            lookupTimer = setTimeout(() => {
                // Ensure this refers to the class instance
                const evt = new CustomEvent('run-name-lookup');
                document.dispatchEvent(evt);
            }, 320);
        }
        
        document.addEventListener('run-name-lookup', () => this._runNameLookup());
    }

    _hideSuggestions() {
        const box = document.getElementById('visitor-lookup-suggestions');
        if (box) {
            box.classList.add('hidden');
            box.innerHTML = '';
        }
    }

    async _runNameLookup() {
        const fn = (document.getElementById('visitor-first-name')?.value || '').trim();
        const ln = (document.getElementById('visitor-last-name')?.value || '').trim();
        const q = `${fn} ${ln}`.trim();
        const box = document.getElementById('visitor-lookup-suggestions');
        if (!box || q.length < 2) {
            this._hideSuggestions();
            return;
        }

        const data = await this.visitorAPI.lookupVisitorNames(q);
        const prof = data.profiling || [];
        const prev = data.previous_visitors || [];
        
        // Auto-fill logic if exact match is found
        const exactPrevMatch = prev.find(row => 
            (row.first_name || '').toLowerCase() === fn.toLowerCase() && 
            (row.last_name || '').toLowerCase() === ln.toLowerCase()
        );
        
        if (exactPrevMatch) {
            this._applySuggestionFromVisitorLog(exactPrevMatch);
            this._hideSuggestions();
            return; // Stop here since we auto-filled
        }
        
        if (prof.length === 0 && prev.length === 0) {
            this._hideSuggestions();
            return;
        }

        box.innerHTML = '';
        const addHeading = (label) => {
            const h = document.createElement('p');
            h.className = 'px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide bg-white sticky top-0';
            h.textContent = label;
            box.appendChild(h);
        };

        if (prof.length > 0) {
            addHeading('Profiling (residents)');
            prof.forEach((row) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-blue-50 text-gray-800';
                btn.textContent = `${row.full_name || ''}${row.resident_id ? ` · ID ${row.resident_id}` : ''}`;
                btn.addEventListener('click', () => {
                    this._applySuggestionFromProfiling(row);
                    this._hideSuggestions();
                });
                box.appendChild(btn);
            });
        }
        if (prev.length > 0) {
            addHeading('Previous visitors');
            prev.forEach((row) => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-3 py-2 text-sm hover:bg-blue-50 text-gray-800';
                btn.textContent = row.full_name || `${row.first_name} ${row.last_name}`;
                btn.addEventListener('click', () => {
                    this._applySuggestionFromVisitorLog(row);
                    this._hideSuggestions();
                });
                box.appendChild(btn);
            });
        }
        box.classList.remove('hidden');
    }

    _applySuggestionFromProfiling(row) {
        const set = (id, v) => {
            const el = document.getElementById(id);
            if (el && v) el.value = v;
        };
        set('visitor-first-name', row.first_name);
        set('visitor-middle-name', row.middle_name || '');
        set('visitor-last-name', row.last_name);
        if (row.birthdate) set('visitor-birthdate', String(row.birthdate).slice(0, 10));
        if (row.address_hint) set('visitor-address', row.address_hint);
    }

    _applySuggestionFromVisitorLog(row) {
        const set = (id, v) => {
            const el = document.getElementById(id);
            if (el && v) el.value = v;
        };
        set('visitor-first-name', row.first_name);
        set('visitor-middle-name', row.middle_name || '');
        set('visitor-last-name', row.last_name);
        if (row.birthdate) set('visitor-birthdate', String(row.birthdate).slice(0, 10));
        if (row.address) set('visitor-address', row.address);
    }

    /**
     * Show form for non-resident visitor
     */
    async show() {
        if (!this.formModal) return;

        // Reset form
        const form = document.getElementById('non-resident-form');
        if (form) form.reset();

        this._hideSuggestions();

        // Hide error
        const errorDiv = document.getElementById('non-resident-error');
        if (errorDiv) {
            errorDiv.classList.add('hidden');
            errorDiv.textContent = '';
        }

        // Reuse last non-resident log as default (optional prefill)
        try {
            const last = await this.visitorAPI.fetchLastNonResident();
            if (last) {
                const set = (id, v) => {
                    const el = document.getElementById(id);
                    if (el && v) el.value = v;
                };
                set('visitor-first-name', last.first_name);
                set('visitor-middle-name', last.middle_name || '');
                set('visitor-last-name', last.last_name);
                if (last.birthdate) set('visitor-birthdate', String(last.birthdate).slice(0, 10));
                set('visitor-address', last.address || '');
            }
        } catch (e) {
            console.warn('last non-resident prefill skipped', e);
        }

        // Fetch services
        try {
            const services = await this.visitorAPI.fetchServices(false);
            this.populateServices(services);
        } catch (error) {
            console.error('Error fetching services:', error);
            this.showError('Failed to load services. Please try again.');
        }

        // Show modal
        this.formModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        document.dispatchEvent(new CustomEvent('visitor:modal-opened', { detail: { id: 'non-resident-modal' } }));
    }

    /**
     * Hide form modal
     */
    hide() {
        this._hideSuggestions();
        if (this.formModal) {
            this.formModal.classList.add('hidden');
            document.body.style.overflow = '';
            document.dispatchEvent(new CustomEvent('visitor:modal-closed', { detail: { id: 'non-resident-modal' } }));
        }
    }

    /**
     * Populate services list
     */
    populateServices(services) {
        const servicesSection = document.getElementById('non-resident-services-section');
        const servicesList = document.getElementById('non-resident-services-list');

        if (!servicesSection || !servicesList) return;

        if (services.length === 0) {
            servicesSection.classList.add('hidden');
            return;
        }

        servicesSection.classList.remove('hidden');
        servicesList.innerHTML = '';

        services.forEach(service => {
            const sid = String(service.service_id).replace(/[^a-zA-Z0-9_-]/g, '_');
            const serviceCard = document.createElement('div');
            serviceCard.className = 'border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition-all cursor-pointer';
            serviceCard.dataset.serviceId = String(service.service_id);
            serviceCard.dataset.serviceName = service.service_name;
            serviceCard.innerHTML = `
                <div class="flex items-center">
                    <input type="radio" name="service" value="${this.escapeHtml(String(service.service_id))}" id="service-${sid}" 
                        class="mt-0.5 mr-3">
                    <label for="service-${sid}" class="flex-1 cursor-pointer">
                        <h5 class="font-semibold text-gray-800">${this.escapeHtml(service.service_name || 'Service')}</h5>
                    </label>
                </div>
            `;

            // Add click handler to select radio
            serviceCard.addEventListener('click', (e) => {
                if (e.target.type !== 'radio') {
                    const radio = serviceCard.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                }
            });

            servicesList.appendChild(serviceCard);
        });

        const otherCard = document.createElement('div');
        otherCard.className = 'border border-dashed border-gray-300 rounded-lg p-4 space-y-2';
        otherCard.dataset.serviceName = 'Other';
        otherCard.innerHTML = `
            <div class="flex items-start gap-3">
                <input type="radio" name="service" value="other" id="service-other" class="mt-1">
                <div class="flex-1 min-w-0">
                    <label for="service-other" class="font-semibold text-gray-800 cursor-pointer">Other (specify)</label>
                    <input type="text" id="non-resident-other-purpose" maxlength="255"
                        class="mt-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describe the service">
                </div>
            </div>
        `;
        otherCard.addEventListener('click', (e) => {
            if (e.target.type !== 'text') {
                const radio = otherCard.querySelector('#service-other');
                if (radio) radio.checked = true;
            }
        });
        servicesList.appendChild(otherCard);
    }

    /**
     * Handle form submission
     */
    async handleSubmit() {
        const form = document.getElementById('non-resident-form');
        if (!form) return;

        // Validate form
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Get form data
        const formData = new FormData(form);
        const visitorData = {
            first_name: formData.get('first_name').trim(),
            middle_name: formData.get('middle_name')?.trim() || null,
            last_name: formData.get('last_name').trim(),
            birthdate: formData.get('birthdate'),
            address: formData.get('address').trim()
        };

        // Get selected service
        const selectedServiceRadio = form.querySelector('input[name="service"]:checked');
        if (!selectedServiceRadio) {
            this.showError('Please select a service.');
            return;
        }

        let selectedService;
        if (selectedServiceRadio.value === 'other') {
            const custom = (document.getElementById('non-resident-other-purpose')?.value || '').trim();
            if (!custom) {
                this.showError('Please describe the service for “Other”.');
                return;
            }
            selectedService = { service_id: 'other', service_name: custom };
        } else {
            selectedService = {
                service_id: selectedServiceRadio.value,
                service_name: selectedServiceRadio.closest('[data-service-name]')?.dataset.serviceName || 'Service'
            };
        }

        // Validate required fields
        if (!visitorData.first_name || !visitorData.last_name || !visitorData.birthdate || !visitorData.address) {
            this.showError('Please fill in all required fields.');
            return;
        }

        try {
            // Log visitor entry
            await this.visitorAPI.logVisitor({
                resident_id: null,
                first_name: visitorData.first_name,
                middle_name: visitorData.middle_name,
                last_name: visitorData.last_name,
                birthdate: visitorData.birthdate,
                address: visitorData.address,
                purpose: selectedService.service_name,
                is_resident: false,
                had_booking: false
            });

            // Submit service application to external API (if configured)
            // Note: Service application data structure depends on external API requirements
            // This is a placeholder - adjust based on actual API requirements
            if (selectedService.external_api_url) {
                const servicePayload = {
                    visitor: visitorData,
                    service: selectedService
                };
                
                try {
                    await this.visitorAPI.submitServiceApplication(
                        servicePayload,
                        selectedService.external_api_url
                    );
                } catch (apiError) {
                    console.error('Error submitting to external API:', apiError);
                    // Continue even if external API fails
                }
            }

            this.hide();

            if (this.onSubmitSuccess && typeof this.onSubmitSuccess === 'function') {
                const visitorName = `${visitorData.first_name} ${visitorData.last_name}`;
                await this.onSubmitSuccess(visitorName);
            }

        } catch (error) {
            console.error('Error submitting form:', error);
            this.showError(error.message || 'Failed to register visitor. Please try again.');
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        const errorDiv = document.getElementById('non-resident-error');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
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
