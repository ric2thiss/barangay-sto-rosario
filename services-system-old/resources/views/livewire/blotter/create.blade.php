<div class="max-w-2xl mx-auto py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">New Blotter Record</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Complainant Search -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Complainant</label>
                    <div class="mb-2">
                        <input wire:model.live.debounce.300ms="searchComplainant" type="text" placeholder="Search name..." class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <select wire:model="complainant_id" size="3" class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Complainant</option>
                        @foreach($complainants as $resident)
                            <option value="{{ $resident->resident_id }}">
                                {{ $resident->last_name }}, {{ $resident->first_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('complainant_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Respondent Search -->
                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Respondent</label>
                    <div class="mb-2">
                        <input wire:model.live.debounce.300ms="searchRespondent" type="text" placeholder="Search name..." class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                    <select wire:model="respondent_id" size="3" class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Respondent</option>
                        @foreach($respondents as $resident)
                            <option value="{{ $resident->resident_id }}">
                                {{ $resident->last_name }}, {{ $resident->first_name }}
                            </option>
                        @endforeach
                    </select>
                    @error('respondent_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label for="incident_type" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Incident Type</label>
                <input wire:model="incident_type" type="text" id="incident_type" placeholder="e.g., Noise Complaint, Property Dispute" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('incident_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="incident_details" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Incident Details</label>
                <textarea wire:model="incident_details" id="incident_details" rows="4" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                @error('incident_details') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="incident_date" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Incident Date</label>
                    <input wire:model="incident_date" type="date" id="incident_date" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('incident_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Status</label>
                    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Active">Active</option>
                        <option value="Settled">Settled</option>
                        <option value="Dismissed">Dismissed</option>
                        <option value="Pending">Pending</option>
                    </select>
                    @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <a href="{{ route('blotter.index') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700" wire:navigate>
                Cancel
            </a>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Record
            </button>
        </div>
    </form>
</div>
