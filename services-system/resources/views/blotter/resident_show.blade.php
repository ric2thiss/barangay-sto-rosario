<x-layouts.app>

{{-- ── Page shell ────────────────────────────────────────────────── --}}
<div class="max-w-3xl mx-auto py-8 space-y-6">

    {{-- ── Top bar ──────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 flex-wrap">
                <h1 class="text-xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    {{ $blotter->form_number }}
                </h1>
                @php
                    $statusColor = match($blotter->status) {
                        'Completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200 dark:border-emerald-700',
                        'Dismissed' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 border-red-200 dark:border-red-700',
                        default     => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200 dark:border-amber-700',
                    };
                @endphp
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusColor }}">
                    {{ $blotter->status }}
                </span>
            </div>
            @if($form && isset($form['title']))
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $form['title'] }}</p>
            @endif
            <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">
                Filed {{ $blotter->created_at->format('F j, Y · g:i A') }}
                @if($blotter->incident_date)
                    · Incident date: {{ $blotter->incident_date->format('F j, Y') }}
                @endif
            </p>
        </div>

        <div class="flex gap-2 flex-wrap">
            <a href="{{ route('blotter.download-docx', $blotter) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white
                      bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                </svg>
                DOCX
            </a>

                @if($blotter->status === 'Pending')
                    <a href="{{ route('blotter.resident_edit', $blotter) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-white
                      bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 012.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12.5L9 13z"/>
                </svg>
                Edit
            </a>
                @else
                    <span class="px-4 py-2 text-sm font-medium text-zinc-400 bg-zinc-100 dark:bg-zinc-800 dark:text-zinc-500 rounded-md cursor-not-allowed"
                          title="Cannot edit — record is {{ $blotter->status }}">
                       
                Edit
       
                    </span>
                @endif

       
            <a href="{{ route('blotter.index') }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium
                      text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800
                      border border-zinc-300 dark:border-zinc-600
                      hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-lg shadow-sm transition-colors">
                ← Back
            </a>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
        <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/30
                    border border-emerald-200 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Main card ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-800 divide-y divide-zinc-100 dark:divide-zinc-800 overflow-hidden">

        {{-- Purpose --}}
        @if($blotter->purpose)
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-1">Purpose</p>
            <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $blotter->purpose }}</p>
        </div>
        @endif

        {{-- Parties --}}
        @if($blotter->complainant || $blotter->respondent)
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @if($blotter->complainant)
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">Complainant</p>
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg px-4 py-3 space-y-0.5">
                    <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ $blotter->complainant->full_name }}</p>
                    @if($blotter->complainant->address)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $blotter->complainant->address }}</p>
                    @endif
                    @if($blotter->complainant->contact_number)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $blotter->complainant->contact_number }}</p>
                    @endif
                </div>
            </div>
            @endif

            @if($blotter->respondent)
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">Respondent</p>
                <div class="bg-zinc-50 dark:bg-zinc-800 rounded-lg px-4 py-3 space-y-0.5">
                    <p class="font-medium text-sm text-zinc-900 dark:text-white">{{ $blotter->respondent->full_name }}</p>
                    @if($blotter->respondent->address)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $blotter->respondent->address }}</p>
                    @endif
                    @if($blotter->respondent->contact_number)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $blotter->respondent->contact_number }}</p>
                    @endif
                </div>
            </div>
            @endif
        </div>
        @endif

        {{-- Dynamic form fields --}}
        @if(!empty($formDataResolved))
        <div class="px-6 py-5 space-y-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Details</p>
            @php
                $labels = [];
                if ($form) {
                    foreach ($form['fields'] ?? [] as $f) {
                        $labels[$f['name']] = $f['label'] ?? $f['name'];
                    }
                }
            @endphp
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                @foreach($formDataResolved as $key => $value)
                <div class="sm:col-span-{{ Str::contains(strtolower($key), ['detail','narrative','statement','description','summary']) ? '2' : '1' }}">
                    <dt class="text-xs text-zinc-400 dark:text-zinc-500 mb-0.5">
                        {{ $labels[$key] ?? str_replace('_', ' ', ucfirst($key)) }}
                    </dt>
                    <dd class="text-sm text-zinc-800 dark:text-zinc-100 whitespace-pre-line bg-zinc-50 dark:bg-zinc-800 rounded-lg px-3 py-2">
                        {{ $value }}
                    </dd>
                </div>
                @endforeach
            </dl>
        </div>
        @endif

        {{-- Location ── FIXED: uses area_id via $locationPurok + $locationArea --}}
        @if($locationPurok)
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">Incident Location</p>
            <div class="inline-flex items-center gap-2 bg-zinc-50 dark:bg-zinc-800 rounded-lg px-4 py-2.5">
                <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
                    {{ $locationPurok->purok_name }}
                    @if($locationArea)
                        <span class="text-zinc-400 dark:text-zinc-500 font-normal mx-1">—</span>{{ $locationArea }}
                    @endif
                </span>
            </div>
        </div>
        @endif

        {{-- Evidence photos --}}
        @php
            $evidencePics = is_array($blotter->evidence_pic)
                ? $blotter->evidence_pic
                : json_decode($blotter->evidence_pic ?? '[]', true) ?? [];
        @endphp
        @if(!empty($evidencePics))
        <div class="px-6 py-5">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-3">
                Evidence Photos
                <span class="ml-1 text-zinc-300 dark:text-zinc-600 font-normal normal-case tracking-normal">({{ count($evidencePics) }})</span>
            </p>

            <div id="show-carousel">
                <div class="flex items-center gap-2">
                    <div class="flex-shrink-0 w-10">
                        @if(count($evidencePics) > 1)
                        <button type="button" id="show-prev"
                                class="w-10 h-10 rounded-full bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700
                                       text-zinc-600 dark:text-zinc-300 flex items-center justify-center text-xl shadow-sm transition-colors">‹</button>
                        @endif
                    </div>

                    <div class="relative flex-1 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-950"
                         style="height: 280px;">
                        @foreach($evidencePics as $i => $pic)
                        <a href="{{ asset('storage/' . $pic) }}" target="_blank"
                           class="show-slide absolute inset-0 transition-opacity duration-300 {{ $i === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}">
                            <img src="{{ asset('storage/' . $pic) }}"
                                 alt="Evidence {{ $i + 1 }}"
                                 class="w-full h-full object-contain">
                        </a>
                        @endforeach

                        @if(count($evidencePics) > 1)
                        <div class="absolute bottom-2.5 right-3 bg-black/60 text-white text-xs px-2.5 py-0.5 rounded-full" id="show-counter">
                            1 / {{ count($evidencePics) }}
                        </div>
                        @endif
                    </div>

                    <div class="flex-shrink-0 w-10">
                        @if(count($evidencePics) > 1)
                        <button type="button" id="show-next"
                                class="w-10 h-10 rounded-full bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-800 dark:hover:bg-zinc-700
                                       text-zinc-600 dark:text-zinc-300 flex items-center justify-center text-xl shadow-sm transition-colors">›</button>
                        @endif
                    </div>
                </div>

                @if(count($evidencePics) > 1)
                <div class="flex justify-center gap-1.5 mt-3">
                    @foreach($evidencePics as $i => $pic)
                    <button type="button"
                            class="show-dot w-2 h-2 rounded-full transition-all duration-200 {{ $i === 0 ? 'bg-indigo-500 w-4' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                            data-index="{{ $i }}"></button>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Evidence link --}}
        @if(!empty($blotter->evidence_link))
        <div class="px-6 py-4">
            <p class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 mb-2">Evidence Link</p>
            <a href="{{ $blotter->evidence_link }}" target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                {{ $blotter->evidence_link }}
            </a>
        </div>
        @endif

        {{-- Footer meta --}}
        @if($blotter->recordedBy)
        <div class="px-6 py-3 bg-zinc-50 dark:bg-zinc-800/50">
            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                Recorded by <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ $blotter->recordedBy->name }}</span>
            </p>
        </div>
        @endif

    </div>{{-- end main card --}}
</div>

<script>
(function () {
    var slides  = Array.from(document.querySelectorAll('.show-slide'));
    var dots    = Array.from(document.querySelectorAll('.show-dot'));
    var counter = document.getElementById('show-counter');
    var idx     = 0;

    if (slides.length <= 1) return;

    slides.forEach(function (s, i) {
        s.style.transition    = 'opacity .25s';
        s.style.opacity       = i === 0 ? '1' : '0';
        s.style.pointerEvents = i === 0 ? 'auto' : 'none';
    });

    function go(n) {
        n = ((n % slides.length) + slides.length) % slides.length;
        slides.forEach(function (s, i) {
            s.style.opacity       = i === n ? '1' : '0';
            s.style.pointerEvents = i === n ? 'auto' : 'none';
        });
        dots.forEach(function (d, i) {
            d.classList.toggle('bg-indigo-500', i === n);
            d.style.width = i === n ? '16px' : '';
            d.classList.toggle('bg-zinc-300',   i !== n);
            d.classList.toggle('dark:bg-zinc-600', i !== n);
        });
        if (counter) counter.textContent = (n + 1) + ' / ' + slides.length;
        idx = n;
    }

    var prev = document.getElementById('show-prev');
    var next = document.getElementById('show-next');
    if (prev) prev.addEventListener('click', function () { go(idx - 1); });
    if (next) next.addEventListener('click', function () { go(idx + 1); });
    dots.forEach(function (d) {
        d.addEventListener('click', function () { go(parseInt(this.dataset.index)); });
    });

    var carousel = document.getElementById('show-carousel');
    var tx = 0;
    if (carousel) {
        carousel.addEventListener('touchstart', function (e) { tx = e.touches[0].clientX; }, { passive: true });
        carousel.addEventListener('touchend', function (e) {
            var diff = tx - e.changedTouches[0].clientX;
            if (Math.abs(diff) > 40) go(diff > 0 ? idx + 1 : idx - 1);
        }, { passive: true });
    }
})();
</script>

</x-layouts.app>