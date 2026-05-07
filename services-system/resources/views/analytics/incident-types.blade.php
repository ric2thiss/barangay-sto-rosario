<x-layouts.app>
<div class="max-w-5xl mx-auto py-8 px-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Incident Type Analytics</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Breakdown of blotter records by incident type</p>
        </div>
        <a href="{{ route('Dashboard') }}"
           class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Analytics
        </a>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('analytics.incident-types') }}"
          class="mb-8 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">Filter By</label>
            <select name="filter_type" onchange="this.form.submit()"
                    class="rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 shadow-sm text-sm h-9 px-3 pr-8 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="date"  {{ $filters['filterType'] === 'date'  ? 'selected' : '' }}>Specific Date</option>
                <option value="month" {{ $filters['filterType'] === 'month' ? 'selected' : '' }}>Month & Year</option>
                <option value="year"  {{ $filters['filterType'] === 'year'  ? 'selected' : '' }}>Year Only</option>
                <option value="all"   {{ $filters['filterType'] === 'all'   ? 'selected' : '' }}>All Time</option>
            </select>
        </div>

        @if($filters['filterType'] === 'date')
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">Date</label>
                <input type="date" name="date" value="{{ $filters['date'] }}" onchange="this.form.submit()"
                       class="rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 shadow-sm text-sm h-9 px-3 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        @elseif($filters['filterType'] === 'month')
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">Month & Year</label>
                <input type="month" name="month" value="{{ $filters['month'] }}" onchange="this.form.submit()"
                       class="rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 shadow-sm text-sm h-9 px-3 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        @elseif($filters['filterType'] === 'year')
            <div>
                <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1.5">Year</label>
                <select name="year" onchange="this.form.submit()"
                        class="rounded-lg border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 shadow-sm text-sm h-9 px-3 pr-8 text-zinc-700 dark:text-zinc-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @foreach(range(now()->year, 2020) as $y)
                        <option value="{{ $y }}" {{ $filters['year'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        @endif
    </form>

    {{-- Summary Card --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="relative bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 px-5 py-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-teal-50 to-transparent dark:from-teal-950/20 dark:to-transparent pointer-events-none"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-md bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-teal-600 dark:text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Total Tagged Records</p>
                </div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $total }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">blotter records with incident type in selected period</p>
            </div>
        </div>
        <div class="relative bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 px-5 py-4 shadow-sm overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-transparent dark:from-indigo-950/20 dark:to-transparent pointer-events-none"></div>
            <div class="relative">
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-md bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5l7 7-7 7-5-5V3z"/>
                        </svg>
                    </div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Incident Types</p>
                </div>
                <p class="text-3xl font-bold text-zinc-900 dark:text-white">{{ $incidentTypeStats->count() }}</p>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">distinct types recorded</p>
            </div>
        </div>
    </div>

    {{-- Chart + Table --}}
    <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">

        {{-- Donut Chart --}}
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 shadow-sm">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-white mb-0.5">Distribution</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-5">By incident type</p>

            @if($incidentTypeStats->isEmpty())
                <div class="flex flex-col items-center justify-center h-48 gap-2 text-zinc-400">
                    <svg class="w-8 h-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span class="text-sm">No data for this period</span>
                </div>
            @else
                <div class="flex items-center justify-center">
                    <div class="relative w-48 h-48">
                        <canvas id="incidentChart"></canvas>
                        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                            <span class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $total }}</span>
                            <span class="text-xs text-zinc-400 dark:text-zinc-500">total</span>
                        </div>
                    </div>
                </div>
                <div class="mt-5 space-y-2" id="incidentLegend"></div>
            @endif
        </div>

        {{-- Breakdown Table --}}
        <div class="lg:col-span-3 bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-100 dark:border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">Incident Type Breakdown</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">Full breakdown with counts and share</p>
                </div>
                <span class="text-xs text-zinc-400 dark:text-zinc-500 bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 rounded-full">
                    {{ $incidentTypeStats->count() }} {{ Str::plural('type', $incidentTypeStats->count()) }}
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/60">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">#</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Incident Type</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Count</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Share</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse($incidentTypeStats as $type => $count)
                            @php $pct = $total > 0 ? round($count / $total * 100, 1) : 0; @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40 transition-colors">
                                <td class="px-5 py-3 text-zinc-400 dark:text-zinc-500 text-xs">{{ $loop->iteration }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full flex-shrink-0"
                                              style="background: {{ ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'][$loop->index % 10] }}"></span>
                                        <span class="text-zinc-700 dark:text-zinc-300 font-medium">{{ $type }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-right font-semibold text-zinc-900 dark:text-white">{{ $count }}</td>
                                <td class="px-5 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="w-16 bg-zinc-100 dark:bg-zinc-800 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full bg-teal-500" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400 w-9 text-right">{{ $pct }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-center text-zinc-400 text-sm">
                                    No incident types recorded for this period.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($incidentTypeStats->isNotEmpty())
                    <tfoot>
                        <tr class="bg-zinc-50 dark:bg-zinc-800/60 border-t border-zinc-200 dark:border-zinc-700">
                            <td colspan="2" class="px-5 py-3 text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Total</td>
                            <td class="px-5 py-3 text-right font-bold text-zinc-900 dark:text-white">{{ $total }}</td>
                            <td class="px-5 py-3 text-right text-xs text-zinc-400">100%</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const COLORS = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#84cc16'];
const ctx = document.getElementById('incidentChart');
if (ctx) {
    const labels = @json($incidentTypeStats->keys());
    const values = @json($incidentTypeStats->values());
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: COLORS.slice(0, labels.length),
                borderWidth: 3,
                borderColor: document.documentElement.classList.contains('dark') ? '#18181b' : '#ffffff',
            }]
        },
        options: {
            responsive: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => {
                            const total = c.dataset.data.reduce((a, b) => a + b, 0);
                            return ` ${c.label}: ${c.parsed} (${Math.round(c.parsed / total * 100)}%)`;
                        }
                    }
                }
            }
        }
    });

    const legend = document.getElementById('incidentLegend');
    labels.forEach((label, i) => {
        const total = values.reduce((a, b) => a + b, 0);
        const pct = total > 0 ? Math.round(values[i] / total * 100) : 0;
        legend.innerHTML += `
            <div class="flex items-center justify-between text-xs py-1">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:${COLORS[i]}"></span>
                    <span class="text-zinc-600 dark:text-zinc-400 truncate">${label}</span>
                </div>
                <span class="text-zinc-900 dark:text-white font-semibold ml-3 flex-shrink-0">
                    ${values[i]} <span class="font-normal text-zinc-400">(${pct}%)</span>
                </span>
            </div>`;
    });
}
</script>
</x-layouts.app>