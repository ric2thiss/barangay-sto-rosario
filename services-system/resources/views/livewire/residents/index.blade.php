<div class="max-w-5xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Residents</h1>
        <div class="flex space-x-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search residents..." class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <a href="{{ route('residents.import') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700 btn-force-dark">
                Import
            </a>
            <a href="{{ route('residents.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Add Resident
            </a>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 rounded-md bg-green-50 dark:bg-green-900 border border-green-200 dark:border-green-700">
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
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Last Name</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">First Name</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Middle Name</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Age</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Purok</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Contact</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Household No.</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Actions</th>
                </tr>
            </thead>
            <tbody class="dark:bg-[#0f1e2e]">
                @forelse ($residents as $resident)
                    <tr class="border-t border-neutral-200 dark:border-[#1f3b5c] dark:bg-[#0f1e2e]">
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->last_name }} {{ $resident->suffix ? $resident->suffix : '' }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->first_name }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->middle_name }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->age }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->purok->purok_name ?? '' }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->contact_number }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $resident->household_number }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('residents.show', $resident) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">View</a>
                            <a href="{{ route('residents.edit', $resident) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
                            No residents have been added yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $residents->links() }}
    </div>
</div>
