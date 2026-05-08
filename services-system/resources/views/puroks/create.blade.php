<x-layouts.app>
    <div class="max-w-lg mx-auto bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
        <h1 class="text-xl font-semibold text-neutral-900 dark:text-white mb-4">Add Purok</h1>
        <form action="{{ route('puroks.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Purok Name</label>
                <input type="text" name="purok_name" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
            </div>
            <div class="flex justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-700 rounded-md">Cancel</a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Save</button>
            </div>
        </form>
    </div>
</x-layouts.app>

