/**
 * Visitors Page Main Entry Point
 * Initializes all modules for the visitors page
 */
import { FaceRecognition } from './faceRecognition.js';
import { WebcamHandler } from './webcamHandler.js';
import { RecognitionLogic } from './recognitionLogic.js';
import { ActivityLogger } from './activityLogger.js';
import { VisitorAPI } from './api.js';
import { StatusUpdater } from './statusUpdater.js';
import { BookingModal } from './bookingModal.js';
import { NonResidentForm } from './nonResidentForm.js';
import { initSidebar } from '../shared/sidebar.js';
import { initSharedClock } from '../shared/clock.js';

// Known faces - will be loaded from API
let labeledDescriptors = [];

const RECOGNITION_THRESHOLD = 0.4; // Lower value = stricter match
const DETECTION_INTERVAL = 1000; // Check every 1000ms (1 second)

// Initialize modules
let faceRecognition;
let webcamHandler;
let recognitionLogic;
let activityLogger;
let visitorAPI;
let statusUpdater;
let bookingModal;
let nonResidentForm;
let faceRecognitionTimeout = null;
const FACE_RECOGNITION_TIMEOUT = 5000; // 5 seconds scan window before showing manual form
let recognitionLocked = false; // lock recognition while a modal/transaction is active

function isAnyModalOpen() {
    const booking = document.getElementById('booking-modal');
    const nonResident = document.getElementById('non-resident-modal');
    const bookingOpen = booking && !booking.classList.contains('hidden');
    const nonResidentOpen = nonResident && !nonResident.classList.contains('hidden');
    return Boolean(bookingOpen || nonResidentOpen);
}

/**
 * Build booking payload from a pending request row (certificate or blotter)
 */
function bookingFromPendingItem(pr) {
    if (!pr) return null;
    const raw = pr.created_at ? String(pr.created_at).replace(' ', 'T') : null;
    const created = raw ? new Date(raw) : new Date();
    const valid = !Number.isNaN(created.getTime());
    const pad = (n) => String(n).padStart(2, '0');
    return {
        booking_id: pr.type === 'certificate' ? `cert:${pr.id}` : `blotter:${pr.id}`,
        request_type: pr.type,
        request_id: pr.id,
        service_name: pr.service_name,
        purpose: pr.purpose,
        appointment_date: valid ? created.toISOString().slice(0, 10) : null,
        appointment_time: valid
            ? `${pad(created.getHours())}:${pad(created.getMinutes())}:${pad(created.getSeconds())}`
            : null,
        status: 'Pending',
        notes: ''
    };
}

/**
 * Handle recognized face
 */
async function handleRecognizedFace(id, name, residentData) {
    if (recognitionLocked) {
        return;
    }
    recognitionLocked = true;
    if (recognitionLogic) {
        recognitionLogic.pause();
    }

    if (recognitionLogic.isLoggedToday(id)) {
        recognitionLocked = false;
        if (recognitionLogic) {
            recognitionLogic.resume();
        }
        return;
    }

    recognitionLogic.markAsLogged(id, 300000);

    const personDetails = labeledDescriptors.find(p => p.id === id);
    statusUpdater.updateRecognized(name, personDetails);

    try {
        const bookingResult = await visitorAPI.checkBooking(id);

        const pendingList = bookingResult.pending_requests || [];
        const hasPending =
            bookingResult.has_pending === true ||
            (Array.isArray(pendingList) && pendingList.length > 0);

        // Scenario A: Exactly 1 pending request → auto-log with that booking
        if (hasPending && pendingList.length === 1) {
            const booking = bookingFromPendingItem(pendingList[0]) || bookingResult.booking;
            if (booking) {
                const logResult = await logResidentWithBooking(residentData, booking);
                if (logResult && logResult.alreadyLogged) {
                    statusUpdater.updateAlreadyLogged(name, booking.service_name || 'Booked Service');
                    stopCamera();
                    return;
                }
                activityLogger.addLogEntry(name, booking.service_name || 'Booked Service');
                refreshVisitorLogs();
                stopCamera();
                bookingModal.showBooking(residentData, booking);
            }
            return;
        }

        // Scenario B: 2+ pending requests → let user pick, then auto-log the chosen one
        if (hasPending && pendingList.length >= 2) {
            bookingModal.showChoosePendingRequest(residentData, pendingList, async (chosen) => {
                const booking = bookingFromPendingItem(chosen);
                if (!booking) return;
                const logResult = await logResidentWithBooking(residentData, booking);
                if (logResult && logResult.alreadyLogged) {
                    statusUpdater.updateAlreadyLogged(name, booking.service_name || 'Booked Service');
                    stopCamera();
                    return;
                }
                activityLogger.addLogEntry(name, booking.service_name || 'Booked Service');
                refreshVisitorLogs();
                stopCamera();
                bookingModal.showBooking(residentData, booking);
            });
            return;
        }

        // Scenario C: No pending requests (completed_only OR pure walk-in)
        // Show service selection modal — resident picks what they came for, then log.
        const services = await visitorAPI.fetchServices(true);
        bookingModal.showServices(residentData, services, async (service) => {
            const logResult = await logResidentWithoutBooking(residentData, service);
            if (logResult && logResult.alreadyLogged) {
                statusUpdater.updateAlreadyLogged(name, service.service_name || service.name || 'Service');
                stopCamera();
                return;
            }
            activityLogger.addLogEntry(name, service.service_name || service.name || 'Service');
            refreshVisitorLogs();
            stopCamera();
        });
    } catch (error) {
        console.error("Error checking booking:", error);
        try {
            const services = await visitorAPI.fetchServices(true);
            bookingModal.showServices(residentData, services, async (service) => {
                const logResult = await logResidentWithoutBooking(residentData, service);
                if (logResult && logResult.alreadyLogged) {
                    statusUpdater.updateAlreadyLogged(name, service.service_name || service.name || 'Service');
                    stopCamera();
                    return;
                }
                activityLogger.addLogEntry(name, service.service_name || service.name || 'Service');
                refreshVisitorLogs();
                stopCamera();
            });
        } catch (serviceError) {
            console.error("Error fetching services:", serviceError);
        }
    } finally {
        if (!isAnyModalOpen()) {
            recognitionLocked = false;
            if (recognitionLogic) {
                recognitionLogic.resume();
            }
            if (statusUpdater) {
                statusUpdater.resetToReady();
            }
        }
    }
}

/**
 * Log resident visitor with booking (Scenario 1)
 */
async function logResidentWithBooking(residentData, booking) {
    try {
        // Fetch address
        const address = await visitorAPI.fetchResidentAddress(residentData.resident_id);
        
        if (!address) {
            console.error("Could not fetch address for resident");
            return { alreadyLogged: false };
        }

        // Log visitor entry
        const result = await visitorAPI.logVisitor({
            resident_id: residentData.resident_id,
            first_name: residentData.first_name,
            middle_name: residentData.middle_name || null,
            last_name: residentData.last_name,
            address: address,
            purpose: booking.service_name || booking.purpose || 'Service',
            is_resident: true,
            had_booking: true,
            booking_id: booking.booking_id || null
        });
        return { alreadyLogged: false, result };
    } catch (error) {
        // Check if the server told us this is a duplicate
        if (error.message && error.message.includes('already been logged')) {
            console.warn('⚠️ Duplicate log prevented:', error.message);
            return { alreadyLogged: true, message: error.message };
        }
        console.error("Error logging resident with booking:", error);
        return { alreadyLogged: false };
    }
}

/**
 * Log resident visitor without booking (Scenario 2)
 */
async function logResidentWithoutBooking(residentData, service) {
    try {
        // Fetch address
        const address = await visitorAPI.fetchResidentAddress(residentData.resident_id);
        
        if (!address) {
            console.error("Could not fetch address for resident");
            return { alreadyLogged: false };
        }

        // Log visitor entry
        const result = await visitorAPI.logVisitor({
            resident_id: residentData.resident_id,
            first_name: residentData.first_name,
            middle_name: residentData.middle_name || null,
            last_name: residentData.last_name,
            address: address,
            purpose: service.service_name || service.name || 'Service',
            is_resident: true,
            had_booking: false
        });

        // Submit service application to external API (if service data provided)
        if (service.external_api_url && service.application_data) {
            try {
                await visitorAPI.submitServiceApplication(
                    service.application_data,
                    service.external_api_url
                );
            } catch (apiError) {
                console.error("Error submitting to external API:", apiError);
                // Continue even if external API fails
            }
        }
        return { alreadyLogged: false, result };
    } catch (error) {
        // Check if the server told us this is a duplicate
        if (error.message && error.message.includes('already been logged')) {
            console.warn('⚠️ Duplicate log prevented:', error.message);
            return { alreadyLogged: true, message: error.message };
        }
        console.error("Error logging resident without booking:", error);
        return { alreadyLogged: false };
    }
}

/**
 * Refresh the visitor activity logs panel from the database
 */
async function refreshVisitorLogs() {
    try {
        const logs = await visitorAPI.fetchRecentLogs(10);
        const logList = document.getElementById('logList');
        if (!logList) return;

        logList.innerHTML = '';

        if (logs.length === 0) {
            logList.innerHTML = '<li class="text-center text-gray-400">No recent activity.</li>';
            return;
        }

        logs.forEach(log => {
            const li = document.createElement('li');
            li.className = 'flex items-center gap-3 py-2';

            const photoSrc = log.photo_url || 'https://placehold.co/36x36/6b7280/white?text=' + encodeURIComponent((log.first_name || '?')[0]);
            const time = log.created_at ? new Date(log.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
            const date = log.created_at ? new Date(log.created_at).toLocaleDateString() : '';
            const residentBadge = log.is_resident == 1
                ? '<span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded">Resident</span>'
                : '<span class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">Non-Resident</span>';
            const bookingBadge = log.had_booking == 1
                ? '<span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded">Booked</span>'
                : '';

            li.innerHTML = `
                <img src="${photoSrc}" alt="" class="w-9 h-9 rounded-full object-cover flex-shrink-0" onerror="this.src='https://placehold.co/36x36/6b7280/white?text=V'">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800 truncate">${log.full_name || 'Unknown'}</p>
                    <p class="text-xs text-gray-500 truncate">${log.purpose || 'N/A'} ${residentBadge} ${bookingBadge}</p>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap">${time}<br>${date}</span>
            `;
            logList.appendChild(li);
        });
    } catch (err) {
        console.error('Error refreshing visitor logs:', err);
    }
}

/**
 * Try again - reset recognition state and allow re-capture
 */
function tryAgain() {
    // Reset recognition state
    if (recognitionLogic) {
        recognitionLogic.resetLoggedState();
    }
    
    // Hide any open modals
    if (bookingModal) {
        bookingModal.hide();
    }
    if (nonResidentForm) {
        nonResidentForm.hide();
    }
    
    // Reset status to ready
    if (statusUpdater) {
        statusUpdater.resetToReady();
    }
    
    // Clear timeout if exists
    if (faceRecognitionTimeout) {
        clearTimeout(faceRecognitionTimeout);
        faceRecognitionTimeout = null;
    }

    recognitionLocked = false;
    if (recognitionLogic) {
        recognitionLogic.resume();
    }

    faceRecognitionTimeout = setTimeout(() => {
        const alreadyLogged = recognitionLogic && recognitionLogic.isLoggedToday('timeout-check');
        if (!alreadyLogged && !isAnyModalOpen()) {
            console.log('No face recognized – showing non-resident form.');
            nonResidentForm.show();
        }
    }, FACE_RECOGNITION_TIMEOUT);
}

/**
 * Initialize face recognition system
 *
 * The webcam is always initialised so the Start Camera button works even when
 * no resident face descriptors could be loaded (falls through to Scenario 3).
 */
async function initializeFaceRecognition() {
    // Always create the webcam handler so camera buttons work regardless of
    // whether face models/descriptors load successfully.
    webcamHandler = new WebcamHandler('webcam-video');

    let faceMatcherReady = false;

    const t0 = performance.now();

    try {
        statusUpdater.updateLoading('Loading residents from database...');

        // Fetch residents and load face-api models in parallel
        const protocol = window.location.protocol;
        const host = window.location.host;
        const pathname = window.location.pathname;
        const basePath = pathname.substring(0, pathname.indexOf('/admin')) || '';
        const modelsUrl = `${protocol}//${host}${basePath}/visitors/models`;

        faceRecognition = new FaceRecognition(modelsUrl);

        const [residents] = await Promise.all([
            visitorAPI.fetchResidents(),
            faceRecognition.loadModels()
        ]);

        const t1 = performance.now();
        console.log(`[perf] Models + residents loaded in ${Math.round(t1 - t0)}ms (${residents.length} residents)`);

        if (residents.length > 0) {
            labeledDescriptors = residents.map(resident => ({
                name: resident.name,
                id: resident.id,
                img: resident.img,
                imgs: resident.imgs || [resident.img],
                resident_id: resident.resident_id,
                data: resident
            }));

            statusUpdater.updateLoading(`Preparing ${labeledDescriptors.length} face models...`);

            try {
                // Pass a progress callback so the UI shows which resident is being processed
                await faceRecognition.initializeFaceMatcher(
                    labeledDescriptors,
                    RECOGNITION_THRESHOLD,
                    (current, total, name, fromCache) => {
                        const pct = Math.round((current / total) * 100);
                        const src = fromCache ? '(cached)' : '(computing)';
                        statusUpdater.updateLoading(
                            `Processing faces: ${current}/${total} (${pct}%) ${src}`
                        );
                    }
                );
                faceMatcherReady = true;
                const t2 = performance.now();
                console.log(`[perf] Face matcher ready in ${Math.round(t2 - t0)}ms total`);
            } catch (descriptorError) {
                console.warn('Face descriptor loading failed (resident photos may be too small):', descriptorError.message);
                console.warn('Camera will still work – unrecognised visitors will use the manual form.');
            }
        } else {
            console.warn('No residents with photos found. Camera will run in manual-only mode.');
        }
    } catch (error) {
        console.error('Error during face recognition setup:', error);
    }

    // Set up recognition loop if we have valid descriptors
    if (faceMatcherReady && faceRecognition && webcamHandler) {
        recognitionLogic = new RecognitionLogic(
            webcamHandler,
            faceRecognition,
            labeledDescriptors,
            DETECTION_INTERVAL
        );

        recognitionLogic.setOnRecognizedCallback((id, name, personData) => {
            if (faceRecognitionTimeout) {
                clearTimeout(faceRecognitionTimeout);
                faceRecognitionTimeout = null;
            }

            let residentData = personData || labeledDescriptors.find(p => p.id === id);
            if (residentData && residentData.data) {
                residentData = {
                    ...residentData.data,
                    id: residentData.id,
                    name: residentData.name
                };
            }

            handleRecognizedFace(id, name, residentData);
        });
    }

    // Always mark ready so the user can click Start Camera
    statusUpdater.updateReady();
    if (!faceMatcherReady) {
        statusUpdater.updateLoading('Ready – camera only (no face data loaded). Click Start Camera.');
        // Override the icon to green so it doesn't look like an error
        const icon = document.getElementById('status-icon');
        if (icon) {
            icon.classList.remove('text-yellow-500', 'text-red-500', 'animate-pulse');
            icon.classList.add('text-green-500');
        }
    }
}

/**
 * Start camera and begin recognition
 */
async function startCamera() {
    const startBtn = document.getElementById('start-camera-btn');
    const stopBtn = document.getElementById('stop-camera-btn');

    if (!webcamHandler) {
        console.error('Webcam handler not initialized');
        return;
    }

    try {
        if (startBtn) {
            startBtn.disabled = true;
            startBtn.textContent = 'Starting...';
        }

        // Reset logged state so detection works again on each new scan session
        if (recognitionLogic) {
            recognitionLogic.resetLoggedState();
        }

        await webcamHandler.start();

        // Start face recognition loop only if descriptors were loaded
        if (recognitionLogic && !recognitionLogic.isRunning()) {
            recognitionLogic.start();
        }

        // Scenario 3 timeout: show non-resident form if no face is matched within 5 seconds.
        faceRecognitionTimeout = setTimeout(() => {
            const alreadyLogged = recognitionLogic && recognitionLogic.isLoggedToday('timeout-check');
            if (!alreadyLogged && !isAnyModalOpen()) {
                console.log('No face recognized – showing non-resident form.');
                nonResidentForm.show();
            }
        }, FACE_RECOGNITION_TIMEOUT);

        if (startBtn) startBtn.classList.add('hidden');
        if (stopBtn) stopBtn.classList.remove('hidden');

        if (statusUpdater) statusUpdater.updateReady();
    } catch (error) {
        console.error("Error starting camera:", error);

        if (startBtn) {
            startBtn.disabled = false;
            startBtn.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Start Camera
            `;
        }

        if (statusUpdater) {
            let errorTitle = 'CAMERA ERROR';
            let errorMessage = 'Camera access denied. Please check permissions and try again.';

            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                errorMessage = 'Camera permission denied. Please allow camera access and click Start again.';
            } else if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
                errorMessage = 'No camera found. Please connect a camera and try again.';
            }

            statusUpdater.updateError(errorTitle, errorMessage);
        }
    }
}

/**
 * Stop camera and pause recognition
 */
function stopCamera() {
    const startBtn = document.getElementById('start-camera-btn');
    const stopBtn = document.getElementById('stop-camera-btn');

    if (!webcamHandler) {
        console.error('Webcam handler not initialized');
        return;
    }

    // Stop webcam
    webcamHandler.stop();

    // Stop recognition logic
    if (recognitionLogic && recognitionLogic.isRunning()) {
        recognitionLogic.stop();
    }

    // Clear timeout if exists
    if (faceRecognitionTimeout) {
        clearTimeout(faceRecognitionTimeout);
        faceRecognitionTimeout = null;
    }

    // Update button visibility
    if (startBtn) {
        startBtn.classList.remove('hidden');
        startBtn.disabled = false;
        startBtn.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Start Camera
        `;
    }
    if (stopBtn) {
        stopBtn.classList.add('hidden');
    }

    // Update status
    if (statusUpdater) {
        statusUpdater.updateReady();
    }
}

/**
 * Initialize all modules
 */
function init() {
    // Initialize status updater
    statusUpdater = new StatusUpdater();
    statusUpdater.clearInitialLogItem();
    
    // Initialize activity logger
    activityLogger = new ActivityLogger();

    // Initialize visitor API
    visitorAPI = new VisitorAPI();

    // Initialize booking modal with tryAgain callback
    bookingModal = new BookingModal(tryAgain);

    // Initialize non-resident form with callbacks for try-again and post-submit
    nonResidentForm = new NonResidentForm(visitorAPI, tryAgain, async (visitorName) => {
        activityLogger.addLogEntry(visitorName || 'Visitor', 'Registered');
        refreshVisitorLogs();
        stopCamera();
    });

    // Initialize sidebar toggle
    initSidebar();
    
    // Initialize shared clock for consistent date display
    initSharedClock();

    // Set up camera control buttons
    const startBtn = document.getElementById('start-camera-btn');
    const stopBtn = document.getElementById('stop-camera-btn');

    if (startBtn) {
        startBtn.addEventListener('click', startCamera);
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', stopCamera);
    }

    // Load initial visitor logs
    refreshVisitorLogs();

    // Start face recognition system (but don't start camera automatically)
    initializeFaceRecognition();

    // Pause/resume scanning when modals open/close
    document.addEventListener('visitor:modal-opened', () => {
        recognitionLocked = true;
        if (recognitionLogic) {
            recognitionLogic.pause();
        }
    });
    document.addEventListener('visitor:modal-closed', () => {
        recognitionLocked = false;
        if (recognitionLogic) {
            recognitionLogic.resume();
        }
        if (statusUpdater) {
            statusUpdater.resetToReady();
        }
    });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    // DOM is already ready
    init();
}
