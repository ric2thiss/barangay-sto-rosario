<x-layouts.app>
<div class="max-w-4xl mx-auto py-8 space-y-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Incident Areas</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Manage the list of locations available when filing blotter records.
            </p>
        </div>
        <span class="text-sm text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-3 py-1 rounded-full border border-zinc-200 dark:border-zinc-700">
            {{ $areas->total() }} {{ Str::plural('area', $areas->total()) }}
        </span>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 text-red-700 dark:text-red-300 text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- Add new area --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3">Add New Area</h2>
        <form action="{{ route('incident-areas.store') }}" method="POST" class="flex gap-3 items-start">
            @csrf
            <div class="flex-1">
                <input type="text"
                       name="name"
                       value="{{ old('name') }}"
                       placeholder="e.g. Purok 3 Road, Sitio Mabolo, Near the barangay hall…"
                       autofocus
                       class="block w-full rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm
                              focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                              @error('name') border-red-400 dark:border-red-500 @enderror">
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="flex-shrink-0 inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-white
                           bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Add Area
            </button>
        </form>
    </div>

    {{-- Search + Table --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">

        {{-- Search bar --}}
        <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-800">
            <form method="GET" action="{{ route('incident-areas.index') }}">
                <div class="relative max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z"/>
                    </svg>
                    <input type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search areas…"
                           class="block w-full pl-9 rounded-lg border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm
                                  focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </form>
        </div>

        @if($areas->isEmpty())
            <div class="px-5 py-16 text-center">
                <svg class="mx-auto w-10 h-10 text-zinc-300 dark:text-zinc-600 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-9.5 11.25S.5 17.642.5 10.5a9.5 9.5 0 1119 0z"/>
                </svg>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    @if($search) No areas match "{{ $search }}". @else No areas added yet. @endif
                </p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-100 dark:border-zinc-800">
                        <th class="text-left px-5 py-3 text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider w-8">#</th>
                        <th class="text-left px-5 py-3 text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Area Name</th>
                        <th class="text-center px-5 py-3 text-xs font-semibold text-zinc-400 dark:text-zinc-500 uppercase tracking-wider w-32">Used in</th>
                        <th class="px-5 py-3 w-36"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-50 dark:divide-zinc-800">
                    @foreach($areas as $area)
                    <tr class="group hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors" id="row-{{ $area->id }}">

                        {{-- Row number --}}
                        <td class="px-5 py-3 text-zinc-300 dark:text-zinc-600 tabular-nums text-xs">
                            {{ $loop->iteration + ($areas->currentPage() - 1) * $areas->perPage() }}
                        </td>

                        {{-- Inline edit name --}}
                        <td class="px-5 py-3">
                            <span class="area-display font-medium text-zinc-800 dark:text-zinc-100">{{ $area->name }}</span>
                            <form class="area-edit-form hidden" action="{{ route('incident-areas.update', $area) }}" method="POST">
                                @csrf @method('PUT')
                                <div class="flex gap-2 items-center">
                                    <input type="text"
                                           name="name"
                                           value="{{ $area->name }}"
                                           class="area-edit-input flex-1 rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800
                                                  focus:border-indigo-500 focus:ring-indigo-500 text-sm py-1">
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition-colors">
                                        Save
                                    </button>
                                    <button type="button"
                                            class="area-cancel-edit px-2.5 py-1 text-xs font-medium bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-md transition-colors">
                                        Cancel
                                    </button>
                                </div>
                            </form>
                        </td>

                        {{-- Usage count --}}
                        <td class="px-5 py-3 text-center">
                            @if($area->blotter_puroks_count > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-800">
                                    {{ $area->blotter_puroks_count }} {{ Str::plural('record', $area->blotter_puroks_count) }}
                                </span>
                            @else
                                <span class="text-xs text-zinc-300 dark:text-zinc-600">—</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="px-5 py-3">
                            <div class="area-actions flex gap-2 justify-end">
                                <button type="button"
                                        class="area-edit-btn inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium
                                               text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400
                                               bg-transparent hover:bg-indigo-50 dark:hover:bg-indigo-900/20
                                               rounded-md border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800
                                               transition-all">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.415.586H8v-2.414A2 2 0 018.586 12.5L9 13z"/>
                                    </svg>
                                    Rename
                                </button>

                                <form action="{{ route('incident-areas.destroy', $area) }}" method="POST"
                                      onsubmit="return confirmDelete(event, '{{ addslashes($area->name) }}', {{ $area->blotter_puroks_count }})">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium
                                                   text-zinc-400 hover:text-red-600 dark:text-zinc-500 dark:hover:text-red-400
                                                   bg-transparent hover:bg-red-50 dark:hover:bg-red-900/20
                                                   rounded-md border border-transparent hover:border-red-200 dark:hover:border-red-800
                                                   transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4h6v3M4 7h16"/>
                                        </svg>
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            @if($areas->hasPages())
                <div class="px-5 py-3 border-t border-zinc-100 dark:border-zinc-800">
                    {{ $areas->links() }}
                </div>
            @endif
        @endif
    </div>

</div>

<script>
// Inline rename toggle
document.querySelectorAll('.area-edit-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var row     = this.closest('tr');
        var display = row.querySelector('.area-display');
        var form    = row.querySelector('.area-edit-form');
        var actions = row.querySelector('.area-actions');

        display.classList.add('hidden');
        form.classList.remove('hidden');
        actions.classList.add('hidden');
        form.querySelector('.area-edit-input').focus();
    });
});

document.querySelectorAll('.area-cancel-edit').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var row     = this.closest('tr');
        var display = row.querySelector('.area-display');
        var form    = row.querySelector('.area-edit-form');
        var actions = row.querySelector('.area-actions');

        form.classList.add('hidden');
        display.classList.remove('hidden');
        actions.classList.remove('hidden');
    });
});

// Escape key cancels edit
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.area-edit-form:not(.hidden)').forEach(function (form) {
        form.querySelector('.area-cancel-edit').click();
    });
});

// Delete confirmation
function confirmDelete(e, name, usageCount) {
    if (usageCount > 0) {
        // Let the server return the error — don't block here
        return confirm(
            'Warning: "' + name + '" is linked to ' + usageCount + ' blotter record(s).\n\n' +
            'The server will block this deletion. Continue anyway?'
        );
    }
    return confirm('Delete area "' + name + '"? This cannot be undone.');
}
</script>
</x-layouts.app>