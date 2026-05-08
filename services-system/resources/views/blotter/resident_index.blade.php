<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        {{-- Page Header & Actions --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Blotter Reports</h1>
                <p class="text-slate-500 mt-1">View and track the status of your submitted incident reports</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('blotter_reports.resident_create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-xl shadow-sm transition-all">
                    <i class="fas fa-plus text-xs"></i>
                    <span>Submit New Report</span>
                </a>
            </div>
        </div>

        {{-- Filter & Control Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-8">
            <form method="GET" action="{{ route('blotter.resident_index') }}" id="filterForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    {{-- Search Input --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Search Reports</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                            <input type="text" name="search" value="{{ $filters['search'] }}" id="searchInput"
                                   placeholder="Form no, purpose, or type..."
                                   class="block w-full pl-10 pr-4 py-2 bg-slate-50 border-slate-200 rounded-xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                        </div>
                    </div>

                    {{-- KP Form Select --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">KP Form</label>
                        <select name="form" onchange="this.form.submit()" 
                                class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">All KP Forms</option>
                            @foreach($kpForms as $f)
                                <option value="{{ $f['label'] }}" {{ $filters['form'] === $f['label'] ? 'selected' : '' }}>{{ $f['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status Select --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Report Status</label>
                        <select name="status" onchange="this.form.submit()" 
                                class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="">All Statuses</option>
                            @foreach(['Approved', 'Released','Pending','Active','Completed','Dismissed'] as $s)
                                <option value="{{ $s }}" {{ $filters['status'] === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Sorting Select --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Ordering</label>
                        <select name="sort" onchange="this.form.submit()" 
                                class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 transition-all">
                            <option value="priority" {{ $filters['sort'] === 'priority' ? 'selected' : '' }}>Recent Submissions</option>
                            <option value="date_desc" {{ $filters['sort'] === 'date_desc' ? 'selected' : '' }}>Newest First</option>
                            <option value="date_asc" {{ $filters['sort'] === 'date_asc' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                    @if(array_filter(array_values($filters)))
                        <a href="{{ route('blotter.resident_index') }}" class="px-5 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 transition-all">Clear All</a>
                    @endif
                    <button type="submit" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all uppercase tracking-widest">Apply Filters</button>
                </div>
            </form>
        </div>

        {{-- Success Alert --}}
        @if (session('success'))
            <div id="success-message" class="mb-6 rounded-2xl bg-emerald-50 border border-emerald-100 p-4 animate-in fade-in slide-in-from-top-4 duration-300">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center text-white text-xs">
                        <i class="fas fa-check"></i>
                    </div>
                    <p class="text-sm font-semibold text-emerald-800">{{ session('success') }}</p>
                    <button type="button" class="ml-auto text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- Main Table Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Report Info</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Parties</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Purpose</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($records as $record)
                            <tr class="hover:bg-slate-50/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="text-sm font-black text-slate-900">{{ $record->form_number }}</span>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $record->incident_date?->format('M d, Y') ?? $record->created_at?->format('M d, Y') ?? '—' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-col gap-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                            <span class="text-xs font-bold text-slate-700 truncate max-w-[150px]" title="Complainant">{{ $record->complainant_names ?? 'N/A' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                            <span class="text-xs font-bold text-slate-700 truncate max-w-[150px]" title="Respondent">{{ $record->respondent_names ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-500 line-clamp-2 max-w-xs" title="{{ $record->purpose }}">
                                        {{ $record->purpose }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusClasses = [
                                            'Pending'    => 'bg-amber-50 text-amber-700 border-amber-100',
                                            'Approved'   => 'bg-blue-50 text-blue-700 border-blue-100',
                                            'Active'     => 'bg-rose-50 text-rose-700 border-rose-100',
                                            'Released'   => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                            'Completed'  => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                            'Dismissed'  => 'bg-slate-50 text-slate-600 border-slate-100',
                                        ];
                                        $label = $record->status === 'Approved' ? 'Ready to Release' : $record->status;
                                        $class = $statusClasses[$record->status] ?? 'bg-slate-50 text-slate-600 border-slate-100';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold border {{ $class }} uppercase tracking-wider">
                                        {{ $label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('blotter.resident_show', $record) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all" title="View Report Details">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        @if($record->status === 'Pending')
                                            <a href="{{ route('blotter.resident_edit', $record) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all" title="Edit Report">
                                                <i class="fas fa-edit text-sm"></i>
                                            </a>
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
                                        <p class="text-sm font-semibold">No reports found</p>
                                        <p class="text-xs mt-1">Submit a new report to see it here</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    </div>

    <script>
        // Search debounce
        const searchInput = document.getElementById('searchInput');
        let searchTimer;
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    document.getElementById('filterForm').submit();
                }, 600);
            });
        }

        // Flash message timeout
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
</x-layouts.app>
