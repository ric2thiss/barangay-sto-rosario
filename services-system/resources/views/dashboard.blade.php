<x-layouts.app>
<div class="max-w-7xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-white tracking-tight">Blotter & Certificate Analytics</h1>
            <p class="text-sm text-zinc-400 dark:text-zinc-500 mt-0.5">Certificate requests and blotter form usage</p>
        </div>

        {{-- Export Button --}}
        <a id="exportBtn"
           href="{{ route('analytics.export', array_merge([
               'filter_type' => $filters['filterType'],
               'date'        => $filters['date'],
               'month'       => $filters['month'],
               'year'        => $filters['year'],
           ], ['panel' => 'cert'])) }}"
           target="_blank"
           class="inline-flex items-center gap-2 px-3.5 py-2 text-sm font-medium text-white bg-rose-500 hover:bg-rose-600 active:bg-rose-700 rounded-lg transition-colors">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            <span id="exportText">Export Certificate Report</span>
        </a>
    </div>

    {{-- Filter + Tab bar row --}}
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">

        {{-- Filters --}}
        <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-zinc-400 dark:text-zinc-500 mb-1.5 uppercase tracking-wide">Filter by</label>
                <select name="filter_type" onchange="this.form.submit()"
                        class="rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm h-9 px-3 pr-8 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="date"  {{ $filters['filterType'] === 'date'  ? 'selected' : '' }}>Specific date</option>
                    <option value="month" {{ $filters['filterType'] === 'month' ? 'selected' : '' }}>Month & year</option>
                    <option value="year"  {{ $filters['filterType'] === 'year'  ? 'selected' : '' }}>Year only</option>
                    <option value="all"   {{ $filters['filterType'] === 'all'   ? 'selected' : '' }}>All time</option>
                </select>
            </div>

            @if($filters['filterType'] === 'date')
                <div>
                    <label class="block text-xs font-medium text-zinc-400 dark:text-zinc-500 mb-1.5 uppercase tracking-wide">Date</label>
                    <input type="date" name="date" value="{{ $filters['date'] }}" onchange="this.form.submit()"
                           class="rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm h-9 px-3 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            @elseif($filters['filterType'] === 'month')
                <div>
                    <label class="block text-xs font-medium text-zinc-400 dark:text-zinc-500 mb-1.5 uppercase tracking-wide">Month & year</label>
                    <input type="month" name="month" value="{{ $filters['month'] }}" onchange="this.form.submit()"
                           class="rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm h-9 px-3 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            @elseif($filters['filterType'] === 'year')
                <div>
                    <label class="block text-xs font-medium text-zinc-400 dark:text-zinc-500 mb-1.5 uppercase tracking-wide">Year</label>
                    <select name="year" onchange="this.form.submit()"
                            class="rounded-lg border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 text-sm h-9 px-3 pr-8 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach(range(now()->year, 2020) as $y)
                            <option value="{{ $y }}" {{ $filters['year'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <input type="hidden" name="panel" id="activePanel" value="cert">
        </form>

        {{-- Tab Toggle --}}
        <div class="flex gap-1.5 p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
            <button onclick="showPanel('cert')" id="btn-cert"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm">
                Certificates
            </button>
            <button onclick="showPanel('blotter')" id="btn-blotter"
                    class="px-4 py-1.5 text-sm font-medium rounded-md transition-all text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                Blotter Records
            </button>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         CERTIFICATE PANEL
    ════════════════════════════════════════════ --}}
    <div id="panel-cert">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">

            {{-- Total Certificates --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total certificates</span>
                    <span class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-semibold text-zinc-900 dark:text-white tracking-tight">{{ $totalCerts }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">requests in selected period</p>
                <div class="mt-3 h-0.5 w-8 rounded-full bg-indigo-500"></div>
            </div>

            {{-- Certificate Types --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Certificate types</span>
                    <span class="w-7 h-7 rounded-lg bg-violet-50 dark:bg-violet-950/50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5l7 7-7 7-5-5V3z"/>
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-semibold text-zinc-900 dark:text-white tracking-tight">{{ $certificateStats->count() }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">distinct types requested</p>
                <div class="mt-3 h-0.5 w-8 rounded-full bg-violet-500"></div>
            </div>
        </div>

        {{-- Chart + Table --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            {{-- Donut Chart --}}
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Certificate types availed</h2>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 mb-5">Distribution by type</p>

                @if($certificateStats->isEmpty())
                    <div class="flex flex-col items-center justify-center h-48 gap-2">
                        <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                            <svg class="w-5 h-5 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">No data for this period</span>
                    </div>
                @else
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative w-44 h-44">
                            <canvas id="certChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $totalCerts }}</span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">total</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2" id="certLegend"></div>
                @endif
            </div>

            {{-- Breakdown Table --}}
            <div class="lg:col-span-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Certificate breakdown</h3>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">Full breakdown with counts and share</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-5 py-2.5 text-left text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider w-8">#</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Type</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Count</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certificateStats as $type => $count)
                                @php
                                    $pct = $totalCerts > 0 ? round($count / $totalCerts * 100, 1) : 0;
                                    $colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'];
                                @endphp
                                <tr class="border-t border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="px-5 py-3 text-zinc-300 dark:text-zinc-600 text-xs">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                  style="background: {{ $colors[$loop->index % 10] }}"></span>
                                            <span class="text-zinc-700 dark:text-zinc-200 font-medium text-sm">{{ $type }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-zinc-900 dark:text-white">{{ $count }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <div class="w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                                                <div class="h-1 rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="text-xs text-zinc-400 dark:text-zinc-500 tabular-nums w-9 text-right">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-zinc-400 text-sm">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($certificateStats->isNotEmpty())
                        <tfoot>
                            <tr class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                <td colspan="2" class="px-5 py-3 text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-zinc-900 dark:text-white">{{ $totalCerts }}</td>
                                <td class="px-5 py-3 text-right text-xs text-zinc-400 dark:text-zinc-500">100%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         BLOTTER PANEL
    ════════════════════════════════════════════ --}}
    <div id="panel-blotter" style="display:none">

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">

            {{-- Total Blotter Records --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total blotter records</span>
                    <span class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-semibold text-zinc-900 dark:text-white tracking-tight">{{ $totalBlotters }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">records in selected period</p>
                <div class="mt-3 h-0.5 w-8 rounded-full bg-emerald-500"></div>
            </div>

            {{-- KP Form Types --}}
            <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 px-5 py-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">KP form types</span>
                    <span class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/50 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </span>
                </div>
                <p class="text-3xl font-semibold text-zinc-900 dark:text-white tracking-tight">{{ $blotterStats->count() }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">distinct form types used</p>
                <div class="mt-3 h-0.5 w-8 rounded-full bg-teal-500"></div>
            </div>
        </div>

        {{-- Chart + Table --}}
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

            {{-- Donut Chart --}}
            <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 p-5">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-white">Blotter KP forms used</h2>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 mb-5">Distribution by KP form type</p>

                @if($blotterStats->isEmpty())
                    <div class="flex flex-col items-center justify-center h-48 gap-2">
                        <div class="w-10 h-10 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
                            <svg class="w-5 h-5 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span class="text-sm text-zinc-400 dark:text-zinc-500">No data for this period</span>
                    </div>
                @else
                    <div class="flex items-center justify-center mb-5">
                        <div class="relative w-44 h-44">
                            <canvas id="blotterChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $totalBlotters }}</span>
                                <span class="text-xs text-zinc-400 dark:text-zinc-500">total</span>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-2" id="blotterLegend"></div>
                @endif
            </div>

            {{-- Breakdown Table --}}
            <div class="lg:col-span-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">
                <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Blotter form breakdown</h3>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">Full breakdown with counts and share</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-800/50">
                                <th class="px-5 py-2.5 text-left text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider w-8">#</th>
                                <th class="px-5 py-2.5 text-left text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">KP Form</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Count</th>
                                <th class="px-5 py-2.5 text-right text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($blotterStats as $form => $count)
                                @php
                                    $pct = $totalBlotters > 0 ? round($count / $totalBlotters * 100, 1) : 0;
                                    $colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'];
                                @endphp
                                <tr class="border-t border-zinc-100 dark:border-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                    <td class="px-5 py-3 text-zinc-300 dark:text-zinc-600 text-xs">{{ $loop->iteration }}</td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0"
                                                  style="background: {{ $colors[$loop->index % 10] }}"></span>
                                            <span class="text-zinc-700 dark:text-zinc-200 font-medium text-sm">{{ $form }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-zinc-900 dark:text-white">{{ $count }}</td>
                                    <td class="px-5 py-3 text-right">
                                        <div class="flex items-center justify-end gap-2.5">
                                            <div class="w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                                                <div class="h-1 rounded-full bg-emerald-500" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="text-xs text-zinc-400 dark:text-zinc-500 tabular-nums w-9 text-right">{{ $pct }}%</span>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-10 text-center text-zinc-400 text-sm">No data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($blotterStats->isNotEmpty())
                        <tfoot>
                            <tr class="border-t border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                                <td colspan="2" class="px-5 py-3 text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider">Total</td>
                                <td class="px-5 py-3 text-right font-semibold text-zinc-900 dark:text-white">{{ $totalBlotters }}</td>
                                <td class="px-5 py-3 text-right text-xs text-zinc-400 dark:text-zinc-500">100%</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
     PUROK × CASE SUBJECT HEATMAP
════════════════════════════════════════════ --}}
@if($purokStats->isNotEmpty())
<div class="mt-4 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 overflow-hidden">

    {{-- Section Header --}}
    <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Incident hotspot map</h3>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5">Case subjects by purok — darker means more incidents</p>
        </div>
        <span class="text-xs text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-full border border-zinc-200 dark:border-zinc-700">
            {{ $purokStats->count() }} {{ Str::plural('purok', $purokStats->count()) }}
        </span>
    </div>

    @if($allCaseSubjects->isNotEmpty())
    @php
        $heatMax = collect($purokStats)->map(fn($d) => $d['subjects']->values()->max() ?? 0)->max();
    @endphp

    <div class="p-5">

        {{-- Color scale legend --}}
        <div class="flex items-center justify-end gap-3 mb-5">
            <span class="text-xs text-zinc-400 dark:text-zinc-500">0</span>
            <div class="flex rounded overflow-hidden" style="height: 12px; width: 140px;">
                <div style="flex:1; background: rgba(99,102,241,0.08)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.20)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.35)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.52)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.68)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.82)"></div>
                <div style="flex:1; background: rgba(99,102,241,0.95)"></div>
            </div>
            <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $heatMax }}</span>
        </div>

        {{-- Heatmap grid --}}
        <div class="overflow-x-auto">
            <table class="w-full text-xs border-separate" style="border-spacing: 3px;">
                <thead>
                    <tr>
                        {{-- empty top-left corner --}}
                        <th class="text-left pb-1 pr-4 min-w-[140px]"></th>
                        @foreach($allCaseSubjects as $subject)
                            <th class="text-center pb-1 font-medium text-zinc-400 dark:text-zinc-500 whitespace-nowrap px-1"
                                style="min-width: 70px;">
                                {{ $subject }}
                            </th>
                        @endforeach
                        <th class="text-right pb-1 pl-2 font-medium text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                            Total
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purokStats as $purokName => $data)
                        @php $purokTotal = $data['subjects']->sum(); @endphp
                        <tr>
                            {{-- Purok label --}}
                            <td class="pr-4 py-0.5 font-medium text-zinc-600 dark:text-zinc-300 whitespace-nowrap text-right align-middle"
                                style="min-width:140px">
                                {{ $purokName }}
                                <span class="block text-zinc-400 dark:text-zinc-500 font-normal text-[10px]">{{ $purokTotal }} records</span>
                            </td>

                            {{-- Heat cells --}}
                            @foreach($allCaseSubjects as $subject)
                                @php
                                    $val       = $data['subjects']->get($subject, 0);
                                    $intensity = $heatMax > 0 ? $val / $heatMax : 0;
                                    $alpha     = $val > 0 ? round($intensity * 0.87 + 0.08, 3) : 0;
                                    $textColor = $alpha > 0.5 ? '#ffffff' : ($alpha > 0 ? '#3730a3' : '');
                                @endphp
                                <td class="align-middle" style="padding: 0; min-width: 70px;">
                                    @if($val > 0)
                                        <div class="flex flex-col items-center justify-center rounded-md font-semibold tabular-nums"
                                             style="height: 44px; background: rgba(99,102,241,{{ $alpha }}); color: {{ $textColor }};">
                                            <span style="font-size: 13px; line-height: 1;">{{ $val }}</span>
                                            <span style="font-size: 9px; opacity: 0.75; margin-top: 2px;">
                                                {{ $heatMax > 0 ? round($val / $heatMax * 100) : 0 }}%
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center rounded-md"
                                             style="height: 44px; background: rgba(113,113,122,0.06);">
                                            <span class="text-zinc-200 dark:text-zinc-700">—</span>
                                        </div>
                                    @endif
                                </td>
                            @endforeach

                            {{-- Row total --}}
                            <td class="pl-3 text-right font-semibold text-zinc-900 dark:text-white align-middle whitespace-nowrap">
                                {{ $purokTotal }}
                            </td>
                        </tr>
                    @endforeach

                    {{-- Column totals row --}}
                    <tr>
                        <td class="pt-2 pr-4 text-right text-zinc-400 dark:text-zinc-500 font-medium uppercase tracking-wider"
                            style="font-size: 10px;">
                            Total
                        </td>
                        @foreach($allCaseSubjects as $subject)
                            @php $colTotal = collect($purokStats)->sum(fn($d) => $d['subjects']->get($subject, 0)); @endphp
                            <td class="pt-2 text-center font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $colTotal }}
                            </td>
                        @endforeach
                        <td class="pt-2 pl-3 text-right font-bold text-zinc-900 dark:text-white">
                            {{ collect($purokStats)->sum(fn($d) => $d['subjects']->sum()) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Incident Areas section (preserved below heatmap) --}}
        @php $hasAreas = collect($purokStats)->contains(fn($d) => $d['areas']->isNotEmpty()); @endphp
        @if($hasAreas)
        <div class="mt-6 pt-5 border-t border-zinc-100 dark:border-zinc-800">
            <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 uppercase tracking-wider mb-4">Incident areas by purok</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-5">
                @foreach($purokStats as $purokName => $data)
                    @if($data['areas']->isNotEmpty())
                    <div>
                        <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-2">{{ $purokName }}</p>
                        <div class="space-y-1.5">
                            @foreach($data['areas'] as $area => $count)
                                @php $pct = $data['areas']->sum() > 0 ? round($count / $data['areas']->sum() * 100) : 0; @endphp
                                <div class="flex items-center gap-2">
                                    <svg class="w-3 h-3 text-teal-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400 w-28 truncate flex-shrink-0">{{ $area }}</span>
                                    <div class="flex-1 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1 overflow-hidden">
                                        <div class="h-1 rounded-full bg-teal-400" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300 w-4 text-right flex-shrink-0">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
        @endif

    </div>
    @endif

</div>
@endif

    </div>{{-- end panel-blotter --}}

</div>{{-- end max-w-7xl --}}

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const COLORS = [
    '#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6',
    '#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'
];

function showPanel(name) {
    ['cert', 'blotter'].forEach(p => {
        document.getElementById('panel-' + p).style.display = p === name ? 'block' : 'none';

        const btn = document.getElementById('btn-' + p);
        if (p === name) {
            btn.className = 'px-4 py-1.5 text-sm font-medium rounded-md transition-all bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white shadow-sm';
        } else {
            btn.className = 'px-4 py-1.5 text-sm font-medium rounded-md transition-all text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200';
        }
    });

    const exportText = document.getElementById('exportText');
    if (exportText) {
        exportText.innerText = name === 'blotter'
            ? 'Export Blotter Report'
            : 'Export Certificate Report';
    }

    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        const url = new URL(exportBtn.href);
        url.searchParams.set('panel', name);
        exportBtn.href = url.toString();
    }

    document.getElementById('activePanel').value = name;
}

function buildPie(canvasId, legendId, labels, values) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: COLORS.slice(0, labels.length),
                borderWidth: 2,
                borderColor: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
                hoverBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            return ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / total * 100)}%)`;
                        }
                    }
                }
            }
        }
    });

    const legend = document.getElementById(legendId);
    if (!legend) return;

    labels.forEach((label, i) => {
        const total = values.reduce((a, b) => a + b, 0);
        const pct   = total > 0 ? Math.round(values[i] / total * 100) : 0;
        legend.innerHTML += `
            <div class="flex items-center justify-between text-xs py-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:${COLORS[i]}"></span>
                    <span class="text-zinc-500 dark:text-zinc-400 truncate">${label}</span>
                </div>
                <span class="text-zinc-900 dark:text-white font-semibold ml-3 flex-shrink-0 tabular-nums">
                    ${values[i]} <span class="font-normal text-zinc-400">(${pct}%)</span>
                </span>
            </div>`;
    });
}

buildPie(
    'certChart', 'certLegend',
    @json($certificateStats->keys()),
    @json($certificateStats->values())
);
buildPie(
    'blotterChart', 'blotterLegend',
    @json($blotterStats->keys()),
    @json($blotterStats->values())
);
</script>
</x-layouts.app>