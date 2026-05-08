<div class="max-w-2xl mx-auto py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Issue Certificate</h1>
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-6">
      <div>
    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Resident</label>

    {{-- Selected resident display --}}
    @if($resident_id && $selectedResident)
        <div class="mt-1 flex items-center justify-between px-3 py-2 rounded-md border border-indigo-300 dark:border-indigo-600 bg-indigo-50 dark:bg-indigo-900/30">
            <div>
                <span class="text-sm font-medium text-indigo-800 dark:text-indigo-200">
{{ $selectedResidentName }}
                </span>
                <span class="ml-2 text-xs text-indigo-500 dark:text-indigo-400">Selected</span>
            </div>
            <div class="flex items-center gap-3 ml-4">

                <button type="button" wire:click="clearResident"
                        class="text-red-600 hover:text-red-600 dark:hover:text-red-600 text-xs font-medium">
                    ✕ Remove
                </button>
            </div>
        </div>
    @else
        {{-- Search input --}}
        <div class="mb-2 mt-1">
            <input wire:model.live.debounce.300ms="searchResident" type="text"
                   placeholder="Type a name to search residents..."
                   class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>

        {{-- Only show list when something is typed --}}
        @if(strlen($searchResident) > 0)
            @if($residents->count() > 0)
                <select wire:model.live="resident_id" id="resident_id" size="5"
                        class="block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">{{ $residents->count() }} result(s) found</option>
                    @foreach($residents as $resident)
                     <option value="{{ $resident->id }}">
                            {{ $resident->last_name }}, {{ $resident->first_name }} {{ $resident->middle_name }}
                        </option>
                    @endforeach
                </select>
            @else
                <p class="text-xs text-neutral-500 dark:text-neutral-400 mt-1 px-1">No residents found for "{{ $searchResident }}".</p>
            @endif
        @else
            <p class="text-xs text-neutral-400 dark:text-neutral-500 mt-1 px-1">Start typing to search for a resident.</p>
        @endif
    @endif

    @error('resident_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
</div>

            <div>
                <label for="certificate_type_id" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Certificate Type</label>
                <select wire:model.live="certificate_type_id" id="certificate_type_id" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Select Certificate Type</option>
                    @foreach($certificateTypes as $type)
                        <option value="{{ $type->certificate_type_id }}">
                            {{ $type->certificate_name }}
                        </option>
                    @endforeach
                </select>
                @error('certificate_type_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="purpose" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Purpose</label>
                <input wire:model="purpose" type="text" id="purpose" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                @error('purpose') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="date_requested" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Date Requested</label>
                    <input wire:model="date_requested" type="date" id="date_requested" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('date_requested') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                {{-- Status field --}}
@if(!$isPendingOnly)
<div>
    <label for="status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Status</label>
    <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <option value="Pending">Pending</option>
        <option value="Processing">Processing</option>
        <option value="Released">Released</option>
        <option value="Rejected">Rejected</option>
    </select>
    @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
</div>
@else
    <input type="hidden" wire:model="status" value="Pending">
@endif
            </div>

            @if(!$isPendingOnly)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label for="payment_status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Payment Status</label>
                    <select wire:model="payment_status" id="payment_status" @disabled($isFreeCertificate) class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:cursor-not-allowed disabled:opacity-70">
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                    </select>
                    @if($isFreeCertificate)
                        <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Indigency certificate is free. Payment is automatically marked as Paid.</p>
                    @endif
                    @error('payment_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Amount</label>
                    <input wire:model.live="amount" type="number" id="amount" @readonly($isFreeCertificate) @disabled($isFreeCertificate) class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:cursor-not-allowed disabled:opacity-70">
                </div>

                <div>
                    <label for="bir_tax" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">BIR Tax</label>
                    <input wire:model="bir_tax" type="text" id="bir_tax" readonly class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <div>
                    <label for="total_amount" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Total Amount</label>
                    <input wire:model="total_amount" type="text" id="total_amount" readonly class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
            </div>
            @else
           <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <label for="payment_status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Payment Status</label>
                    <select wire:model="payment_status" id="payment_status" @disabled($isFreeCertificate) class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:cursor-not-allowed disabled:opacity-70">
                        <option value="Paid">Paid</option>
                        <option value="Pending">Pending</option>
                    </select>
                    @if($isFreeCertificate)
                        <p class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">Indigency certificate is free. Payment is automatically marked as Paid.</p>
                    @endif
                    @error('payment_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="amount" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Amount</label>
                    <input wire:model.live="amount" type="number" id="amount" @readonly($isFreeCertificate) @disabled($isFreeCertificate) class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:cursor-not-allowed disabled:opacity-70">
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">BIR Tax</label>
                    <input wire:model="bir_tax" type="text" readonly class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm sm:text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-neutral-700 dark:text-neutral-300">Amount To Pay</label>
                    <input wire:model="total_amount" type="text" readonly class="mt-1 block w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm sm:text-sm">
                </div>
            </div>
            @endif
        </div>

        <div class="flex justify-end space-x-3">
            {{-- Cancel button --}}
<a href="{{ $isPendingOnly ? route('certificates.resident_index') : route('certificates.index') }}"
   class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700"
   wire:navigate>
    Cancel
</a>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Save Request
            </button>
        </div>
    </form>
</div>
