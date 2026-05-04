<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
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
                                    <option value="">— Select Certificate Type —</option>
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

                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4 gap-4">
                            <div class="col-span-2">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Payment Status</label>
                                <select wire:model="payment_status" id="payment_status" @disabled($isFreeCertificate) 
                                        class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                    <option value="Paid">Paid</option>
                                    <option value="Pending">Awaiting Payment</option>
                                </select>
                            </div>

                            <div class="col-span-1">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Base Fee</label>
                                <input wire:model.live="amount" type="number" id="amount" @readonly($isFreeCertificate) @disabled($isFreeCertificate)
                                       class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all disabled:opacity-50">
                            </div>

                            <div class="col-span-1">
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

                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <a href="{{ $isPendingOnly ? route('certificates.resident_index') : route('certificates.index') }}"
                       class="flex-1 sm:flex-none px-6 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-all text-center uppercase tracking-widest"
                       wire:navigate>
                        Discard
                    </a>
                    <button type="submit" class="flex-1 sm:flex-none px-8 py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-black shadow-lg shadow-blue-200 transition-all uppercase tracking-widest">
                        Submit Request
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
