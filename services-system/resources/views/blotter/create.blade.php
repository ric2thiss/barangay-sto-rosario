<x-layouts.app>
    <div class="max-w-5xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        
        {{-- Page Header --}}
        <div class="mb-10 text-center">
            <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Register New Blotter Record</h1>
            <p class="text-slate-500 mt-2">Official documentation of community incidents and dispute proceedings</p>
        </div>

        <form action="{{ route('blotter.store') }}" method="POST" id="blotter-create-form" enctype="multipart/form-data" class="space-y-10">
            @csrf

            {{-- KP Form Selection Card --}}
            <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden">
                <div class="p-8 lg:p-10 space-y-8">
                    
                    <div class="flex flex-col lg:flex-row gap-10">
                        {{-- Form Selector --}}
                        <div class="flex-1 space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-rose-50 flex items-center justify-center text-rose-600 text-sm">
                                    <i class="fas fa-balance-scale"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">KP Form Selection</h3>
                            </div>
                            
                            <div class="relative">
                                <select name="form_number" id="form_number" required
                                        class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all appearance-none cursor-pointer">
                                    <option value="">— Select Official KP Form —</option>
                                    @foreach($kpForms as $f)
                                        <option value="{{ $f['id'] }}" {{ old('form_number') == $f['id'] ? 'selected' : '' }}
                                                data-title="{{ $f['title'] ?? '' }}"
                                                data-purpose="{{ $f['purpose'] ?? '' }}">
                                            {{ $f['label'] }} — {{ $f['title'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-slate-400">
                                    <i class="fas fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                            @error('form_number') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror

                            <div id="form-purpose" class="p-4 rounded-2xl bg-slate-50 border border-slate-100 text-sm text-slate-600 hidden animate-in fade-in duration-300"></div>
                        </div>

                        {{-- Case Metadata --}}
                        <div class="w-full lg:w-72 space-y-4">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-600 text-sm">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Case Info</h3>
                            </div>

                            <div {{ $isPendingOnly ? 'class=hidden' : '' }}>
                                <label for="status" class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Record Status</label>
                                <select name="status" id="status" {{ !$isPendingOnly ? 'required' : '' }}
                                        class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-rose-500 focus:border-rose-500 transition-all">
                                    <option value="Pending" {{ old('status', 'Pending') == 'Pending' ? 'selected' : '' }}>Pending Review</option>
                                    <option value="Completed" {{ old('status') == 'Completed' ? 'selected' : '' }}>Completed/Settled</option>
                                    <option value="Dismissed" {{ old('status') == 'Dismissed' ? 'selected' : '' }}>Dismissed</option>
                                </select>
                                @error('status') <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                            </div>
                            @if($isPendingOnly)
                                <input type="hidden" name="status" value="Pending">
                                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-100 flex items-center gap-3">
                                    <i class="fas fa-clock text-amber-500 text-sm"></i>
                                    <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Submitted as Pending Review</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Dynamic Form Fields Card --}}
            <div id="dynamic-fields-card" class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 overflow-hidden hidden">
                <div class="p-8 lg:p-10">
                    <div class="flex items-center gap-2 mb-8">
                        <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600 text-sm">
                            <i class="fas fa-edit"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Incident Details & Parties</h3>
                    </div>

                    @foreach($kpForms as $form)
                        <div class="form-fields hidden space-y-8" data-form-id="{{ $form['id'] }}" id="fields-{{ $form['id'] }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach($form['fields'] ?? [] as $field)
                                    @php
                                        $name  = $field['name'];
                                        $label = $field['label'] ?? $name;
                                        $req   = $field['required'] ?? true;
                                        $type  = $field['type'] ?? 'text';
                                        $max   = $field['max'] ?? null;
                                        $isFullWidth = in_array($type, ['textarea', 'resident_multi']);
                                    @endphp
                                    
                                    <div class="field-wrap {{ $isFullWidth ? 'md:col-span-2' : '' }} space-y-2">
                                        <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 block">
                                            {{ $label }}{{ $req ? ' *' : '' }}
                                        </label>

                                        @if($type === 'resident')
                                            {{-- AJAX resident picker --}}
                                            <div class="resident-picker relative" data-name="{{ $name }}">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                                                    <i class="fas fa-user-search text-xs"></i>
                                                </div>
                                                <input type="text"
                                                       class="resident-search block w-full pl-10 pr-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                       placeholder="Search for resident..."
                                                       autocomplete="off">
                                                <ul class="resident-dropdown hidden absolute z-50 mt-2 max-h-60 w-full overflow-auto rounded-2xl bg-white border border-slate-100 shadow-2xl text-sm divide-y divide-slate-50"></ul>
                                                <input type="hidden" name="{{ $name }}"
                                                       value="{{ old($name, $oldResidents->get(old($name))?->resident_id ?? '') }}">
                                                <div class="resident-selected-wrap mt-2 hidden">
                                                    <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-700 rounded-xl text-[10px] font-bold border border-blue-100">
                                                        <i class="fas fa-check-circle"></i>
                                                        <span class="resident-selected">
                                                            @if(old($name) && $oldResidents->has(old($name)))
                                                                {{ $oldResidents->get(old($name))->last_name }}, {{ $oldResidents->get(old($name))->first_name }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                        @elseif($type === 'resident_multi')
                                            @php
                                                $count = in_array($name, ['complainant_ids', 'respondent_ids']) ? ($max ?? 3) : ($max ?? 4);
                                                $oldVals = (array) old($name, []);
                                            @endphp
                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 multi-resident-stack" data-name="{{ $name }}" data-count="{{ $count }}">
                                                @for($i = 0; $i < $count; $i++)
                                                    @php $oldId = $oldVals[$i] ?? null; @endphp
                                                    <div class="resident-picker relative group" data-name="{{ $name }}[]">
                                                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-300 group-focus-within:text-blue-500 transition-colors">
                                                            <i class="fas fa-user text-xs"></i>
                                                        </div>
                                                        <input type="text"
                                                               class="resident-search block w-full pl-9 pr-8 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm placeholder-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                               placeholder="Search Slot {{ $i + 1 }}..."
                                                               autocomplete="off"
                                                               value="{{ $oldId && $oldResidents->has($oldId) ? $oldResidents->get($oldId)->last_name.', '.$oldResidents->get($oldId)->first_name : '' }}">
                                                        <ul class="resident-dropdown hidden absolute z-50 mt-2 max-h-48 w-full overflow-auto rounded-xl bg-white border border-slate-100 shadow-2xl text-xs divide-y divide-slate-50"></ul>
                                                        <input type="hidden" name="{{ $name }}[]" value="{{ $oldId ?? '' }}">
                                                        <button type="button" class="resident-clear absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 hover:text-rose-500 transition-colors" title="Clear">
                                                            <i class="fas fa-times-circle"></i>
                                                        </button>
                                                    </div>
                                                @endfor
                                            </div>

                                        @elseif($type === 'date')
                                            <input type="date" name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" value="{{ old($name) }}"
                                                   class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        
                                        @elseif($type === 'textarea')
                                            <textarea name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" rows="4"
                                                      placeholder="Provide detailed description of the incident..."
                                                      class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm placeholder-slate-400 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">{{ old($name) }}</textarea>
                                        
                                        @else
                                            <input type="text" name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" value="{{ old($name) }}"
                                                   class="block w-full px-4 py-3 bg-slate-50 border-slate-200 rounded-2xl text-sm placeholder-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                                        @endif
                                        
                                        @error($name) <p class="text-rose-500 text-[10px] font-bold uppercase mt-1 ml-1">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Location & Evidence Card Footer-Style Sections --}}
                <div class="bg-slate-50/50 border-t border-slate-100 p-8 lg:p-10 space-y-10">
                    
                    {{-- Location Section --}}
                    <div class="space-y-6">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center text-emerald-600 text-sm">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Incident Location</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="purok_id" class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Purok / District</label>
                                <select name="purok_id" id="purok_id"
                                        class="block w-full px-4 py-3 bg-white border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                                    <option value="">— Select Purok —</option>
                                    @foreach($puroks as $purok)
                                        <option value="{{ $purok->purok_id }}"
                                                data-name="{{ $purok->purok_name }}"
                                                {{ old('purok_id', $existingPurok->purok_id ?? '') == $purok->purok_id ? 'selected' : '' }}>
                                            {{ $purok->purok_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="area_id" class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 mb-1.5 block">Specific Area / Landmark</label>
                                <div id="area-select-wrap">
                                    <select name="area_id" id="area_id"
                                            class="block w-full px-4 py-3 bg-white border-slate-200 rounded-2xl text-sm text-slate-700 focus:ring-2 focus:ring-emerald-500 transition-all">
                                        <option value="">— Select Area —</option>
                                    </select>
                                </div>
                                <div id="new-area-wrap" class="hidden mt-2 animate-in slide-in-from-top-2 duration-200">
                                    <div class="relative">
                                        <input type="text" name="new_area_name" id="new_area_name"
                                               placeholder="Type new area name..."
                                               class="block w-full px-4 py-3 bg-white border-emerald-200 rounded-2xl text-sm focus:ring-2 focus:ring-emerald-500 transition-all">
                                        <button type="button" id="cancel-new-area" class="absolute right-3 top-1/2 -translate-y-1/2 text-xs font-bold text-slate-400 hover:text-slate-600">Cancel</button>
                                    </div>
                                </div>
                                <button type="button" id="toggle-new-area" class="mt-2 text-[10px] font-bold text-emerald-600 uppercase tracking-widest hover:text-emerald-700 hidden">
                                    + Add New Location
                                </button>
                            </div>
                        </div>

                        <div id="location-preview" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-100 inline-flex items-center gap-2">
                            <i class="fas fa-compass text-emerald-500 text-xs"></i>
                            <span id="location-text" class="text-[10px] font-black text-emerald-700 uppercase tracking-wider"></span>
                        </div>
                    </div>

                    {{-- Evidence Section --}}
                    <div class="space-y-6 pt-10 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center text-amber-600 text-sm">
                                <i class="fas fa-camera"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Supporting Evidence</h3>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-4">
                                <label class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 block">Photo Attachments (Max 5)</label>
                                <div class="relative">
                                    <input type="file" name="evidence_pics[]" id="evidence_pics"
                                           accept="image/jpeg,image/png,image/webp" multiple
                                           class="block w-full text-xs text-slate-500
                                                  file:mr-4 file:py-2.5 file:px-6 file:rounded-xl file:border-0
                                                  file:text-[10px] file:font-black file:uppercase file:tracking-widest
                                                  file:bg-amber-600 file:text-white hover:file:bg-amber-700 transition-all">
                                </div>
                                @error('evidence_pics') <p class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</p> @enderror
                                <p id="evidence-count-error" class="text-[10px] font-bold text-rose-500 uppercase hidden">Maximum 5 photos exceeded</p>
                                
                                <div id="preview-carousel-wrap" class="hidden space-y-4 animate-in fade-in zoom-in-95 duration-300">
                                    <div class="relative aspect-video rounded-3xl overflow-hidden border-4 border-white shadow-xl bg-slate-900 group">
                                        <div id="preview-slides" class="w-full h-full relative"></div>
                                        <div id="preview-counter" class="absolute top-4 right-4 bg-black/60 text-white text-[10px] font-black px-3 py-1 rounded-full backdrop-blur-sm"></div>
                                        <button type="button" id="preview-prev" class="absolute left-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white backdrop-blur-md hidden items-center justify-center transition-all">
                                            <i class="fas fa-chevron-left"></i>
                                        </button>
                                        <button type="button" id="preview-next" class="absolute right-4 top-1/2 -translate-y-1/2 w-10 h-10 rounded-full bg-white/20 hover:bg-white/40 text-white backdrop-blur-md hidden items-center justify-center transition-all">
                                            <i class="fas fa-chevron-right"></i>
                                        </button>
                                    </div>
                                    <div id="preview-thumbs" class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide"></div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <label for="evidence_link" class="text-[11px] font-bold text-slate-400 uppercase tracking-widest ml-1 block">Digital Evidence Link</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-300">
                                        <i class="fas fa-link text-xs"></i>
                                    </div>
                                    <input type="text" name="evidence_link" id="evidence_link" value="{{ old('evidence_link') }}"
                                           placeholder="https://drive.google.com/..."
                                           class="block w-full pl-10 pr-4 py-3 bg-white border-slate-200 rounded-2xl text-sm placeholder-slate-300 focus:ring-2 focus:ring-amber-500 transition-all">
                                </div>
                                @error('evidence_link') <p class="text-rose-500 text-[10px] font-bold uppercase">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Form Actions --}}
                <div class="px-8 py-6 bg-slate-100/50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <a href="{{ auth()->user()->hasRole('Resident') ? route('blotter.resident_index') : route('blotter.index') }}"
                       class="px-6 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-800 transition-all uppercase tracking-widest">
                        Cancel
                    </a>
                    <button type="submit" class="px-10 py-3 bg-slate-900 hover:bg-black text-white rounded-2xl text-xs font-black shadow-xl transition-all uppercase tracking-widest">
                        Commit Record
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var SEARCH_URL = '{{ route('blotter.residents.search') }}';
        var PUROK_AREAS = @json($purokAreas);

        // ── Resident AJAX picker ──────────────────────────────────────
        var debounceTimers = new WeakMap();

        function initPicker(picker) {
            var input    = picker.querySelector('.resident-search');
            var dropdown = picker.querySelector('.resident-dropdown');
            var hidden   = picker.querySelector('input[type=hidden]');
            var wrap     = picker.querySelector('.resident-selected-wrap');
            var label    = picker.querySelector('.resident-selected');

            if (!input || !dropdown || !hidden) return;

            function closeDropdown() { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; }

            input.addEventListener('input', function () {
                clearTimeout(debounceTimers.get(input));
                var q = this.value.trim();
                if (q.length < 1) { closeDropdown(); return; }
                debounceTimers.set(input, setTimeout(function () {
                    fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        dropdown.innerHTML = '';
                        if (!data.length) {
                            dropdown.innerHTML = '<li class="px-4 py-3 text-slate-400 text-xs font-bold uppercase">No matching residents</li>';
                        } else {
                            data.forEach(function (item) {
                                var li = document.createElement('li');
                                li.className = 'px-4 py-3 cursor-pointer hover:bg-blue-50 transition-colors text-sm font-bold text-slate-700';
                                li.textContent = item.text;
                                li.dataset.id = item.id;
                                li.addEventListener('mousedown', function (e) {
                                    e.preventDefault();
                                    hidden.value = item.id;
                                    input.value  = item.text;
                                    if (label) { 
                                        label.textContent = item.text; 
                                        if(wrap) wrap.classList.remove('hidden');
                                    }
                                    closeDropdown();
                                });
                                dropdown.appendChild(li);
                            });
                        }
                        dropdown.classList.remove('hidden');
                    });
                }, 300));
            });

            input.addEventListener('blur', function () { setTimeout(closeDropdown, 150); });

            var clearBtn = picker.querySelector('.resident-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    hidden.value = '';
                    input.value  = '';
                    if(wrap) wrap.classList.add('hidden');
                    closeDropdown();
                });
            }
        }

        document.querySelectorAll('.resident-picker').forEach(initPicker);

        // ── Form switcher ─────────────────────────────────────────────
        var formSelect     = document.getElementById('form_number');
        var purposeEl      = document.getElementById('form-purpose');
        var allFieldGroups = document.querySelectorAll('.form-fields');
        var fieldsCard     = document.getElementById('dynamic-fields-card');

        function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        function showPurpose(opt) {
            if (!opt || !opt.value) { purposeEl.classList.add('hidden'); return; }
            var purpose = opt.dataset.purpose || '', title = opt.dataset.title || '';
            if (!purpose && !title) { purposeEl.classList.add('hidden'); return; }
            purposeEl.classList.remove('hidden');
            purposeEl.innerHTML = (title ? '<div class="text-[10px] font-black uppercase text-rose-500 mb-1">' + escapeHtml(title) + '</div>' : '') + 
                                  '<div class="text-xs font-medium leading-relaxed italic text-slate-500">' + escapeHtml(purpose) + '</div>';
        }

        function toggleRequired(container, required) {
            container.querySelectorAll('select[name], input[name], textarea[name]').forEach(function (el) {
                if (el.type === 'hidden') return;
                var wrap = el.closest('.field-wrap');
                var lab  = wrap && wrap.querySelector('label');
                if (lab && lab.textContent.includes('*')) el.required = required;
            });
        }

        function setDisabled(container, disabled) {
            container.querySelectorAll('select, input, textarea').forEach(function (el) { el.disabled = disabled; });
        }

        function switchForm(id) {
            if(!id) { fieldsCard.classList.add('hidden'); return; }
            fieldsCard.classList.remove('hidden');
            allFieldGroups.forEach(function (g) {
                var active = g.dataset.formId === id;
                g.classList.toggle('hidden', !active);
                toggleRequired(g, active);
                setDisabled(g, !active);
            });
        }

        formSelect.addEventListener('change', function () {
            showPurpose(this.options[this.selectedIndex]);
            switchForm(this.value);
        });

        var initialId = formSelect.value;
        if (initialId) { showPurpose(formSelect.options[formSelect.selectedIndex]); switchForm(initialId); }

        // ── Location Logic ─────────────────────────────────────────────
        (function () {
            var purokSel      = document.getElementById('purok_id');
            var areaSelect    = document.getElementById('area_id');
            var preview       = document.getElementById('location-preview');
            var locText       = document.getElementById('location-text');
            var toggleBtn     = document.getElementById('toggle-new-area');
            var newAreaWrap   = document.getElementById('new-area-wrap');
            var newAreaInput  = document.getElementById('new_area_name');
            var cancelNewArea = document.getElementById('cancel-new-area');
            var OLD_AREA_ID   = '{{ old('area_id', isset($existingPurok) ? ($existingPurok->pivot->area_id ?? '') : '') }}';

            function populateAreas(purokId) {
                areaSelect.innerHTML = '<option value="">— Select Area —</option>';
                var areas = PUROK_AREAS[purokId] || PUROK_AREAS[String(purokId)] || [];
                areas.forEach(function (area) {
                    var opt = document.createElement('option');
                    opt.value = area.id;
                    opt.textContent = area.name;
                    if (String(area.id) === String(OLD_AREA_ID)) opt.selected = true;
                    areaSelect.appendChild(opt);
                });
                toggleBtn.classList.toggle('hidden', !purokId);
            }

            function updatePreview() {
                var purokOpt  = purokSel.options[purokSel.selectedIndex];
                var purokName = purokOpt && purokOpt.value ? purokOpt.dataset.name : '';
                var areaName  = '';
                if (newAreaWrap && !newAreaWrap.classList.contains('hidden') && newAreaInput.value.trim()) {
                    areaName = newAreaInput.value.trim();
                } else {
                    var areaOpt = areaSelect.options[areaSelect.selectedIndex];
                    areaName = areaOpt && areaOpt.value ? areaOpt.textContent.trim() : '';
                }
                var parts = [purokName, areaName].filter(Boolean);
                if (parts.length) {
                    locText.textContent = parts.join(' • ');
                    preview.classList.remove('hidden');
                } else {
                    preview.classList.add('hidden');
                }
            }

            toggleBtn.addEventListener('click', function () {
                newAreaWrap.classList.remove('hidden');
                areaSelect.value = ''; areaSelect.disabled = true;
                toggleBtn.classList.add('hidden'); newAreaInput.focus();
            });

            cancelNewArea.addEventListener('click', function () {
                newAreaWrap.classList.add('hidden');
                newAreaInput.value = ''; areaSelect.disabled = false;
                if (purokSel.value) toggleBtn.classList.remove('hidden');
                updatePreview();
            });

            newAreaInput.addEventListener('input', updatePreview);
            purokSel.addEventListener('change', function () {
                newAreaWrap.classList.add('hidden'); newAreaInput.value = ''; areaSelect.disabled = false;
                if (this.value) populateAreas(this.value);
                else { areaSelect.innerHTML = '<option value="">— Select Area —</option>'; toggleBtn.classList.add('hidden'); }
                updatePreview();
            });
            areaSelect.addEventListener('change', updatePreview);
            if (purokSel.value) { populateAreas(purokSel.value); updatePreview(); }
        })();

        // ── Evidence carousel ─────────────────────────────────────────
        var eInput          = document.getElementById('evidence_pics');
        var ePreviewWrap    = document.getElementById('preview-carousel-wrap');
        var eSlides         = document.getElementById('preview-slides');
        var eThumbs         = document.getElementById('preview-thumbs');
        var eCounter        = document.getElementById('preview-counter');
        var ePrev           = document.getElementById('preview-prev');
        var eNext           = document.getElementById('preview-next');
        var eIdx = 0, eImgs = [];

        function eGoTo(n) {
            eImgs.forEach(function (img, i) { img.style.opacity = i === n ? '1' : '0'; });
            Array.from(eThumbs.children).forEach(function (t, i) {
                t.style.borderColor = i === n ? 'rgb(59,130,246)' : 'transparent';
                t.style.opacity = i === n ? '1' : '0.5';
            });
            eCounter.textContent = (n + 1) + ' / ' + eImgs.length;
            eIdx = n;
        }

        if (eInput) {
            eInput.addEventListener('change', function () {
                var files = Array.from(this.files);
                eSlides.innerHTML = ''; eThumbs.innerHTML = ''; eImgs = [];
                document.getElementById('evidence-count-error').classList.add('hidden');
                if (files.length > 5) { document.getElementById('evidence-count-error').classList.remove('hidden'); this.value = ''; ePreviewWrap.classList.add('hidden'); return; }
                if (!files.length) { ePreviewWrap.classList.add('hidden'); return; }
                files.forEach(function (file, i) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:contain;transition:opacity .4s ease;opacity:' + (i === 0 ? '1' : '0') + ';';
                        eSlides.appendChild(img); eImgs.push(img);
                        var thumb = document.createElement('button'); thumb.type = 'button';
                        thumb.className = 'w-16 h-12 rounded-xl overflow-hidden border-2 transition-all ' + (i === 0 ? 'border-blue-500 opacity-100' : 'border-transparent opacity-50');
                        thumb.innerHTML = '<img src="' + e.target.result + '" class="w-full h-full object-cover">';
                        thumb.onclick = function() { eGoTo(i); };
                        eThumbs.appendChild(thumb);
                        if (eImgs.length === files.length) {
                            ePreviewWrap.classList.remove('hidden');
                            eCounter.textContent = '1 / ' + files.length;
                            ePrev.style.display = files.length > 1 ? 'flex' : 'none';
                            eNext.style.display = files.length > 1 ? 'flex' : 'none';
                        }
                    };
                    reader.readAsDataURL(file);
                });
            });
            ePrev.onclick = function() { eGoTo((eIdx - 1 + eImgs.length) % eImgs.length); };
            eNext.onclick = function() { eGoTo((eIdx + 1) % eImgs.length); };
        }
    })();
    </script>
</x-layouts.app>
