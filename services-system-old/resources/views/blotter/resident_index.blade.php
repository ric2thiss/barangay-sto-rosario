<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8">
<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Blotter Records</h1>
        <a href="{{ route('blotter_reports.resident_create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Submit Record
        </a>
    </div>

    {{-- Filter bar --}}
   {{-- Filter bar --}}
<form method="GET" action="{{ route('blotter.resident_index') }}" class="mb-5 flex flex-wrap gap-2 items-end" id="filterForm">
    <div>
        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Search</label>
        <input type="text" name="search" value="{{ $filters['search'] }}"
               placeholder="Form no., purpose…"
               id="searchInput"
               class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm h-9 px-3">
    </div>
    <div>
        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">KP Form</label>
        <select name="form" onchange="this.form.submit()" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm h-9 px-2">
            <option value="">All Forms</option>
            @foreach($kpForms as $f)
                <option value="{{ $f['label'] }}" {{ $filters['form'] === $f['label'] ? 'selected' : '' }}>
                    {{ $f['label'] }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Status</label>
        <select name="status" onchange="this.form.submit()" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm h-9 px-2">
            <option value="">All Status</option>
            @foreach(['Approved', 'Released','Pending','Active','Completed','Dismissed'] as $s)
                <option value="{{ $s }}" {{ $filters['status'] === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Sort by Date</label>
        <select name="sort" onchange="this.form.submit()" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm h-9 px-2">
            <option value="priority" {{ $filters['sort'] === 'priority' ? 'selected' : '' }}>Priority (Pending first)</option>
            <option value="date_desc" {{ $filters['sort'] === 'date_desc' ? 'selected' : '' }}>Date — Newest first</option>
            <option value="date_asc" {{ $filters['sort'] === 'date_asc' ? 'selected' : '' }}>Date — Oldest first</option>
        </select>
    </div>
    <div class="flex gap-2">
        {{-- Keep Filter button as fallback for the search input --}}
        <button type="submit" class="h-9 px-4 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Filter</button>
        @if(array_filter(array_values($filters)))
            <a href="{{ route('blotter.resident_index') }}" class="h-9 px-4 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-700 flex items-center">Clear</a>
        @endif
    </div>
</form>

<script>
    // Debounce the search input so it auto-submits 500ms after the user stops typing
    const searchInput = document.getElementById('searchInput');
    let searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    });
</script>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('success') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent table-modern table-theme-blue table-force-dark">
        <table class="min-w-full text-sm dark:bg-[#0f1e2e]" id="blotterTable">
            <thead class="bg-neutral-50 dark:bg-[#15233b] dark:text-[#e5e7eb]">
                <tr>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Form Number</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Date</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Complainant</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Respondent</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Purpose</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Status</th>
                    <th class="px-4 py-2 text-right font-medium dark:text-[#e5e7eb]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e] blotter-row">
                        <td class="px-4 py-2 dark:text-[#e5e7eb] font-medium">{{ $record->form_number }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->incident_date?->format('M d, Y') ?? $record->created_at?->format('M d, Y') ?? '—' }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                               {{ $record->complainant_names ?? 'N/A' }}
</td>
<td class="px-4 py-2 dark:text-[#e5e7eb]">
    {{ $record->respondent_names ?? 'N/A' }}
</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb] max-w-xs truncate">{{ Str::limit($record->purpose, 50) }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($record->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($record->status === 'Completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                 @elseif($record->status === 'Approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($record->status === 'Released') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                                {{ $record->status === 'Approved' ? 'Ready to Release' : $record->status }}

                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('blotter.resident_show', $record) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">View</a>
                            {{-- <a href="{{ route('blotter.edit', $record) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">Edit</a>
                            <a href="{{ route('blotter.download-docx', $record) }}" class="text-green-600 hover:text-green-900 dark:text-green-400">Download DOCX</a>
                            <button type="button" onclick="confirmDelete({{ $record->blotter_id }}, '{{ $record->form_number }}')" class="text-red-600 hover:text-red-900 dark:text-red-400">Delete</button> --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
                            No blotter records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-neutral-800 rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-medium text-neutral-900 dark:text-white mb-2">Confirm Deletion</h3>
        <p class="text-neutral-600 dark:text-neutral-300 mb-4">
            Are you sure you want to delete blotter record <span id="deleteFormNumber" class="font-medium"></span>? This action cannot be undone.
        </p>
        <div class="flex justify-end space-x-3">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-neutral-700 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-600">
                Cancel
            </button>
            <form id="deleteForm" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(blotterId, formNumber) {
    document.getElementById('deleteFormNumber').textContent = formNumber;
    document.getElementById('deleteForm').action = `/blotter/${blotterId}`;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('deleteModal').classList.add('flex');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.remove('flex');
}

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeDeleteModal();
});
</script>
    </div>
</x-layouts.app>
