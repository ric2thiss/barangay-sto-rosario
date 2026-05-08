<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Blotter Records report</h1>
        <div class="flex space-x-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search blotter..." class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            {{-- <a href="{{ route('blotter.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" wire:navigate>
                New Record
            </a> --}}
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800 dark:text-green-200">
                        {{ session('message') }}
                    </p>
                </div>
            </div>
        </div>

    @endif

    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent table-modern table-theme-blue table-force-dark">
        <table class="min-w-full text-sm dark:bg-[#0f1e2e]">
            <thead class="bg-neutral-50 dark:bg-[#15233b] dark:text-[#e5e7eb]">
                <tr>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Incident Date</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Type</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Complainant</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Respondent</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Status</th>
                    <th class="px-4 py-2 text-right font-medium dark:text-[#e5e7eb]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($records as $record)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e]">
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->incident_date->format('M d, Y') }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->incident_type }}</td>
                                         <td class="px-4 py-2 dark:text-[#e5e7eb]">
    {{ $record->complainant_names ?? 'N/A' }}
</td>
<td class="px-4 py-2 dark:text-[#e5e7eb]">
    {{ $record->respondent_names ?? 'N/A' }}
</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($record->status === 'Active') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($record->status === 'Settled') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200 @endif">
                                {{ $record->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            {{-- Add actions like view/edit here later --}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
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
