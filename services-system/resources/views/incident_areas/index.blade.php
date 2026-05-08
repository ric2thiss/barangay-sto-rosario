<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Directories</h1>
                <p class="text-slate-500 mt-1">Manage locations, incident areas, and community puroks</p>
            </div>

            <div class="flex items-center gap-2">
                <div class="px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 text-xs">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Areas</span>
                        <span class="text-lg font-black text-slate-900 leading-tight">{{ $areas->total() }}</span>
                    </div>
                </div>
                <div class="px-4 py-2 bg-white rounded-xl border border-slate-200 shadow-sm flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-xs">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none">Puroks</span>
                        <span class="text-lg font-black text-slate-900 leading-tight">{{ $puroks->total() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-semibold animate-in fade-in slide-in-from-top-4 duration-300">
                <i class="fas fa-check-circle text-emerald-500"></i>
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 flex items-center gap-3 px-4 py-3 rounded-2xl bg-rose-50 border border-rose-100 text-rose-700 text-sm font-semibold animate-in fade-in slide-in-from-top-4 duration-300">
                <i class="fas fa-exclamation-circle text-rose-500"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Main Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

            {{-- ══════════════════════════════════════════
                 COLUMN 1: INCIDENT AREAS
            ══════════════════════════════════════════ --}}
            <div class="space-y-6">
                
                {{-- Add Area Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Add Incident Area</h3>
                        <p class="text-sm text-slate-500">Register a new street or location for blotter mapping</p>
                    </div>
                    <form action="{{ route('incident-areas.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <div class="flex-1">
                            <input type="text" name="name" value="{{ old('name') }}" placeholder="e.g. Purok 3 Road, Sitio Mabolo…"
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-2.5 px-4 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all @error('name') border-rose-400 @enderror">
                            @error('name')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-sm transition-all">
                            <i class="fas fa-plus text-[10px]"></i>
                            <span>Register</span>
                        </button>
                    </form>
                </div>

                {{-- Areas List Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Active Locations</h3>
                        <form method="GET" action="{{ route('incident-areas.index') }}" class="relative w-48">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Quick filter..."
                                   class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border-none rounded-lg text-xs placeholder-slate-400 focus:ring-1 focus:ring-blue-500 transition-all">
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-slate-50">
                                @forelse($areas as $area)
                                    <tr class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="area-display">
                                                <p class="font-bold text-slate-700">{{ $area->name }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    {{ $area->blotter_puroks_count }} {{ Str::plural('record', $area->blotter_puroks_count) }}
                                                </p>
                                            </div>
                                            <form class="area-edit-form hidden" action="{{ route('incident-areas.update', $area) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="flex gap-2 items-center">
                                                    <input type="text" name="name" value="{{ $area->name }}"
                                                           class="area-edit-input flex-1 rounded-lg border-slate-200 bg-slate-50 text-sm py-1.5 px-3 focus:ring-2 focus:ring-blue-500">
                                                    <button type="submit" class="px-3 py-1.5 text-[10px] font-bold bg-blue-600 text-white rounded-lg uppercase tracking-widest">Save</button>
                                                    <button type="button" class="area-cancel-edit px-3 py-1.5 text-[10px] font-bold bg-slate-100 text-slate-500 rounded-lg uppercase tracking-widest">Cancel</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="area-actions flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button type="button" class="area-edit-btn w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-all">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>
                                                <form action="{{ route('incident-areas.destroy', $area) }}" method="POST"
                                                      onsubmit="return confirmDelete(event, '{{ addslashes($area->name) }}', {{ $area->blotter_puroks_count }})">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-12 text-center text-slate-400">
                                            <i class="fas fa-map-pin text-2xl mb-2 opacity-20"></i>
                                            <p class="text-sm">No areas found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($areas->hasPages())
                        <div class="px-6 py-4 bg-slate-50/30 border-t border-slate-100">
                            {{ $areas->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- ══════════════════════════════════════════
                 COLUMN 2: PUROKS
            ══════════════════════════════════════════ --}}
            <div class="space-y-6">
                
                {{-- Add Purok Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="mb-4">
                        <h3 class="text-lg font-bold text-slate-900">Register Purok</h3>
                        <p class="text-sm text-slate-500">Create a new neighborhood zone for resident profiling</p>
                    </div>
                    <form action="{{ route('puroks.store') }}" method="POST" class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        <div class="flex-1">
                            <input type="text" name="purok_name" value="{{ old('purok_name') }}" placeholder="e.g. Purok 1, Purok Sampaguita…"
                                   class="block w-full rounded-xl border-slate-200 bg-slate-50 text-sm py-2.5 px-4 text-slate-700 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all @error('purok_name') border-rose-400 @enderror">
                            @error('purok_name')
                                <p class="mt-1.5 text-[11px] font-bold text-rose-500 uppercase tracking-wider ml-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 rounded-xl shadow-sm transition-all">
                            <i class="fas fa-plus text-[10px]"></i>
                            <span>Register</span>
                        </button>
                    </form>
                </div>

                {{-- Puroks List Card --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Neighborhood Zones</h3>
                        <form method="GET" action="{{ route('incident-areas.index') }}" class="relative w-48">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                            <input type="text" name="purok_search" value="{{ $purokSearch }}" placeholder="Quick filter..."
                                   class="w-full pl-8 pr-3 py-1.5 bg-slate-50 border-none rounded-lg text-xs placeholder-slate-400 focus:ring-1 focus:ring-emerald-500 transition-all">
                        </form>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-slate-50">
                                @forelse($puroks as $purok)
                                    <tr class="group hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="purok-display">
                                                <p class="font-bold text-slate-700">{{ $purok->purok_name }}</p>
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                    {{ $purok->residents_count }} {{ Str::plural('resident', $purok->residents_count) }}
                                                </p>
                                            </div>
                                            <form class="purok-edit-form hidden" action="{{ route('puroks.update', $purok->purok_id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <div class="flex gap-2 items-center">
                                                    <input type="text" name="purok_name" value="{{ $purok->purok_name }}"
                                                           class="purok-edit-input flex-1 rounded-lg border-slate-200 bg-slate-50 text-sm py-1.5 px-3 focus:ring-2 focus:ring-emerald-500">
                                                    <button type="submit" class="px-3 py-1.5 text-[10px] font-bold bg-emerald-600 text-white rounded-lg uppercase tracking-widest">Save</button>
                                                    <button type="button" class="purok-cancel-edit px-3 py-1.5 text-[10px] font-bold bg-slate-100 text-slate-500 rounded-lg uppercase tracking-widest">Cancel</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="purok-actions flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button type="button" class="purok-edit-btn w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-all">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </button>
                                                <form action="{{ route('puroks.destroy', $purok->purok_id) }}" method="POST"
                                                      onsubmit="return confirmPurokDelete(event, '{{ addslashes($purok->purok_name) }}', {{ $purok->residents_count }})">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="px-6 py-12 text-center text-slate-400">
                                            <i class="fas fa-home text-2xl mb-2 opacity-20"></i>
                                            <p class="text-sm">No puroks found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($puroks->hasPages())
                        <div class="px-6 py-4 bg-slate-50/30 border-t border-slate-100">
                            {{ $puroks->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Inline rename logic
        function setupInlineEdit(editBtnClass, displayClass, formClass, actionsClass, inputClass, cancelBtnClass) {
            document.querySelectorAll('.' + editBtnClass).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = this.closest('tr');
                    row.querySelector('.' + displayClass).classList.add('hidden');
                    row.querySelector('.' + formClass).classList.remove('hidden');
                    row.querySelector('.' + actionsClass).classList.add('hidden');
                    row.querySelector('.' + inputClass).focus();
                });
            });
            document.querySelectorAll('.' + cancelBtnClass).forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const row = this.closest('tr');
                    row.querySelector('.' + formClass).classList.add('hidden');
                    row.querySelector('.' + displayClass).classList.remove('hidden');
                    row.querySelector('.' + actionsClass).classList.remove('hidden');
                });
            });
        }

        setupInlineEdit('area-edit-btn', 'area-display', 'area-edit-form', 'area-actions', 'area-edit-input', 'area-cancel-edit');
        setupInlineEdit('purok-edit-btn', 'purok-display', 'purok-edit-form', 'purok-actions', 'purok-edit-input', 'purok-cancel-edit');

        function confirmDelete(e, name, usageCount) {
            if (usageCount > 0) return confirm('Warning: "' + name + '" is linked to ' + usageCount + ' records. Delete anyway?');
            return confirm('Delete area "' + name + '"?');
        }

        function confirmPurokDelete(e, name, resCount) {
            if (resCount > 0) return confirm('Warning: "' + name + '" has ' + resCount + ' residents. Delete anyway?');
            return confirm('Delete purok "' + name + '"?');
        }
    </script>
</x-layouts.app>