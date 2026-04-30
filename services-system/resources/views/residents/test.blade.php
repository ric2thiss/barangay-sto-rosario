<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('storage/logos/logo_left.jpg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-gray-50 text-gray-800 font-sans">

<div class="max-w-6xl mx-auto px-6 py-8">

    {{-- Page Header --}}
    <div class="flex items-center gap-3 mb-8">
        <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Residents</h1>
            <p class="text-sm text-gray-500">Dual database preview — new &amp; old records</p>
        </div>
    </div>

    {{-- ✅ NEW DB: profiling-system --}}
    <div class="bg-white border border-gray-200 rounded-xl mb-6 overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">NEW</span>
            <span class="text-sm font-medium text-gray-800">profiling-system</span>
            <span class="ml-auto text-xs text-gray-400">{{ $newResidents->count() }} records shown</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">ID</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Resident</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Purok</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Barangay</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Sex</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Voter</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($newResidents as $r)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ str_pad($r->id, 3, '0', STR_PAD_LEFT) }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-medium flex-shrink-0">
                                {{ strtoupper(substr($r->first_name, 0, 1) . substr($r->surname, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $r->full_name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->purok ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $r->barangay }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->sex }}</td>
                    <td class="px-5 py-3">
                        @if($r->voters_status === 'Yes')
                            <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">Yes</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-400">No</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-8 text-center text-sm text-gray-400">No records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- 🕰 OLD DB: baranggay --}}
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <div class="flex items-center gap-3 px-5 py-3 border-b border-gray-100">
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-700">OLD</span>
            <span class="text-sm font-medium text-gray-800">baranggay</span>
            <span class="ml-auto text-xs text-gray-400">{{ $oldResidents->count() }} records shown</span>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left">
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">ID</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Resident</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Address</th>
                    <th class="px-5 py-2.5 text-xs font-medium uppercase tracking-wide text-gray-400">Civil status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($oldResidents as $r)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-5 py-3">
                        <span class="text-xs font-mono text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full">
                            {{ $r->resident_id }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-medium flex-shrink-0">
                                {{ strtoupper(substr($r->first_name, 0, 1) . substr($r->last_name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-gray-900">{{ $r->full_name }}</span>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->address ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $r->civil_status ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="px-5 py-8 text-center text-sm text-gray-400">No records found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>

</body>
</html>