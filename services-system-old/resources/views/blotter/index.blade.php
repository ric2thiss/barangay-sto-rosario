<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8">
<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-semibold">Blotter Records</h1>
        <a href="{{ route('blotter.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            New Record
        </a>
    </div>
 {{-- Filter bar --}}<form method="GET" action="{{ route('blotter.index') }}" class="mb-5 flex flex-wrap gap-2 items-end" id="filterForm">
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

    {{-- ✅ NEW: Approval quick-filter buttons --}}
    <div>
        <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Approval</label>
     <div>
   
    <div class="flex gap-1">
        <a href="{{ route('blotter.index', array_merge($filters, ['approval' => 'pending'])) }}"
           class="h-9 px-3 text-sm font-medium rounded-md border flex items-center
                  {{ ($filters['approval'] ?? '') === 'pending'
                     ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900 dark:text-amber-200 dark:border-amber-700'
                     : 'bg-white dark:bg-neutral-800 text-zinc-700 dark:text-zinc-300 border-neutral-300 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-700' }}">
            Pending
        </a>
        <a href="{{ route('blotter.index', array_merge($filters, ['approval' => 'approved'])) }}"
           class="h-9 px-3 text-sm font-medium rounded-md border flex items-center
                  {{ ($filters['approval'] ?? '') === 'approved'
                     ? 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700'
                     : 'bg-white dark:bg-neutral-800 text-zinc-700 dark:text-zinc-300 border-neutral-300 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-700' }}">
            Approved
        </a>
    </div>
</div>
    </div>

    <div class="flex gap-2">
        <button type="submit" class="h-9 px-4 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Filter</button>
        @if(array_filter(array_values($filters)))
            <a href="{{ route('blotter.index') }}" class="h-9 px-4 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-white dark:bg-neutral-800 border border-neutral-300 dark:border-neutral-600 rounded-md hover:bg-neutral-50 dark:hover:bg-neutral-700 flex items-center">Clear</a>
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
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Recorded By</th>
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
<td class="px-4 py-2 dark:text-[#e5e7eb]">
    <div class="flex flex-col gap-1">
        <span class="font-medium">{{ $record->recorder_name }}</span>
        @if($record->recorder_is_resident !== null)
            <span class="inline-flex w-fit items-center px-2 py-0.5 rounded-full text-xs font-medium
                {{ $record->recorder_is_resident
                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200'
                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                {{ $record->recorder_is_resident ? 'Resident' : 'Alien' }}
            </span>
        @endif
    </div>
</td>

          <td class="px-4 py-2">
    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('blotter.show', $record) }}" title="View" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
        </a>
        <a href="{{ route('blotter.edit', $record) }}" title="Edit" class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
        </a>
        {{-- <a href="{{ route('blotter.download-docx', $record) }}" title="Download DOCX" class="text-green-600 hover:text-green-900 dark:text-green-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
        </a> --}}
        {{-- New PDF button --}}
<a href="{{ route('blotter.download-pdf', $record) }}" title="Download PDF" class="text-rose-600 hover:text-rose-900 dark:text-rose-400">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
    </svg>
</a>

       {{-- ✅ Status update button --}}
@if($record->status === 'Pending')
    <form method="POST" action="{{ route('blotter.updateStatus', $record) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="Approved">
        <button type="submit" title="Mark as Approved"
            class="text-green-600 hover:text-green-900 dark:text-green-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
        </button>
    </form>
@elseif($record->status === 'Approved')
    <form method="POST" action="{{ route('blotter.updateStatus', $record) }}">
        @csrf @method('PATCH')
        <input type="hidden" name="status" value="Released">
        <button type="submit" title="Mark as Released"
            class="text-blue-600 hover:text-blue-900 dark:text-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
            </svg>
        </button>
    </form>
@endif

        <button type="button" onclick="confirmDelete({{ $record->blotter_id }}, '{{ $record->form_number }}')" title="Delete" class="text-red-600 hover:text-red-900 dark:text-red-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
        </button>
    </div>
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
