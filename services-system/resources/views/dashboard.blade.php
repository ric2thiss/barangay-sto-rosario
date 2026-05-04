<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        {{-- Page Header --}}
        <div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Overview</h1>
                <p class="text-slate-500 mt-1">Real-time analytics for certificates and blotter records</p>
            </div>

            {{-- Global Actions / Export --}}
            <div class="flex items-center gap-3">
                <a id="exportBtn"
                   href="{{ route('analytics.export', array_merge([
                       'filter_type' => $filters['filterType'],
                       'date'        => $filters['date'],
                       'month'       => $filters['month'],
                       'year'        => $filters['year'],
                   ], ['panel' => 'cert'])) }}"
                   target="_blank"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shadow-sm transition-all">
                    <i class="fas fa-file-export text-xs"></i>
                    <span id="exportText">Export Report</span>
                </a>
            </div>
        </div>

        {{-- Filter & Control Bar --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                
                {{-- Date Filters --}}
                <form method="GET" action="{{ route('analytics.index') }}" class="flex flex-wrap items-center gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-wider ml-1">View Period</label>
                        <div class="flex items-center gap-2">
                            <select name="filter_type" onchange="this.form.submit()"
                                    class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                <option value="date"  {{ $filters['filterType'] === 'date'  ? 'selected' : '' }}>Specific Day</option>
                                <option value="month" {{ $filters['filterType'] === 'month' ? 'selected' : '' }}>Monthly View</option>
                                <option value="year"  {{ $filters['filterType'] === 'year'  ? 'selected' : '' }}>Yearly View</option>
                                <option value="all"   {{ $filters['filterType'] === 'all'   ? 'selected' : '' }}>All Time</option>
                            </select>

                            @if($filters['filterType'] === 'date')
                                <input type="date" name="date" value="{{ $filters['date'] }}" onchange="this.form.submit()"
                                       class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 px-3 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            @elseif($filters['filterType'] === 'month')
                                <input type="month" name="month" value="{{ $filters['month'] }}" onchange="this.form.submit()"
                                       class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 px-3 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            @elseif($filters['filterType'] === 'year')
                                <select name="year" onchange="this.form.submit()"
                                        class="rounded-xl border-slate-200 bg-slate-50 text-sm py-2 pl-3 pr-8 text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                    @foreach(range(now()->year, 2020) as $y)
                                        <option value="{{ $y }}" {{ $filters['year'] == $y ? 'selected' : '' }}>{{ $y }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                    <input type="hidden" name="panel" id="activePanel" value="cert">
                </form>

                {{-- Module Tabs --}}
                <div class="flex items-center p-1.5 bg-slate-100 rounded-xl self-end lg:self-center">
                    <button onclick="showPanel('cert')" id="btn-cert"
                            class="px-5 py-2 text-sm font-semibold rounded-lg transition-all bg-white text-blue-600 shadow-sm ring-1 ring-black/5">
                        <i class="fas fa-file-contract mr-2"></i>Certificates
                    </button>
                    <button onclick="showPanel('blotter')" id="btn-blotter"
                            class="px-5 py-2 text-sm font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-700">
                        <i class="fas fa-shield-alt mr-2"></i>Blotter
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
             CERTIFICATE PANEL
        ════════════════════════════════════════════ --}}
        <div id="panel-cert" class="space-y-6">
            
            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                
                {{-- Total Requests --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 flex items-center justify-center text-blue-600 text-xl">
                        <i class="fas fa-copy"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Requests</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ number_format($totalCerts) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Certificates processed</p>
                    </div>
                </div>

                {{-- Types Count --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-xl">
                        <i class="fas fa-tags"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Active Types</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $certificateStats->count() }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Unique certificate categories</p>
                    </div>
                </div>

                {{-- Completion Placeholder --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-xl">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Efficiency</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">98.5<span class="text-xl font-bold text-slate-400">%</span></h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Estimated processing rate</p>
                    </div>
                </div>
            </div>

            {{-- Main Dashboard Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Chart Card --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Distribution</h3>
                        <p class="text-sm text-slate-500">Volume by certificate type</p>
                    </div>

                    @if($certificateStats->isEmpty())
                        <div class="flex-1 flex flex-col items-center justify-center py-12 text-slate-400">
                            <i class="fas fa-chart-pie text-4xl mb-4 opacity-20"></i>
                            <p class="text-sm">No data available for this range</p>
                        </div>
                    @else
                        <div class="relative w-56 h-56 mx-auto mb-8">
                            <canvas id="certChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-black text-slate-900">{{ $totalCerts }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Total</span>
                            </div>
                        </div>
                        <div class="space-y-3 mt-auto" id="certLegend"></div>
                    @endif
                </div>

                {{-- Table Card --}}
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Certificate Breakdown</h3>
                            <p class="text-sm text-slate-500">Detailed counts and percentage share</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-50/50">
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Certificate Type</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest text-center">Requests</th>
                                    <th class="px-6 py-4 text-[11px] font-bold text-slate-400 uppercase tracking-widest">Share</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($certificateStats as $type => $count)
                                    @php
                                        $pct = $totalCerts > 0 ? round($count / $totalCerts * 100, 1) : 0;
                                        $colors = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#ef4444','#f59e0b','#10b981','#14b8a6','#06b6d4','#0ea5e9'];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $colors[$loop->index % 10] }}"></span>
                                                <span class="font-bold text-slate-700">{{ $type }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center font-black text-slate-900">{{ number_format($count) }}</td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="flex-1 bg-slate-100 rounded-full h-1.5 min-w-[100px] overflow-hidden">
                                                    <div class="h-full rounded-full" style="width: {{ $pct }}%; background: {{ $colors[$loop->index % 10] }}"></div>
                                                </div>
                                                <span class="text-xs font-bold text-slate-500 tabular-nums w-10">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-6 py-12 text-center text-slate-400">
                                            <i class="fas fa-folder-open text-2xl mb-2 opacity-20"></i>
                                            <p class="text-sm">No records found for this period</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════════
             BLOTTER PANEL
        ════════════════════════════════════════════ --}}
        <div id="panel-blotter" class="space-y-6" style="display:none">
            
            {{-- Stat Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 flex items-center justify-center text-rose-600 text-xl">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Total Cases</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ number_format($totalBlotters) }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Blotter records filed</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600 text-xl">
                        <i class="fas fa-file-medical"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Form Variants</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $blotterStats->count() }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">KP forms utilized</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 flex items-center gap-5">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center text-slate-600 text-xl">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Coverage</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ $purokStats->count() }}</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Puroks with reports</p>
                    </div>
                </div>
            </div>

            {{-- Blotter Content Grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Chart --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm flex flex-col">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900">KP Form Usage</h3>
                        <p class="text-sm text-slate-500">Distribution by form type</p>
                    </div>

                    @if($blotterStats->isEmpty())
                        <div class="flex-1 flex flex-col items-center justify-center py-12 text-slate-400">
                            <i class="fas fa-chart-pie text-4xl mb-4 opacity-20"></i>
                            <p class="text-sm">No data available</p>
                        </div>
                    @else
                        <div class="relative w-56 h-56 mx-auto mb-8">
                            <canvas id="blotterChart"></canvas>
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                                <span class="text-2xl font-black text-slate-900">{{ $totalBlotters }}</span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase">Cases</span>
                            </div>
                        </div>
                        <div class="space-y-3 mt-auto" id="blotterLegend"></div>
                    @endif
                </div>

                {{-- Hotspot Map --}}
                <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                    <div class="px-8 py-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-black text-slate-900 tracking-tight">Geospatial Hotspot Analysis</h3>
                            <p class="text-sm text-slate-500">Incident concentration by Purok and Category</p>
                        </div>
                        <div class="flex items-center gap-3 p-2 bg-slate-50 rounded-xl border border-slate-100">
                            <span class="text-[10px] font-black uppercase text-slate-400 tracking-widest ml-1">Density Scale</span>
                            <div class="flex gap-1">
                                <div class="w-4 h-4 rounded-md bg-blue-50 border border-blue-100/50" title="Low Activity"></div>
                                <div class="w-4 h-4 rounded-md bg-blue-200" title="Low-Medium"></div>
                                <div class="w-4 h-4 rounded-md bg-blue-400" title="Medium-High"></div>
                                <div class="w-4 h-4 rounded-md bg-blue-600" title="Critical/High Activity"></div>
                            </div>
                        </div>
                    </div>

                    @if($purokStats->isNotEmpty() && $allCaseSubjects->isNotEmpty())
                        @php
                            $heatMax = collect($purokStats)->map(fn($d) => $d['subjects']->values()->max() ?? 0)->max();
                        @endphp
                        <div class="overflow-x-auto p-4 lg:p-6">
                            <table class="w-full text-xs border-separate" style="border-spacing: 8px;">
                                <thead>
                                    <tr>
                                        <th class="text-left pl-4 pb-6 w-1/4">
                                            <div class="flex flex-col">
                                                <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest leading-none mb-1">Regional Scope</span>
                                                <span class="text-sm font-black text-slate-800">Purok District</span>
                                            </div>
                                        </th>
                                        @foreach($allCaseSubjects as $subject)
                                            <th class="pb-6">
                                                <div class="flex flex-col items-center gap-1 group">
                                                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-tighter group-hover:text-blue-600 transition-colors" title="{{ $subject }}">
                                                        {{ $subject }}
                                                    </span>
                                                    <div class="w-full h-0.5 rounded-full bg-slate-100 group-hover:bg-blue-200 transition-colors"></div>
                                                </div>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($purokStats as $purokName => $data)
                                        <tr class="group">
                                            <td class="pl-4 py-2 text-left font-black text-slate-700 whitespace-nowrap group-hover:text-blue-600 transition-colors">
                                                {{ $purokName }}
                                            </td>
                                            @foreach($allCaseSubjects as $subject)
                                                @php
                                                    $val = $data['subjects']->get($subject, 0);
                                                    $intensity = $heatMax > 0 ? $val / $heatMax : 0;
                                                    
                                                    // High-fidelity color scaling
                                                    if ($val === 0) {
                                                        $bgColor = "bg-slate-50/50";
                                                        $textColor = "text-slate-300";
                                                        $shadow = "";
                                                    } else {
                                                        $shadow = $intensity > 0.7 ? "shadow-md shadow-blue-200/50" : "";
                                                        if ($intensity <= 0.25) {
                                                            $bgColor = "bg-blue-50 border border-blue-100/50";
                                                            $textColor = "text-blue-600";
                                                        } elseif ($intensity <= 0.5) {
                                                            $bgColor = "bg-blue-200";
                                                            $textColor = "text-blue-800";
                                                        } elseif ($intensity <= 0.75) {
                                                            $bgColor = "bg-blue-400";
                                                            $textColor = "text-white";
                                                        } else {
                                                            $bgColor = "bg-blue-600";
                                                            $textColor = "text-white";
                                                        }
                                                    }
                                                @endphp
                                                <td class="p-0">
                                                    <div class="h-12 flex flex-col items-center justify-center rounded-2xl font-black tabular-nums transition-all hover:scale-105 hover:z-10 relative cursor-default group/cell {{ $bgColor }} {{ $textColor }} {{ $shadow }}"
                                                         title="{{ $purokName }} - {{ $subject }}: {{ $val }} cases">
                                                        <span class="text-sm">{{ $val > 0 ? $val : '-' }}</span>
                                                        @if($val > 0)
                                                            <div class="absolute bottom-1 w-1 h-1 rounded-full {{ $intensity > 0.5 ? 'bg-white/40' : 'bg-blue-400/40' }}"></div>
                                                        @endif
                                                    </div>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="flex-1 flex flex-col items-center justify-center py-20 text-slate-400">
                            <div class="w-20 h-20 rounded-3xl bg-slate-50 flex items-center justify-center mb-4">
                                <i class="fas fa-map-marked-alt text-3xl opacity-20"></i>
                            </div>
                            <p class="text-sm font-bold uppercase tracking-widest">Insufficient spatial data</p>
                            <p class="text-xs mt-1">Populate blotter records with locations to generate map</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Scripts --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const COLORS = ['#3b82f6','#6366f1','#8b5cf6','#ec4899','#ef4444','#f59e0b','#10b981','#14b8a6','#06b6d4','#0ea5e9'];

        function showPanel(name) {
            document.getElementById('panel-cert').style.display = name === 'cert' ? 'block' : 'none';
            document.getElementById('panel-blotter').style.display = name === 'blotter' ? 'block' : 'none';

            const btnCert = document.getElementById('btn-cert');
            const btnBlotter = document.getElementById('btn-blotter');

            if (name === 'cert') {
                btnCert.className = 'px-5 py-2 text-sm font-semibold rounded-lg transition-all bg-white text-blue-600 shadow-sm ring-1 ring-black/5';
                btnBlotter.className = 'px-5 py-2 text-sm font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-700';
            } else {
                btnBlotter.className = 'px-5 py-2 text-sm font-semibold rounded-lg transition-all bg-white text-blue-600 shadow-sm ring-1 ring-black/5';
                btnCert.className = 'px-5 py-2 text-sm font-semibold rounded-lg transition-all text-slate-500 hover:text-slate-700';
            }

            const exportBtn = document.getElementById('exportBtn');
            const exportText = document.getElementById('exportText');
            if (exportBtn) {
                const url = new URL(exportBtn.href);
                url.searchParams.set('panel', name);
                exportBtn.href = url.toString();
                exportText.innerText = name === 'cert' ? 'Export Certificates' : 'Export Blotter';
            }
            document.getElementById('activePanel').value = name;
        }

        let charts = {};

        function buildPie(canvasId, legendId, labels, values) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            // Destroy existing chart if it exists
            if (charts[canvasId]) {
                charts[canvasId].destroy();
            }

            charts[canvasId] = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data: values,
                        backgroundColor: COLORS.slice(0, labels.length),
                        borderWidth: 0,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '80%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            padding: 12,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            cornerRadius: 8,
                            displayColors: true
                        }
                    }
                }
            });

            const legend = document.getElementById(legendId);
            if (!legend) return;
            legend.innerHTML = ''; // Clear legend before rebuilding
            const total = values.reduce((a, b) => a + b, 0);
            
            labels.forEach((label, i) => {
                const pct = total > 0 ? Math.round(values[i] / total * 100) : 0;
                legend.innerHTML += `
                    <div class="flex items-center justify-between group">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background:${COLORS[i]}"></span>
                            <span class="text-xs font-bold text-slate-600 truncate">${label}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black text-slate-900">${values[i]}</span>
                            <span class="text-[10px] font-bold text-slate-400">${pct}%</span>
                        </div>
                    </div>`;
            });
        }

        function initDashboard() {
            const certData = @json($certificateStats);
            const blotterData = @json($blotterStats);

            buildPie('certChart', 'certLegend', Object.keys(certData), Object.values(certData));
            buildPie('blotterChart', 'blotterLegend', Object.keys(blotterData), Object.values(blotterData));
            
            // Restore panel state if needed
            const activePanel = document.getElementById('activePanel').value;
            showPanel(activePanel);
        }

        document.addEventListener('DOMContentLoaded', initDashboard);
        document.addEventListener('livewire:navigated', initDashboard);
    </script>
</x-layouts.app>