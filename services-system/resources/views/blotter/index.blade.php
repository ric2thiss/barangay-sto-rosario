<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        {{-- Page Header & Actions --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Blotter Management</h1>
                <p class="text-slate-500 mt-1">Official incident reports and community dispute records</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('blotter.create') }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 rounded-xl shadow-sm transition-all">
                    <i class="fas fa-plus text-xs"></i>
                    <span>New Record</span>
                </a>
            </div>
        </div>

        {{-- Filter & Control Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-8">
            <form method="GET" action="{{ route('blotter.index') }}" id="filterForm" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    
                    {{-- Search Input --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Search Records</label>
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
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Case Status</label>
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
                            <option value="priority" {{ $filters['sort'] === 'priority' ? 'selected' : '' }}>Priority View</option>
                            <option value="date_desc" {{ $filters['sort'] === 'date_desc' ? 'selected' : '' }}>Newest First</option>
                            <option value="date_asc" {{ $filters['sort'] === 'date_asc' ? 'selected' : '' }}>Oldest First</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-4 border-t border-slate-100">
                    {{-- Approval Filters --}}
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest ml-1">Quick Filters</label>
                        <div class="flex items-center p-1 bg-slate-100 rounded-xl">
                            <a href="{{ route('blotter.index', array_merge($filters, ['approval' => 'pending'])) }}"
                               class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all {{ ($filters['approval'] ?? '') === 'pending' ? 'bg-white text-amber-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                                Pending
                            </a>
                            <a href="{{ route('blotter.index', array_merge($filters, ['approval' => 'approved'])) }}"
                               class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all {{ ($filters['approval'] ?? '') === 'approved' ? 'bg-white text-emerald-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                                Approved
                            </a>
                            <a href="{{ route('blotter.index') }}"
                               class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all {{ !in_array(($filters['approval'] ?? ''), ['pending', 'approved']) ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}">
                                Show All
                            </a>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        @if(array_filter(array_values($filters)))
                            <a href="{{ route('blotter.index') }}" class="px-5 py-2 text-xs font-bold text-slate-500 hover:text-slate-700 transition-all">Clear All</a>
                        @endif
                        <button type="submit" class="px-6 py-2 bg-slate-900 text-white rounded-xl text-xs font-bold hover:bg-slate-800 transition-all uppercase tracking-widest">Apply Filters</button>
                    </div>
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
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Incident Info</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Parties</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Summary</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Officer</th>
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
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700">{{ $record->recorder_name }}</span>
                                        @if($record->recorder_is_resident !== null)
                                            <span class="text-[10px] font-bold {{ $record->recorder_is_resident ? 'text-emerald-500' : 'text-blue-500' }} uppercase tracking-tighter">
                                                {{ $record->recorder_is_resident ? 'Resident' : 'Official' }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="{{ route('blotter.show', $record) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all" title="View Details">
                                            <i class="fas fa-eye text-sm"></i>
                                        </a>
                                        <a href="{{ route('blotter.edit', $record) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all" title="Edit Record">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <a href="{{ route('blotter.download-pdf', $record) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-rose-500 hover:bg-rose-50 transition-all" title="Download PDF">
                                            <i class="fas fa-file-pdf text-sm"></i>
                                        </a>

                                        @if($record->status === 'Pending')
                                            <form method="POST" action="{{ route('blotter.updateStatus', $record) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Approved">
                                                <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-emerald-500 hover:bg-emerald-50 transition-all" title="Approve Record">
                                                    <i class="fas fa-check-circle text-sm"></i>
                                                </button>
                                            </form>
                                        @elseif($record->status === 'Approved')
                                            <form method="POST" action="{{ route('blotter.updateStatus', $record) }}" class="inline">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="Released">
                                                <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-blue-500 hover:bg-blue-50 transition-all" title="Release Record">
                                                    <i class="fas fa-file-export text-sm"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <button type="button" onclick="confirmDelete({{ $record->blotter_id }}, '{{ $record->form_number }}')" 
                                                class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 hover:text-rose-600 hover:bg-rose-50 transition-all" title="Delete">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <div class="w-16 h-16 rounded-2xl bg-slate-50 flex items-center justify-center mb-4 text-2xl">
                                            <i class="fas fa-balance-scale opacity-20"></i>
                                        </div>
                                        <p class="text-sm font-semibold">No blotter records found</p>
                                        <p class="text-xs mt-1">Try adjusting your filters or search terms</p>
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

    {{-- Delete Modal --}}
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-6">
                <div class="w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center text-rose-600 mb-4 mx-auto text-xl">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3 class="text-lg font-bold text-center text-slate-900">Delete Record?</h3>
                <p class="text-slate-500 text-center mt-2 text-sm">
                    Are you sure you want to delete blotter record <span id="deleteFormNumber" class="font-black text-slate-700"></span>? This action cannot be undone.
                </p>
            </div>
            <div class="bg-slate-50 p-4 flex items-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="flex-1 px-4 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 transition-all">Cancel</button>
                <form id="deleteForm" method="POST" class="flex-1">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2.5 bg-rose-600 text-white rounded-xl text-sm font-bold hover:bg-rose-700 shadow-sm transition-all">Delete Forever</button>
                </form>
            </div>
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

        // Delete Modal logic
        function confirmDelete(blotterId, formNumber) {
            document.getElementById('deleteFormNumber').textContent = formNumber;
            document.getElementById('deleteForm').action = `/blotter/${blotterId}`;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });

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
