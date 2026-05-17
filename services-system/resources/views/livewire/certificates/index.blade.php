<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    {{-- Page Header & Actions --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Certificate Requests</h1>
            <p class="text-slate-500 mt-1">Manage and process resident certificate applications</p>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="location.reload()" class="inline-flex items-center justify-center p-2.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all" title="Refresh List">
                <i class="fas fa-sync-alt"></i>
            </button>
            <a href="{{ route('certificates.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-sm transition-all">
                <i class="fas fa-plus text-xs"></i>
                <span>Issue Certificate</span>
            </a>
        </div>
    </div>

    {{-- Filter & Search Bar --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center gap-6">
            
            {{-- Search Input --}}
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <i class="fas fa-search text-sm"></i>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by resident name or purpose..." 
                       class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
            </div>

            {{-- Filters Group --}}
            <div class="flex flex-wrap items-center gap-4">
                {{-- Status Filter --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Status</label>
                    <select wire:model.live="statusFilter" class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="All">All Statuses</option>
                        <option value="Approved">Approved</option>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Released">Released</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                {{-- Type Filter --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Certificate Type</label>
                    <select wire:model.live="typeFilter" class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        <option value="All">All Types</option>
                        @foreach($certificateTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Approval Toggle --}}
                <div class="flex flex-col gap-1.5">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Quick View</label>
                    <div class="flex items-center p-1 bg-slate-100 rounded-xl">
                        <button wire:click="$set('approvalFilter', 'pending')"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $this->approvalFilter === 'pending' ? 'bg-white text-amber-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Pending
                        </button>
                        <button wire:click="$set('approvalFilter', 'approved')"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ $this->approvalFilter === 'approved' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            Approved
                        </button>
                        <button wire:click="$set('approvalFilter', 'all')"
                                class="px-3 py-1.5 text-xs font-bold rounded-lg transition-all {{ !in_array($this->approvalFilter, ['pending', 'approved']) ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                            All
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Success Alert --}}
    @if (session()->has('message'))
        <div id="success-message" class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 animate-in fade-in slide-in-from-top-4 duration-300">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs">
                    <i class="fas fa-check"></i>
                </div>
                <p class="text-sm font-semibold text-emerald-800">{{ session('message') }}</p>
                <button type="button" class="ml-auto text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <script>
            setTimeout(() => {
                const msg = document.getElementById('success-message');
                if (msg) {
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-10px)';
                    msg.style.transition = 'all 0.5s ease';
                    setTimeout(() => msg.remove(), 500);
                }
            }, 5000);
        </script>
    @endif

    {{-- Main Table Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Date / Resident</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Certificate Info</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-right">Payment</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($requests as $request)
                        @php
                            $paymentStatus = $request->payment_status ?? 'Pending';
                            $amountDue = $request->amount_due ?? (($request->certificateType->price ?? 0) + 30);
                            
                            $templateMap = [
                                'Barangay Clearance'       => 'barangay_clearance',
                                'Certificate of Residency' => 'residency',
                                'Indigency Certificate'    => 'indigent',
                                'Good Moral Certificate'   => 'goodmoral',
                                'Barangay Permit'          => 'brgy_permit',
                            ];
                            $certName = $request->certificateType->certificate_name ?? '';
                            $template = $templateMap[$certName] ?? 'residency';
                        @endphp
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900">{{ $request->resident?->first_name }} {{ $request->resident?->surname }}</span>
                                    <span class="text-[11px] text-slate-400 font-medium">{{ $request->date_requested->format('M d, Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-semibold text-slate-700">{{ $certName }}</span>
                                    <span class="text-xs text-slate-400 italic truncate max-w-[200px]" title="{{ $request->purpose }}">{{ $request->purpose }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = [
                                        'Pending'    => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'Approved'   => 'bg-blue-50 text-blue-700 border-blue-100',
                                        'Processing' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                        'Released'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'Rejected'   => 'bg-rose-50 text-rose-700 border-rose-100',
                                    ];
                                    $label = $request->status === 'Approved' ? 'Ready to Release' : $request->status;
                                    $class = $statusClasses[$request->status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $class }} uppercase tracking-wider">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-col items-end">
                                    <span class="text-sm font-black text-slate-900">₱{{ number_format((float) $amountDue, 2) }}</span>
                                    <span class="text-[10px] font-bold {{ $paymentStatus === 'Paid' ? 'text-emerald-500' : 'text-amber-500' }} uppercase">{{ $paymentStatus }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    @if($request->status === 'Rejected')
                                        <button wire:click="updateStatus({{ $request->request_id }}, 'Pending')" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all" 
                                                title="Reset to Pending">
                                            <i class="fas fa-undo-alt text-sm"></i>
                                        </button>
                                    @else
                                        {{-- Actions for active requests --}}
                                        <a href="{{ route('certificates.export', [$request->request_id, 'pdf', $template]) }}" 
                                           target="_blank" 
                                           class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-all" 
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf text-sm"></i>
                                        </a>

                                        @if(in_array($request->status, ['Pending', 'Processing']))
                                            <button wire:click="updateStatus({{ $request->request_id }}, 'Approved')" 
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-emerald-500 hover:bg-emerald-50 transition-all" 
                                                    title="Approve Request">
                                                <i class="fas fa-check-circle text-sm"></i>
                                            </button>
                                        @endif

                                        @if(in_array($request->status, ['Pending', 'Processing', 'Approved']))
                                            <button wire:click="updateStatus({{ $request->request_id }}, 'Released')" 
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-500 hover:bg-blue-50 transition-all" 
                                                    title="Release Certificate">
                                                <i class="fas fa-file-export text-sm"></i>
                                            </button>
                                            
                                            <button wire:click="updateStatus({{ $request->request_id }}, 'Rejected')" 
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-all" 
                                                    title="Reject Request">
                                                <i class="fas fa-times-circle text-sm"></i>
                                            </button>
                                        @endif

                                        @if(($request->payment_status ?? 'Pending') === 'Pending')
                                            <button wire:click="updatePaymentStatus({{ $request->request_id }}, 'Paid')" 
                                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-amber-500 hover:bg-amber-50 transition-all" 
                                                    title="Mark as Paid">
                                                <i class="fas fa-money-bill-wave text-sm"></i>
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-4 text-2xl">
                                        <i class="fas fa-folder-open opacity-20"></i>
                                    </div>
                                    <p class="text-sm font-semibold">No certificate requests found</p>
                                    <p class="text-xs mt-1">Try adjusting your search or filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($requests->hasPages())
            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>