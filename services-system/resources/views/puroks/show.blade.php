<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ $purok->purok_name }}</h1>
            <div class="flex items-center gap-3">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 dark:text-neutral-300 bg-neutral-100 dark:bg-neutral-700 rounded-md">Back</a>
                @if($totalResidents > 0)
                    <button type="button" class="px-4 py-2 text-sm font-medium text-white rounded-md bg-neutral-400 cursor-not-allowed" disabled>
                        Delete
                    </button>
                @else
                    <flux:modal.trigger name="confirm-purok-deletion">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-white rounded-md bg-red-600 hover:bg-red-700" x-data x-on:click.prevent="$dispatch('open-modal', 'confirm-purok-deletion')">
                            Delete
                        </button>
                    </flux:modal.trigger>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Residents</p>
                <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $totalResidents }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Seniors (60+)</p>
                <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $totalSeniors }}</p>
            </div>
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total PWDs</p>
                <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $totalPwd }}</p>
            </div>
        </div>

        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Residents</h2>
                <form method="GET" action="{{ route('puroks.show', $purok) }}" class="flex items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <select name="filter" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="all" {{ request('filter', 'all') === 'all' ? 'selected' : '' }}>All</option>
                        <option value="male" {{ request('filter') === 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ request('filter') === 'female' ? 'selected' : '' }}>Female</option>
                        <option value="pwd" {{ request('filter') === 'pwd' ? 'selected' : '' }}>PWD</option>
                    </select>
                    <select name="sort" class="rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="asc" {{ request('sort', 'asc') === 'asc' ? 'selected' : '' }}>A→Z</option>
                        <option value="desc" {{ request('sort') === 'desc' ? 'selected' : '' }}>Z→A</option>
                    </select>
                    <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">
                        Apply
                    </button>
                    <a href="{{ route('puroks.export.consolidation', $purok) }}" class="px-3 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                        Export Consolidation
                    </a>
                    <a href="{{ route('puroks.export.residents', $purok) }}" class="px-3 py-2 text-sm font-medium text-white bg-emerald-600 rounded-md hover:bg-emerald-700">
                        Export All Information
                    </a>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-700/50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Age</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Sex</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Contact No.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">HH No.</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Health</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Social</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-neutral-500 dark:text-neutral-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-neutral-800 divide-y divide-neutral-200 dark:divide-neutral-700">
                        @forelse($residents as $resident)
                            <tr>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    {{ $resident->last_name }}, {{ $resident->first_name }}{{ $resident->middle_name ? ' ' . $resident->middle_name : '' }}{{ $resident->suffix ? ' ' . $resident->suffix : '' }}
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    {{ $resident->age ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    {{ $resident->sex }}
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    {{ $resident->contact_number ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    {{ $resident->household_number ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    <span class="inline-flex gap-2">
                                        <span>Sanitary: {{ $resident->getRawOriginal('sanitary_toilet') === null ? '-' : ($resident->sanitary_toilet ? 'Y' : 'N') }}</span>
                                        <span>Smoker: {{ $resident->getRawOriginal('smoker') === null ? '-' : ($resident->smoker ? 'Y' : 'N') }}</span>
                                        <span>Binge: {{ $resident->getRawOriginal('binge_drinker') === null ? '-' : ($resident->binge_drinker ? 'Y' : 'N') }}</span>
                                        <span>HPN: {{ $resident->getRawOriginal('hpn') === null ? '-' : ($resident->hpn ? 'Y' : 'N') }}</span>
                                        <span>DM: {{ $resident->getRawOriginal('dm') === null ? '-' : ($resident->dm ? 'Y' : 'N') }}</span>
                                        <span>PWD: {{ $resident->getRawOriginal('pwd') === null ? '-' : ($resident->pwd ? 'Y' : 'N') }}</span>
                                        <span>Water: {{ $resident->water_supply ?? '-' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-neutral-900 dark:text-white">
                                    <span class="inline-flex gap-2">
                                        <span>Membership: {{ $resident->membership ?? '-' }}</span>
                                        <span>Family Planning: {{ $resident->family_planning_method ?? '-' }}</span>
                                        <span>PHIC: {{ $resident->phic_no ?? '-' }}</span>
                                        <span>FB/Email: {{ $resident->fb_email_address ?? '-' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('residents.show', $resident) }}" class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 mr-2">View</a>
                                    <a href="{{ route('residents.edit', $resident) }}" class="px-3 py-1 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700">Edit</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-neutral-500 dark:text-neutral-400">No residents found for this purok</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                </div>
            @if($residents->hasPages())
                <div class="mt-4">
                    {{ $residents->links() }}
                </div>
            @endif
        </div>
            </div>
        </div>
    </div>
    <flux:modal name="confirm-purok-deletion" class="max-w-lg" closeable="false">
        <form action="{{ route('puroks.destroy', $purok) }}" method="POST" class="space-y-6">
            @csrf
            @method('DELETE')
            <div>
                <flux:heading size="lg">Delete this Purok?</flux:heading>
                <flux:subheading>This action cannot be undone.</flux:subheading>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button>Cancel</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="danger">Confirm Delete</flux:button>
            </div>
        </form>
    </flux:modal>
</x-layouts.app>
