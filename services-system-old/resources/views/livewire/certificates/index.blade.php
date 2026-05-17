 <div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-start gap-4 mb-6 flex-wrap">
    <div>
        <h1 class="text-2xl font-semibold">Certificate Requests</h1>
        <div class="mt-2 flex flex-wrap gap-2 items-center">
            <label class="text-sm font-medium text-zinc-700 dark:text-white">Status:</label>
            <select wire:model.live="statusFilter" class="rounded-md border neutral-300 dark:border-neutral-700 dark:bg-neutral-800 px-2 py-1 text-sm">
                <option value="All">All</option>
                <option value="Approved">Approved</option>
                <option value="Pending">Pending</option>
                <option value="Processing">Processing</option>
                <option value="Released">Released</option>
                <option value="Rejected">Rejected</option>
                
            </select>

            <label class="text-sm font-medium text-zinc-700 dark:text-white">Type:</label>
            <select wire:model.live="typeFilter" class="rounded-md border neutral-300 dark:border-neutral-700 dark:bg-neutral-800 px-2 py-1 text-sm">
                <option value="All">All</option>
                @foreach($certificateTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </select>

            {{-- ✅ NEW: Approval quick-filter buttons --}}
            <span class="text-zinc-300 dark:text-zinc-600 select-none">|</span>

           <button
    wire:click="$set('approvalFilter', 'pending')"
    class="px-3 py-1 text-sm font-medium rounded-md border transition-colors
           {{ $this->approvalFilter === 'pending'
              ? 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-900 dark:text-amber-200 dark:border-amber-700'
              : 'bg-white dark:bg-neutral-800 text-zinc-600 dark:text-zinc-300 border-neutral-300 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-700' }}">
    Pending
</button>
<button
    wire:click="$set('approvalFilter', 'approved')"
    class="px-3 py-1 text-sm font-medium rounded-md border transition-colors
           {{ $this->approvalFilter === 'approved'
              ? 'bg-green-100 text-green-800 border-green-300 dark:bg-green-900 dark:text-green-200 dark:border-green-700'
              : 'bg-white dark:bg-neutral-800 text-zinc-600 dark:text-zinc-300 border-neutral-300 dark:border-neutral-600 hover:bg-neutral-50 dark:hover:bg-neutral-700' }}">
    Approved
</button>
        </div>
    </div>

    <div class="flex items-center space-x-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search requests..." class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <button onclick="location.reload()" class="px-4 py-2 text-sm font-medium text-white bg-gray-600 rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
            Refresh
        </button>
        <a href="{{ route('certificates.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" wire:navigate>
            Issue Certificate
        </a>
    </div>
</div>

    @if (session()->has('message'))
        <div id="success-message" class="mb-4 rounded-md bg-green-50 p-4 dark:bg-green-900">
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
        <script>
            (function() {
                const message = document.getElementById('success-message');
                if (message) {
                    setTimeout(function() {
                        message.style.transition = 'opacity 0.5s';
                        message.style.opacity = '0';
                        setTimeout(function() {
                            message.remove();
                        }, 500);
                    }, 5000);
                }
            })();
        </script>
    @endif


    <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent table-modern table-theme-blue table-force-dark">
        <table class="min-w-full text-sm dark:bg-[#0f1e2e]">
            <thead class="bg-neutral-50 dark:bg-[#15233b] dark:text-[#e5e7eb]">
                <tr>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Date</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Resident</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Type</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Purpose</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Status</th>
                    <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Payment Status</th>
                    <th class="px-4 py-2 text-right font-medium dark:text-[#e5e7eb]">Amount Due</th>
                    <th class="px-4 py-2 text-right font-medium dark:text-[#e5e7eb]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                   @php
                        $paymentStatus = $request->payment_status ?? 'Pending';
                        $amountDue = $request->amount_due ?? (($request->certificateType->price ?? 0) + 30);
                    @endphp
                    <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e]">
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->date_requested->format('M d, Y') }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                      {{ $request->resident?->first_name }} {{ $request->resident?->surname }}

                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->certificateType->certificate_name }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->purpose }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($request->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($request->status === 'Completed') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                 @elseif($request->status === 'Approved') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @elseif($request->status === 'Released') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
                               {{ $request->status === 'Approved' ? 'Ready to Release' : $request->status }}
                            </span>
                        </td>

                         <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($paymentStatus === 'Paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                {{ $paymentStatus }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right dark:text-[#e5e7eb] font-medium">₱{{ number_format((float) $amountDue, 2) }}</td>


                        <td class="px-4 py-2 text-right">
                            @php
                                $templateMap = [
                                    'Barangay Clearance'       => 'barangay_clearance',
                                    'Certificate of Residency' => 'residency',
                                    'Indigency Certificate'    => 'indigent',
                                    'Good Moral Certificate'   => 'goodmoral',
                                    'Barangay Permit'          => 'brgy_permit',
                                ];
                                $certName = $request->certificateType->certificate_name ?? '';
                                $template = $templateMap[$certName] ?? 'residency';
                            @endphp
                            <div class="flex items-center justify-end gap-3">

                                @if($request->status === 'Rejected')
                                    {{-- Rejected: only show reset-to-pending icon --}}
                                    <button wire:click="updateStatus({{ $request->request_id }}, 'Pending')"
                                            title="Reset to Pending"
                                            class="text-zinc-500 hover:text-indigo-600 dark:text-zinc-400 dark:hover:text-indigo-400 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"/>
                                        </svg>
                                    </button>

                                @else
                                    {{-- PDF download — shown for all non-rejected statuses --}}
                                    <a href="{{ route('certificates.export', [$request->request_id, 'pdf', $template]) }}"
                                       title="Download PDF"
                                       target="_blank"
                                       class="text-rose-600 hover:text-rose-800 dark:text-rose-400 dark:hover:text-rose-300 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                        </svg>
                                    </a>

                                    {{-- Approve — Pending & Processing --}}
                                    @if(in_array($request->status, ['Pending', 'Processing']))
                                        <button wire:click="updateStatus({{ $request->request_id }}, 'Approved')"
                                                title="Mark as Approved"
                                                class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Release — Pending, Processing & Approved --}}
                                    @if(in_array($request->status, ['Pending', 'Processing', 'Approved']))
                                        <button wire:click="updateStatus({{ $request->request_id }}, 'Released')"
                                                title="Mark as Released"
                                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Reject — Pending, Processing & Approved --}}
                                    @if(in_array($request->status, ['Pending', 'Processing', 'Approved']))
                                        <button wire:click="updateStatus({{ $request->request_id }}, 'Rejected')"
                                                title="Reject"
                                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Mark payment as paid — only when payment is still pending --}}
                                    @if(($request->payment_status ?? 'Pending') === 'Pending')
                                        <button wire:click="updatePaymentStatus({{ $request->request_id }}, 'Paid')"
                                                title="Mark Payment as Paid"
                                                class="text-amber-600 hover:text-amber-800 dark:text-amber-400 dark:hover:text-amber-300 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/>
                                            </svg>
                                        </button>
                                    @endif
                                @endif

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
                            No certificate requests found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $requests->links() }}
    </div>
</div>