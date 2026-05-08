<div class="max-w-5xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Certificate Types</h1>
        <div class="flex space-x-4">
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search types..." class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <a href="{{ route('certificate-types.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" wire:navigate>
                Add Type
            </a>
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

    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900">
        <table class="min-w-full text-sm">
            <thead class="bg-neutral-50 dark:bg-neutral-800">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Name</th>
                    <th class="px-4 py-2 text-left font-medium">Price</th>
                    <th class="px-4 py-2 text-right font-medium">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($certificateTypes as $type)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700">
                        <td class="px-4 py-2">{{ $type->certificate_name }}</td>
                        <td class="px-4 py-2">₱{{ number_format($type->price, 2) }}</td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <a href="{{ route('certificate-types.edit', $type) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" wire:navigate>Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-neutral-500">
                            No certificate types have been added yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-4">
        {{ $certificateTypes->links() }}
    </div>
</div>
