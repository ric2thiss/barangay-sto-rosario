<div class="max-w-2xl mx-auto py-8">
    <h1 class="text-2xl font-semibold mb-6">Generate Certificate of Residency</h1>

    @if(session()->has('message'))
        <div class="mb-4 p-4 rounded-md bg-green-50 border border-green-200">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('message') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-6">
        <div class="bg-white p-6 rounded-lg border border-gray-200 space-y-6">
            <div>
                <label for="resident_id" class="block text-sm font-medium text-gray-700">Resident</label>
                
                <div class="mb-2">
                    <input wire:model.live.debounce.300ms="searchResident" type="text" placeholder="Search by name..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <select wire:model="resident_id" id="resident_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Select Resident ({{ $residents->count() }} found)</option>
                    @foreach($residents as $resident)
                        <option value="{{ $resident->resident_id }}">
                            {{ $resident->last_name }}, {{ $resident->first_name }} {{ $resident->middle_name }}
                        </option>
                    @endforeach
                </select>
                @if(empty($searchResident) && $residents->count() >= 50)
                    <p class="text-xs text-gray-500 mt-1">Showing first 50 residents. Please use search to find others.</p>
                @endif
                @error('resident_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="purpose" class="block text-sm font-medium text-gray-700">Purpose</label>
                <input wire:model="purpose" type="text" id="purpose" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="For various purposes">
                @error('purpose') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Generate Certificate
            </button>
        </div>
    </form>
</div>
