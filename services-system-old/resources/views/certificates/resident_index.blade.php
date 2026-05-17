<x-layouts.app :title="__('Certificate Requests')">
<div class="max-w-7xl mx-auto py-8">
    <div class="flex justify-between items-start gap-4 mb-6 flex-wrap">
        <div>
            <h1 class="text-2xl font-semibold">Certificate Requests</h1>
            <form method="GET" action="{{ route('certificates.resident_index') }}" class="mt-2 flex flex-wrap items-center gap-2" id="filterForm">
    <select name="status" onchange="this.form.submit()" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 px-2 py-1 text-sm">
        <option value="All" {{ request('status', 'All') === 'All' ? 'selected' : '' }}>All Statuses</option>
        <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
        <option value="Processing" {{ request('status') === 'Processing' ? 'selected' : '' }}>Processing</option>
        <option value="Released" {{ request('status') === 'Released' ? 'selected' : '' }}>Released</option>
        <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
    </select>

    <select name="type" onchange="this.form.submit()" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 px-2 py-1 text-sm">
        <option value="All" {{ request('type', 'All') === 'All' ? 'selected' : '' }}>All Types</option>
        @foreach($certificateTypes as $type)
            <option value="{{ $type }}" {{ request('type') === $type ? 'selected' : '' }}>{{ $type }}</option>
        @endforeach
    </select>

    <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
           placeholder="Search requests..."
           class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm">Filter</button>
</form>

<script>
    const searchInput = document.getElementById('searchInput');
    let searchTimer;
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    });
</script>
        </div>

        <div>
            <a href="{{ route('certificates.create') }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" wire:navigate>
                Request Certificate
            </a>
        </div>
    </div>

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
                        <td class="px-4 py-2 dark:text-[#e5e7eb]">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                @if($paymentStatus === 'Paid') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                {{ $paymentStatus }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-right dark:text-[#e5e7eb] font-medium">₱{{ number_format((float) $amountDue, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-neutral-500 dark:text-[#cbd5e1]">
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
</x-layouts.app>
