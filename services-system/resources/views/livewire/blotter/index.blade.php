<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    
    {{-- Page Header & Actions --}}
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Blotter Records</h1>
            <p class="text-slate-500 mt-1">Official incident reports and community dispute documentation</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('blotter.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-xl shadow-sm transition-all" wire:navigate>
                <i class="fas fa-plus text-xs"></i>
                <span>Register New Incident</span>
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
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by name, incident type, or details..." 
                       class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all">
            </div>

            {{-- Quick Stats / Info --}}
            <div class="flex items-center gap-6 text-[11px] font-bold uppercase tracking-widest text-slate-400">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Active Cases</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>Settled</span>
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
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Incident Details</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Parties Involved</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr class="hover:bg-slate-50/30 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-slate-900">{{ $record->incident_type }}</span>
                                    <span class="text-[11px] text-slate-400 font-medium italic">{{ $record->incident_date->format('M d, Y') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-slate-300 uppercase tracking-tighter">Complainant</span>
                                        <span class="text-xs font-bold text-slate-700">{{ $record->complainant->full_name ?? 'N/A' }}</span>
                                    </div>
                                    <i class="fas fa-arrow-right text-[10px] text-slate-200"></i>
                                    <div class="flex flex-col">
                                        <span class="text-[10px] font-bold text-slate-300 uppercase tracking-tighter">Respondent</span>
                                        <span class="text-xs font-bold text-slate-700">{{ $record->respondent->full_name ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusClasses = [
                                        'Active'    => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'Settled'   => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'Dismissed' => 'bg-slate-50 text-slate-600 border-slate-100',
                                    ];
                                    $class = $statusClasses[$record->status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $class }} uppercase tracking-wider">
                                    {{ $record->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all" title="View Details">
                                        <i class="fas fa-eye text-sm"></i>
                                    </button>
                                    <button class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all" title="Edit Record">
                                        <i class="fas fa-edit text-sm"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-4 text-2xl">
                                        <i class="fas fa-balance-scale opacity-20"></i>
                                    </div>
                                    <p class="text-sm font-semibold">No blotter records found</p>
                                    <p class="text-xs mt-1">Incident reports will appear here</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($records->hasPages())
            <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
