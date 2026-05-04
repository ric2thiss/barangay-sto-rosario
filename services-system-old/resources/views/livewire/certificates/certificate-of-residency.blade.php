<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Certificate of Residency Requests</h1>
        <div class="flex space-x-4">
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

    @if (session()->has('auto_download_request_id'))
        @php
            $requestId = session('auto_download_request_id');
            $certificateRequest = \App\Models\CertificateRequest::find($requestId);
            if ($certificateRequest) {
                $templateMap = [
                    'Barangay Clearance' => 'barangay_clearance',
                    'Certificate of Residency' => 'residency',
                    'Indigency Certificate' => 'indigent',
                    'Good Moral Certificate' => 'goodmoral',
                    'Barangay Permit' => 'brgy_permit',
                ];
                $template = $templateMap[$certificateRequest->certificateType->certificate_name] ?? 'residency';
                $downloadUrl = route('certificates.export', [$requestId, 'docx', $template]);
            }
            session()->forget('auto_download_request_id');
        @endphp
        @if (isset($downloadUrl))
            <script>
                window.location.href = '{{ $downloadUrl }}';
            </script>
        @endif
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
                    <th class="px-4 py-2 text-right font-medium dark:text-[#e5e7eb]">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($requests as $request)
                    <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e]">
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->date_requested->format('M d, Y') }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            {{ $request->resident->first_name }} {{ $request->resident->last_name }}
                        </td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->certificateType->certificate_name }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $request->purpose }}</td>
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($request->status === 'Pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($request->status === 'Released') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-neutral-100 text-neutral-800 dark:bg-neutral-700 dark:text-neutral-200 @endif">
                                {{ $request->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            <flux:dropdown align="end">
                                <flux:button icon="ellipsis-horizontal" size="sm" variant="ghost" inset="top bottom" />

                                <flux:menu>
                                    <!-- Export Options -->
                                    <flux:menu.group label="Export Certificate">
                                        <flux:menu.item href="{{ route('certificates.export', [$request->request_id, 'docx', 'residency']) }}" icon="arrow-down-tray" target="_blank">Certificate of Residency (Word)</flux:menu.item>
                                    </flux:menu.group>
                                    
                                    <flux:menu.separator />
                                    
                                    @if($request->status !== 'Released')
                                        <flux:menu.item wire:click="updateStatus({{ $request->request_id }}, 'Released')" icon="check">Mark as Released</flux:menu.item>
                                    @endif
                                    
                                    @if($request->status !== 'Rejected')
                                        <flux:menu.item wire:click="updateStatus({{ $request->request_id }}, 'Rejected')" icon="x-mark" class="text-red-600">Reject</flux:menu.item>
                                    @endif

                                    @if($request->status !== 'Pending')
                                        <flux:menu.item wire:click="updateStatus({{ $request->request_id }}, 'Pending')" icon="clock">Mark as Pending</flux:menu.item>
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
                            No certificate of residency requests found.
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