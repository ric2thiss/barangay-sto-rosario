<div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    {{-- Page Header --}}
    <div class="mb-10 text-center">
        <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Issue New Certificate</h1>
        <p class="text-slate-500 mt-2">Generate official documents for community residents and stakeholders</p>
    </div>

    <form wire:submit="save" class="space-y-8">
        
        {{-- Main Form Card --}}
        <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
            
            {{-- Form Body --}}
            <div class="p-8 lg:p-10 space-y-10">
                
                {{-- Section 1: Resident & Certificate Type --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    
                    {{-- Resident Selection --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 text-sm">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Resident Information</h3>
                        </div>

                        @if($resident_id && $selectedResident)
                            <div class="relative group animate-in fade-in zoom-in-95 duration-300">
                                <div class="flex items-center justify-between p-4 rounded-2xl border-2 border-blue-100 bg-blue-50/50 transition-all">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-lg font-bold">
                                            {{ substr($selectedResidentName, 0, 1) }}
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-sm font-black text-slate-900">{{ $selectedResidentName }}</span>
                                            <span class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">Selected Resident</span>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="clearResident"
                                            class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all"
                                            title="Remove Resident">
                                        <i class="fas fa-times text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        @else
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-search text-sm"></i>
                                </div>
                                <input wire:model.live.debounce.400ms="searchResident" type="text"
                                       placeholder="Search resident by name..."
                                       class="block w-full pl-11 pr-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            </div>

                            @if(strlen($searchResident) > 0)
                                <div class="mt-2 rounded-2xl border border-slate-100 bg-white shadow-lg overflow-hidden animate-in slide-in-from-top-2 duration-200 z-10 relative">
                                    @if($residents->count() > 0)
                                        <div class="max-h-60 overflow-y-auto divide-y divide-slate-50">
                                            @foreach($residents as $resident)
                                                <button type="button" wire:click="$set('resident_id', {{ $resident->id }})"
                                                        class="w-full text-left px-5 py-3 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-slate-700 group-hover:text-blue-600 transition-colors">{{ $resident->last_name }}, {{ $resident->first_name }} {{ $resident->middle_name }}</span>
                                                        <span class="text-[10px] text-slate-400 uppercase font-medium">Resident ID: {{ $resident->resident_id }}</span>
                                                    </div>
                                                    <i class="fas fa-chevron-right text-[10px] text-slate-300 group-hover:text-blue-400 transition-all transform group-hover:translate-x-1"></i>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-5 text-center">
                                            <p class="text-xs font-bold text-slate-400 italic">No residents found matching "{{ $searchResident }}"</p>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-tighter mt-2 ml-1 italic opacity-60">Start typing to search our registry...</p>
                            @endif
                        @endif
                        @error('resident_id') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Certificate Type --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center text-indigo-600 text-sm">
                                <i class="fas fa-file-contract"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Document Details</h3>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Type of Document</label>
                                <select wire:model.live="certificate_type_id" id="certificate_type_id" 
                                        class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer">
                                    <option value="">&mdash; Select Certificate Type &mdash;</option>
                                    @foreach($certificateTypes as $type)
                                        <option value="{{ $type->certificate_type_id }}">{{ $type->certificate_name }}</option>
                                    @endforeach
                                </select>
                                @error('certificate_type_id') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Primary Purpose</label>
                                <input wire:model="purpose" type="text" id="purpose" placeholder="e.g. For Employment, Travel, etc."
                                       class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                @error('purpose') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-slate-100"></div>

                {{-- Section 2: Timeline & Status --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-sm">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Schedule & Status</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Request Date</label>
                                <input wire:model="date_requested" type="date" id="date_requested" 
                                       class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                @error('date_requested') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>

                            @if(!$isPendingOnly)
                                <div>
                                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Current Workflow State</label>
                                    <select wire:model="status" id="status" 
                                            class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        <option value="Pending">Pending Review</option>
                                        <option value="Processing">In-Process</option>
                                        <option value="Released">Released</option>
                                        <option value="Rejected">Rejected</option>
                                    </select>
                                    @error('status') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                                </div>
                            @else
                                <input type="hidden" wire:model="status" value="Pending">
                            @endif
                        </div>
                    </div>

                    {{-- Section 3: Financials --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 text-sm">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Payment Assessment</h3>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            @if(!$isPendingOnly)
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Payment Status</label>
                                <select wire:model="payment_status" id="payment_status" @disabled($isFreeCertificate) 
                                        class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Awaiting Payment</option>
                                </select>
                            </div>
                            @else
                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Payment Method</label>
                                <div class="px-4 py-3 bg-slate-100 border border-slate-200 rounded-2xl text-sm font-semibold text-slate-600 text-center">
                                    @if($paymentMethod === 'online') <span class="text-blue-600">Online Payment</span>
                                    @elseif($paymentMethod === 'cash') <span class="text-emerald-600">Cash Payment</span>
                                    @else <span class="text-slate-400">Not Selected</span>
                                    @endif
                                </div>
                                <input type="hidden" wire:model="payment_status" value="Pending">
                            </div>
                            @endif

                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Base Fee</label>
                                @if($isPendingOnly)
                                    <div class="px-4 py-3 bg-slate-100 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 text-center">
                                        {{ number_format((float)$amount, 2) }}
                                    </div>
                                    <input type="hidden" wire:model="amount">
                                @else
                                    <input wire:model.live="amount" type="number" id="amount" @readonly($isFreeCertificate) @disabled($isFreeCertificate)
                                           class="block w-full min-w-0 px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all disabled:opacity-50">
                                @endif
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Total</label>
                                <div class="px-4 py-3 bg-slate-100 border border-slate-200 rounded-2xl text-sm font-black text-slate-900 text-center">
                                    {{ $total_amount }}
                                </div>
                            </div>
                        </div>

                        @if($isFreeCertificate)
                            <div class="mt-2 p-3 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center gap-3 animate-in fade-in slide-in-from-left-2 duration-300">
                                <i class="fas fa-info-circle text-emerald-500"></i>
                                <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Free Certificate: Assessment bypass enabled</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Form Footer --}}
            <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-slate-400">
                    <i class="fas fa-shield-alt text-xs"></i>
                    <span class="text-[10px] font-bold uppercase tracking-widest">Official Document Authorization</span>
                </div>

                @error('payment') <p class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</p> @enderror

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ $isPendingOnly ? route('certificates.resident_index') : route('certificates.index') }}"
                       class="flex-1 sm:flex-none px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-all text-center uppercase tracking-widest">
                        Discard
                    </a>

                    @if($isPendingOnly && !$isFreeCertificate)
                        @if(!$paymentCompleted)
                        <button type="button" wire:click="openPaymentModal"
                                class="flex-1 sm:flex-none px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black shadow-lg shadow-emerald-200 transition-all uppercase tracking-widest">
                            <i class="fas fa-wallet mr-1"></i> Proceed to Payment
                        </button>
                        @endif
                        <button type="submit" {{ !$paymentCompleted ? 'disabled' : '' }}
                                class="flex-1 sm:flex-none px-6 py-2.5 rounded-xl text-xs font-black shadow-lg transition-all uppercase tracking-widest {{ $paymentCompleted ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-blue-200' : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none' }}">
                            Submit Request
                        </button>
                    @else
                        <button type="submit" class="flex-1 sm:flex-none px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black shadow-lg shadow-blue-200 transition-all uppercase tracking-widest">
                            Submit Request
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Payment Modal (Resident Only) --}}
    @if($isPendingOnly && !$isFreeCertificate && $showPaymentModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

            {{-- Modal Header --}}
            <div class="px-8 pt-8 pb-4 text-center border-b border-slate-100">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-2xl mx-auto mb-3">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <h2 class="text-xl font-black text-slate-900">
                    {{ $paymentStep === 'choose' ? 'Choose Payment Method' : 'Online Payment' }}
                </h2>
                <p class="text-sm text-slate-400 mt-1">
                    {{ $paymentStep === 'choose' ? "Select how you'd like to pay for this certificate" : 'Scan QR & upload proof of payment' }}
                </p>
            </div>

            {{-- Step 1: Payment Method Selection --}}
            @if($paymentStep === 'choose')
            <div class="p-8 space-y-4">
                <button type="button" wire:click="selectPaymentMethod('online')"
                        class="w-full p-5 rounded-2xl border-2 border-slate-100 hover:border-blue-400 hover:bg-blue-50/50 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 text-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-slate-800">Pay Online</p>
                        <p class="text-xs text-slate-400">Scan QR code and upload proof of payment</p>
                    </div>
                    <i class="fas fa-chevron-right text-slate-300 ml-auto"></i>
                </button>

                <button type="button" wire:click="selectPaymentMethod('cash')"
                        class="w-full p-5 rounded-2xl border-2 border-slate-100 hover:border-emerald-400 hover:bg-emerald-50/50 transition-all flex items-center gap-4 group">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600 text-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-coins"></i>
                    </div>
                    <div class="text-left">
                        <p class="font-bold text-slate-800">Pay Cash</p>
                        <p class="text-xs text-slate-400">Pay at the barangay hall upon claiming</p>
                    </div>
                    <i class="fas fa-chevron-right text-slate-300 ml-auto"></i>
                </button>

                <button type="button" wire:click="closePaymentModal" class="w-full mt-2 py-2.5 text-xs font-bold text-slate-400 hover:text-slate-600 uppercase tracking-widest transition-all">
                    Cancel
                </button>
            </div>
            @endif

            {{-- Step 2: QR Code + Upload (Online Only) --}}
            @if($paymentStep === 'upload')
            <div class="p-8">
                <div class="text-center mb-6">
                    <div class="inline-block p-3 rounded-2xl border-2 border-blue-200 bg-blue-50/30 mb-3">
                        <img src="/treasury-system/assets/images/qr.jpg" alt="Payment QR Code" class="w-48 h-48 rounded-xl object-contain">
                    </div>
                    <p class="text-xs text-slate-400 italic">Scan the QR code to pay, then upload your receipt or enter reference number below</p>
                </div>

                <div class="mb-4 p-4 rounded-xl bg-slate-50 border border-slate-100">
                    <p class="text-xs text-slate-500"><strong>Certificate:</strong> <span id="certSummary">{{ $certificate_type_id ? optional(\App\Models\CertificateType::find($certificate_type_id))->certificate_name ?? 'â€”' : 'â€”' }}</span></p>
                    <p class="text-xs text-slate-500 mt-1"><strong>Total Amount:</strong> PHP <span id="totalSummary">{{ $total_amount }}</span></p>
                </div>

                {{-- Upload Area - wire:ignore prevents Livewire from resetting preview/validation during re-renders --}}
                <div wire:ignore>
                    <div class="mb-4">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2 block">
                            <i class="fas fa-file-upload mr-1"></i> Upload Proof of Payment
                        </label>
                        <input type="file" id="proofFileInput" accept="image/jpeg,image/png"
                               class="block w-full text-sm text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100 transition-all">
                        <p class="text-[10px] text-slate-400 mt-1">JPG or PNG only, max 5MB</p>
                    </div>

                    <div id="proofPreview" style="display:none" class="mb-4">
                        <img id="proofPreviewImg" class="w-full max-h-48 object-contain rounded-xl border border-slate-200" alt="Proof preview">
                    </div>

                    <div id="validationStatus" style="display:none" class="mb-4 p-3 rounded-xl flex items-center gap-3"></div>
                </div>

                {{-- Divider --}}
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center"><div class="w-full border-t border-slate-200"></div></div>
                    <div class="relative flex justify-center"><span class="bg-white px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">or enter reference number</span></div>
                </div>

                {{-- Reference Number Input --}}
                <div class="mb-4">
                    <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-2 block">
                        <i class="fas fa-hashtag mr-1"></i> Transaction Reference Number
                    </label>
                    <div class="flex gap-2">
                        <input wire:model="referenceNumber" type="text" placeholder="e.g. GCash Ref #12345678"
                               class="flex-1 px-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <button type="button" wire:click="submitReferenceNumber"
                                class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold uppercase tracking-widest transition-all whitespace-nowrap">
                            Submit
                        </button>
                    </div>
                    @error('referenceNumber') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1.5 ml-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex gap-3 mt-6">
                    <button type="button" wire:click="backToChooseStep" class="flex-1 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 uppercase tracking-widest transition-all border border-slate-200 rounded-xl">
                        <i class="fas fa-arrow-left mr-1"></i> Back
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    @script
    <script>
        // Load Tesseract.js dynamically
        (function() {
            if (!window.Tesseract) {
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js';
                document.head.appendChild(s);
            }
        })();

        window.showStatus = function(type, msg) {
            var div = document.getElementById('validationStatus');
            if (!div) return;
            div.style.display = 'flex';
            if (type === 'loading') {
                div.className = 'mb-4 p-3 rounded-xl flex items-center gap-3 bg-blue-50 border border-blue-100';
                div.innerHTML = '<div class="animate-spin w-5 h-5 border-2 border-blue-400 border-t-transparent rounded-full"></div><p class="text-xs font-bold text-blue-700">' + msg + '</p>';
            } else if (type === 'success') {
                div.className = 'mb-4 p-3 rounded-xl flex items-center gap-3 bg-emerald-50 border border-emerald-100';
                div.innerHTML = '<i class="fas fa-check-circle text-emerald-500"></i><p class="text-xs font-bold text-emerald-700">' + msg + '</p>';
            } else {
                div.className = 'mb-4 p-3 rounded-xl flex items-center gap-3 bg-rose-50 border border-rose-100';
                div.innerHTML = '<i class="fas fa-exclamation-circle text-rose-500"></i><p class="text-xs font-bold text-rose-700">' + msg + '</p>';
            }
        };

        // Use MutationObserver to attach file input listener when upload step appears
        function attachFileListener() {
            var fileInput = document.getElementById('proofFileInput');
            if (!fileInput || fileInput.dataset.listenerAttached) return;
            fileInput.dataset.listenerAttached = 'true';

            fileInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (!file) return;
                var previewDiv = document.getElementById('proofPreview');
                var previewImg = document.getElementById('proofPreviewImg');
                var statusDiv = document.getElementById('validationStatus');
                if (statusDiv) statusDiv.style.display = 'none';

                // Validate file type
                if (!['image/jpeg', 'image/png'].includes(file.type)) {
                    window.showStatus('error', 'Please upload a JPG or PNG image.');
                    fileInput.value = '';
                    return;
                }
                // Validate file size
                if (file.size > 5 * 1024 * 1024) {
                    window.showStatus('error', 'Image must be 5MB or smaller.');
                    fileInput.value = '';
                    return;
                }

                // Show preview
                var reader = new FileReader();
                reader.onload = function(ev) {
                    if (previewImg) previewImg.src = ev.target.result;
                    if (previewDiv) previewDiv.style.display = 'block';
                };
                reader.readAsDataURL(file);

                window.showStatus('loading', 'Validating receipt... This may take a moment.');

                function runOCR() {
                    if (!window.Tesseract) {
                        setTimeout(runOCR, 500);
                        return;
                    }
                    Tesseract.recognize(file, 'eng', { logger: function() {} })
                        .then(function(result) {
                            var text = result.data.text.toLowerCase();
                            var keywords = [
                                'receipt', 'transaction', 'reference', 'ref no', 'ref.',
                                'gcash', 'maya', 'paymaya', 'instapay', 'g-xchange',
                                'amount', 'total', 'paid', 'payment',
                                'successful', 'completed', 'confirmed',
                                'transfer', 'sent', 'received',
                                'bpi', 'bdo', 'unionbank', 'metrobank', 'landbank',
                                'peso', 'php'
                            ];
                            var matches = keywords.filter(function(kw) { return text.indexOf(kw) !== -1; });
                            if (matches.length >= 2) {
                                window.showStatus('success', 'Receipt verified! (' + matches.length + ' indicators found). Closing in 2 seconds...');
                                // Upload the file to Livewire
                                $wire.upload('proofOfPayment', file, function() {
                                    $wire.confirmReceipt();
                                    // Auto-close after 2 second delay
                                    setTimeout(function() {
                                        $wire.closePaymentModal();
                                    }, 2000);
                                }, function() {
                                    window.showStatus('error', 'File upload failed. Please try again.');
                                });
                            } else {
                                window.showStatus('error', 'This image does not appear to be a valid payment receipt. Please upload a clearer receipt image.');
                                fileInput.value = '';
                                if (previewDiv) previewDiv.style.display = 'none';
                            }
                        })
                        .catch(function() {
                            window.showStatus('error', 'Failed to validate the image. Please try again.');
                        });
                }
                runOCR();
            });
        }

        // Attach listener on initial render and after Livewire morphs
        attachFileListener();
        Livewire.hook('morph.updated', () => {
            setTimeout(attachFileListener, 100);
        });
    </script>
    @endscript
    @endif
</div>
