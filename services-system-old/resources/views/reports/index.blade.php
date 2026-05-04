<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Reports & Statistics</h1>
            <p class="text-neutral-600 dark:text-neutral-400">View and export various reports for the barangay system</p>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Residents -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 dark:bg-blue-900/30">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Total Residents</p>
                        <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $totalResidents ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Male Residents -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 dark:bg-green-900/30">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Male Residents</p>
                        <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $maleResidents ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Female Residents -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-pink-100 dark:bg-pink-900/30">
                        <svg class="w-6 h-6 text-pink-600 dark:text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Female Residents</p>
                        <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $femaleResidents ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <!-- Puroks Count -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-neutral-600 dark:text-neutral-400">Puroks</p>
                        <p class="text-2xl font-semibold text-neutral-900 dark:text-white">{{ $residentsPerPurok->count() ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Age Groups and Civil Status Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Age Groups -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Residents by Age Group</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Children (0-17)</span>
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $residentsByAge['children'] ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $totalResidents > 0 ? ($residentsByAge['children'] / $totalResidents * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Adults (18-59)</span>
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $residentsByAge['adults'] ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $totalResidents > 0 ? ($residentsByAge['adults'] / $totalResidents * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">Seniors (60+)</span>
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $residentsByAge['seniors'] ?? 0 }}</span>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                            <div class="bg-purple-600 h-2.5 rounded-full" style="width: {{ $totalResidents > 0 ? ($residentsByAge['seniors'] / $totalResidents * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Civil Status -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Residents by Civil Status</h3>
                <div class="space-y-4">
                    @foreach($residentsByCivilStatus as $status => $count)
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ ucfirst($status) }}</span>
                            <span class="text-sm font-medium text-neutral-700 dark:text-neutral-300">{{ $count }}</span>
                        </div>
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-2.5">
                            <div class="bg-amber-600 h-2.5 rounded-full" style="width: {{ $totalResidents > 0 ? ($count / $totalResidents * 100) : 0 }}%;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Export Options -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Export Reports</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('reports.export.residents') }}" class="px-4 py-3 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export Residents Data (CSV)
                </a>
                <a href="{{ route('reports.export.certificates') }}" class="px-4 py-3 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export Certificates (CSV)
                </a>
                <a href="{{ route('reports.export.blotters') }}" class="px-4 py-3 text-sm font-medium text-white bg-purple-600 rounded-md hover:bg-purple-700 transition-colors duration-200 flex items-center justify-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Export Blotters (CSV)
                </a>
            </div>
        </div>

        <!-- Monthly Registrations Chart -->
        <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Monthly Registrations</h3>
            <div class="h-64 flex items-end space-x-2">
                @foreach($monthlyRegistrations as $item)
                    <div class="flex flex-col items-center flex-1">
                        <div class="text-xs text-neutral-600 dark:text-neutral-400 mb-1">{{ $item['month'] }}</div>
                        <div 
                            class="w-full bg-gradient-to-t from-blue-500 to-blue-400 rounded-t-md" 
                            style="height: {{ $item['count'] > 0 ? min(100, $item['count'] * 2) : 5 }}%;"
                        ></div>
                        <div class="text-xs mt-1 text-neutral-700 dark:text-neutral-300">{{ $item['count'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Certificates -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Recent Certificates</h3>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @forelse($recentCertificates as $certificate)
                        <div class="border-b border-neutral-200 dark:border-neutral-700 pb-3 last:border-0 last:pb-0">
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-medium text-neutral-900 dark:text-white">{{ $certificate->certificateType->type_name ?? 'N/A' }}</p>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $certificate->resident->full_name ?? 'N/A' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $certificate->created_at->format('M d, Y') }}</p>
                                    <p class="text-sm font-medium text-green-600">₱{{ number_format($certificate->amount_paid, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-neutral-500 dark:text-neutral-400 text-center py-4">No recent certificates</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Blotter Records -->
            <div class="bg-white dark:bg-neutral-800 rounded-xl border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white mb-4">Recent Blotter Cases</h3>
                <div class="space-y-3 max-h-60 overflow-y-auto">
                    @forelse($recentBlotters as $blotter)
                        <div class="border-b border-neutral-200 dark:border-neutral-700 pb-3 last:border-0 last:pb-0">
                            <div class="flex justify-between">
                                <div>
                                    <p class="font-medium text-neutral-900 dark:text-white">{{ $blotter->case_number }}</p>
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ Str::limit($blotter->nature_of_complaint, 50) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-neutral-600 dark:text-neutral-400">{{ $blotter->created_at->format('M d, Y') }}</p>
                                    <p class="text-sm font-medium {{ $blotter->status == 'Resolved' ? 'text-green-600' : 'text-amber-600' }}">{{ $blotter->status }}</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-neutral-500 dark:text-neutral-400 text-center py-4">No recent blotter cases</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>