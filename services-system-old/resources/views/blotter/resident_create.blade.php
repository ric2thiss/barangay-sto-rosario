<x-layouts.app>
    <div class="max-w-3xl mx-auto py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">New Blotter Record</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Select a KP form, then fill the required data.</p>
        </div>

      <form action="{{ route('blotter_reports.store') }}" method="POST"
              class="space-y-6" id="blotter-create-form" enctype="multipart/form-data">
            @csrf
    <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-6">
                <div>
                    <label for="form_number" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">KP Form *</label>
                    <select name="form_number" id="form_number" required
                            class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">— Select form —</option>
                        @foreach($kpForms as $f)
                            <option value="{{ $f['id'] }}" {{ old('form_number') == $f['id'] ? 'selected' : '' }}
                                    data-title="{{ $f['title'] ?? '' }}"
                                    data-purpose="{{ $f['purpose'] ?? '' }}">
                                {{ $f['label'] }} — {{ $f['title'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('form_number')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                    <div id="form-purpose" class="mt-2 p-3 rounded-md bg-zinc-50 dark:bg-zinc-800 text-sm text-zinc-600 dark:text-zinc-300 hidden"></div>
                </div>

                @foreach($kpForms as $form)
                    <div class="form-fields hidden space-y-4" data-form-id="{{ $form['id'] }}" id="fields-{{ $form['id'] }}">
                        @foreach($form['fields'] ?? [] as $field)
                            @php
                                $name  = $field['name'];
                                $label = $field['label'] ?? $name;
                                $req   = $field['required'] ?? true;
                                $type  = $field['type'] ?? 'text';
                                $max   = $field['max'] ?? null;
                            @endphp
                            <div class="field-wrap">
                                @if($type === 'resident')
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $label }}{{ $req ? ' *' : '' }}
                                    </label>
                                    {{-- AJAX resident picker --}}
                                    <div class="resident-picker mt-1" data-name="{{ $name }}">
                                        <input type="text"
                                               class="resident-search block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                               placeholder="Type to search resident…"
                                               autocomplete="off">
                                        <ul class="resident-dropdown hidden absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-lg text-sm"></ul>
                                        <input type="hidden" name="{{ $name }}"
                                               value="{{ old($name, $oldResidents->get(old($name))?->resident_id ?? '') }}">
                                        <p class="resident-selected mt-1 text-xs text-indigo-600 dark:text-indigo-400">
                                            @if(old($name) && $oldResidents->has(old($name)))
                                                {{ $oldResidents->get(old($name))->last_name }}, {{ $oldResidents->get(old($name))->first_name }}
                                            @endif
                                        </p>
                                    </div>
                                @elseif($type === 'resident_multi')
                                    @php
                                        $count = in_array($name, ['complainant_ids', 'respondent_ids']) ? ($max ?? 3) : ($max ?? 4);
                                        $oldVals = (array) old($name, []);
                                    @endphp
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $label }}{{ $req ? ' *' : '' }}{{ $max ? " (max {$max})" : '' }}
                                    </label>
                                    <div class="space-y-2 multi-resident-stack" data-name="{{ $name }}" data-count="{{ $count }}">
                                        @for($i = 0; $i < $count; $i++)
                                            @php $oldId = $oldVals[$i] ?? null; @endphp
                                            <div class="resident-picker flex gap-2 items-center" data-name="{{ $name }}[]">
                                                <div class="relative flex-1">
                                                    <input type="text"
                                                           class="resident-search block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                           placeholder="Slot {{ $i + 1 }} — type to search"
                                                           autocomplete="off"
                                                           value="{{ $oldId && $oldResidents->has($oldId) ? $oldResidents->get($oldId)->last_name.', '.$oldResidents->get($oldId)->first_name : '' }}">
                                                    <ul class="resident-dropdown hidden absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-lg text-sm"></ul>
                                                </div>
                                                <input type="hidden" name="{{ $name }}[]" value="{{ $oldId ?? '' }}">
                                                <button type="button" class="resident-clear text-zinc-400 hover:text-red-500 text-lg leading-none" title="Clear">×</button>
                                            </div>
                                        @endfor
                                    </div>
                                @elseif($type === 'date')
                                    <label for="field_{{ $form['id'] }}_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $label }}{{ $req ? ' *' : '' }}
                                    </label>
                                    <input type="date" name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" value="{{ old($name) }}"
                                           class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @elseif($type === 'textarea')
                                    <label for="field_{{ $form['id'] }}_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $label }}{{ $req ? ' *' : '' }}
                                    </label>
                                    <textarea name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" rows="3"
                                              class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old($name) }}</textarea>
                                @else
                                    <label for="field_{{ $form['id'] }}_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ $label }}{{ $req ? ' *' : '' }}
                                    </label>
                                    <input type="text" name="{{ $name }}" id="field_{{ $form['id'] }}_{{ $name }}" value="{{ old($name) }}"
                                           class="mt-1 block w-full rounded-md border-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @endif
                                @error($name)
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div {{ $isPendingOnly ? 'class=hidden' : '' }}>
                    <label for="status" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status *</label>
                    <select name="status" id="status" {{ !$isPendingOnly ? 'required' : '' }}
                            class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Pending" {{ old('status', 'Pending') == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Completed" {{ old('status') == 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Dismissed" {{ old('status') == 'Dismissed' ? 'selected' : '' }}>Dismissed</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Incident Type Section
<div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 space-y-3">
    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
        Incident Type
        <span class="text-zinc-400 dark:text-zinc-500 text-xs font-normal ml-1">(optional — select all that apply)</span>
    </h3>

    <div class="flex flex-wrap gap-2">
        @foreach($incidentTypes as $type)
            @php
                $checked = in_array($type->id, old('incident_type_ids', $selectedTypeIds ?? []));
            @endphp
            <label class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border cursor-pointer text-sm transition-colors
                          {{ $checked
                              ? 'bg-indigo-100 border-indigo-400 text-indigo-800 dark:bg-indigo-900 dark:border-indigo-600 dark:text-indigo-200'
                              : 'bg-white border-zinc-300 text-zinc-600 hover:bg-zinc-50 dark:bg-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700' }}">
                <input type="checkbox"
                       name="incident_type_ids[]"
                       value="{{ $type->id }}"
                       {{ $checked ? 'checked' : '' }}
                       class="sr-only peer"
                       onchange="this.closest('label').classList.toggle('bg-indigo-100', this.checked);
                                 this.closest('label').classList.toggle('border-indigo-400', this.checked);
                                 this.closest('label').classList.toggle('text-indigo-800', this.checked);
                                 this.closest('label').classList.toggle('dark:bg-indigo-900', this.checked);
                                 this.closest('label').classList.toggle('dark:border-indigo-600', this.checked);
                                 this.closest('label').classList.toggle('dark:text-indigo-200', this.checked);
                                 this.closest('label').classList.toggle('bg-white', !this.checked);
                                 this.closest('label').classList.toggle('border-zinc-300', !this.checked);
                                 this.closest('label').classList.toggle('text-zinc-600', !this.checked);">
                {{ $type->name }}
            </label>
        @endforeach
    </div>

    @error('incident_type_ids')
        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div> --}}
     {{-- Location Section --}}
{{-- Location Section --}}
<div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 space-y-4">
    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
        Incident Location
        <span class="text-zinc-400 dark:text-zinc-500 text-xs font-normal ml-1">(optional)</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        {{-- Purok --}}
        <div>
            <label for="purok_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                Purok
            </label>
            <select name="purok_id" id="purok_id"
                    class="block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">— Select Purok —</option>
                @foreach($puroks as $purok)
                    <option value="{{ $purok->purok_id }}"
                            data-name="{{ $purok->purok_name }}"
                            {{ old('purok_id', $existingPurok->purok_id ?? '') == $purok->purok_id ? 'selected' : '' }}>
                        {{ $purok->purok_name }}
                    </option>
                @endforeach
            </select>
            @error('purok_id')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        {{-- Specific Area (dropdown + add-new) --}}
        {{-- Specific Area (dropdown only) --}}
<div>
    <label for="area_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
        Specific Area
        <span class="text-zinc-400 text-xs">(street, sitio, landmark…)</span>
    </label>

    <div id="area-select-wrap">
        <select name="area_id" id="area_id"
                class="block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">— Select area —</option>
        </select>
    </div>

    {{-- "Add new area" toggle --}}
    <button type="button" id="toggle-new-area"
            class="mt-1.5 text-xs text-indigo-500 hover:text-indigo-700 dark:text-indigo-400 hidden underline underline-offset-2">
        
    </button>

    <div id="new-area-wrap" class="hidden mt-2 flex gap-2 items-center">
        <input type="text" name="new_area_name" id="new_area_name"
               placeholder="Type new area name…"
               class="flex-1 rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm
                      focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-sm">
        <button type="button" id="cancel-new-area"
                class="text-xs text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 px-2 py-1 rounded border
                       border-zinc-200 dark:border-zinc-700">
            Cancel
        </button>
    </div>

    @error('area_id')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
    @error('new_area_name')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror

    
</div>

    {{-- Preview pill --}}
    <p id="location-preview" class="text-xs text-indigo-600 dark:text-indigo-400 hidden">
        <svg class="inline w-3 h-3 mr-0.5 -mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
        </svg>
        <span id="location-text"></span>
    </p>
</div>

{{-- Purok → areas map (id+name pairs) passed from controller --}}
<script>
var PUROK_AREAS = @json($purokAreas);

(function () {
    var purokSel      = document.getElementById('purok_id');
    var areaSelect    = document.getElementById('area_id');
    var areaSelect    = document.getElementById('area_id');
    var preview       = document.getElementById('location-preview');
    var locText       = document.getElementById('location-text');
    var toggleBtn     = document.getElementById('toggle-new-area');
    var newAreaWrap   = document.getElementById('new-area-wrap');
    var newAreaInput  = document.getElementById('new_area_name');
    var cancelNewArea = document.getElementById('cancel-new-area');

    var OLD_AREA_ID = '{{ old('area_id', isset($existingPurok) ? ($existingPurok->pivot->area_id ?? '') : '') }}';

    function populateAreas(purokId) {
        areaSelect.innerHTML = '<option value="">— Select area —</option>';

        var areas = PUROK_AREAS[purokId] || PUROK_AREAS[String(purokId)] || [];

        areas.forEach(function (area) {
            var opt = document.createElement('option');
            opt.value = area.id;
            opt.textContent = area.name;
            if (String(area.id) === String(OLD_AREA_ID)) opt.selected = true;
            areaSelect.appendChild(opt);
        });

        // Show "Add new area" button only when a purok is selected
        toggleBtn.classList.toggle('hidden', !purokId);
    }

    function updatePreview() {
        var purokOpt  = purokSel.options[purokSel.selectedIndex];
        var purokName = purokOpt && purokOpt.value ? purokOpt.dataset.name : '';

        var areaName = '';
        if (newAreaWrap && !newAreaWrap.classList.contains('hidden') && newAreaInput.value.trim()) {
            areaName = newAreaInput.value.trim();
        } else {
            var areaOpt = areaSelect.options[areaSelect.selectedIndex];
            areaName = areaOpt && areaOpt.value ? areaOpt.textContent.trim() : '';
        }

        var parts = [purokName, areaName].filter(Boolean);
        if (parts.length) {
            locText.textContent = parts.join(' — ');
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    // Toggle "add new area" mode
    toggleBtn.addEventListener('click', function () {
        newAreaWrap.classList.remove('hidden');
        areaSelect.value = '';              // clear dropdown selection
        areaSelect.disabled = true;
        toggleBtn.classList.add('hidden');
        newAreaInput.focus();
    });

    cancelNewArea.addEventListener('click', function () {
        newAreaWrap.classList.add('hidden');
        newAreaInput.value = '';
        areaSelect.disabled = false;
        if (purokSel.value) toggleBtn.classList.remove('hidden');
        updatePreview();
    });

    newAreaInput.addEventListener('input', updatePreview);

    purokSel.addEventListener('change', function () {
        // Reset new-area state on purok change
        newAreaWrap.classList.add('hidden');
        newAreaInput.value = '';
        areaSelect.disabled = false;

        if (this.value) {
            populateAreas(this.value);
        } else {
            areaSelect.innerHTML = '<option value="">— Select area —</option>';
            toggleBtn.classList.add('hidden');
        }
        updatePreview();
    });

    areaSelect.addEventListener('change', updatePreview);

    // Init on page load (e.g. after validation error repopulation)
    if (purokSel.value) {
        populateAreas(purokSel.value);
        updatePreview();
    }
})();
</script>



                @if($isPendingOnly)
                    <input type="hidden" name="status" value="Pending">
                @endif

                {{-- Evidence Section --}}
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 space-y-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Evidence (Optional)</h3>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Photos <span class="text-zinc-400 text-xs">(max 5, JPG/PNG/WEBP, 5MB each)</span>
                        </label>
                        <input type="file" name="evidence_pics[]" id="evidence_pics"
                               accept="image/jpeg,image/png,image/webp" multiple
                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                      file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0
                                      file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700
                                      hover:file:bg-indigo-100 dark:file:bg-indigo-900 dark:file:text-indigo-300">
                        @error('evidence_pics')   <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        @error('evidence_pics.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        <p id="evidence-count-error" class="mt-1 text-sm text-red-600 hidden">Maximum 5 photos allowed.</p>
                        <div id="preview-carousel-wrap" class="mt-3 hidden">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Selected photos:</p>
                            <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800" style="height:200px;">
                                <div id="preview-slides" class="relative w-full h-full"></div>
                                <button type="button" id="preview-prev" class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white hidden items-center justify-center z-10 text-lg leading-none">‹</button>
                                <button type="button" id="preview-next" class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white hidden items-center justify-center z-10 text-lg leading-none">›</button>
                                <div id="preview-counter" class="absolute bottom-2 right-2 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full"></div>
                            </div>
                            <div id="preview-thumbs" class="flex gap-2 mt-2 overflow-x-auto pb-1"></div>
                        </div>
                    </div>
                    <div>
                        <label for="evidence_link" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Evidence Link <span class="text-zinc-400 text-xs">(Google Drive, YouTube, etc.)</span>
                        </label>
                        <input type="text" name="evidence_link" id="evidence_link"
                               value="{{ old('evidence_link') }}"
                               placeholder="https://..."
                               class="block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        @error('evidence_link') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3">
                <a href="{{ auth()->user()->hasRole('Resident') ? route('blotter.resident_index') : route('blotter.index') }}"
                   class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                    Cancel
                </a>
                <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Record
                </button>
            </div>
        </form>
    </div>

    <script>
    (function () {
       SEARCH_URL = '{{ route('blotter_reports.residents.search') }}';

        // ── Resident AJAX picker ──────────────────────────────────────
        var debounceTimers = new WeakMap();

        function initPicker(picker) {
            var input    = picker.querySelector('.resident-search');
            var dropdown = picker.querySelector('.resident-dropdown');
            var hidden   = picker.querySelector('input[type=hidden]');
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
                            dropdown.innerHTML = '<li class="px-3 py-2 text-zinc-400 text-xs">No results</li>';
                        } else {
                            data.forEach(function (item) {
                                var li = document.createElement('li');
                                li.className = 'px-3 py-2 cursor-pointer hover:bg-indigo-50 dark:hover:bg-zinc-700';
                                li.textContent = item.text;
                                li.dataset.id = item.id;
                                li.addEventListener('mousedown', function (e) {
                                    e.preventDefault();
                                    hidden.value = item.id;
                                    input.value  = item.text;
                                    if (label) { label.textContent = item.text; }
                                    closeDropdown();
                                });
                                dropdown.appendChild(li);
                            });
                        }
                        dropdown.classList.remove('hidden');
                    });
                }, 250));
            });

            input.addEventListener('blur', function () { setTimeout(closeDropdown, 150); });

            // Clear button (multi only)
            var clearBtn = picker.querySelector('.resident-clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    hidden.value = '';
                    input.value  = '';
                    closeDropdown();
                });
            }

            // Position dropdown relative to picker
            picker.style.position = 'relative';
        }

        document.querySelectorAll('.resident-picker').forEach(initPicker);

        // ── Form switcher ─────────────────────────────────────────────
        var formSelect     = document.getElementById('form_number');
        var purposeEl      = document.getElementById('form-purpose');
        var allFieldGroups = document.querySelectorAll('.form-fields');

        function escapeHtml(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        function showPurpose(opt) {
            if (!opt || !opt.value) { purposeEl.classList.add('hidden'); return; }
            var purpose = opt.dataset.purpose || '', title = opt.dataset.title || '';
            if (!purpose && !title) { purposeEl.classList.add('hidden'); return; }
            purposeEl.classList.remove('hidden');
            purposeEl.innerHTML = (title ? '<strong>' + escapeHtml(title) + '</strong><br>' : '') + escapeHtml(purpose);
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
        else { allFieldGroups.forEach(function (g) { g.classList.add('hidden'); toggleRequired(g, false); setDisabled(g, true); }); }

        // ── Submit: validate multi-resident required fields ───────────
        document.getElementById('blotter-create-form').addEventListener('submit', function (e) {
            var activeGroup = document.querySelector('.form-fields:not(.hidden)');
            if (activeGroup) {
                var valid = true;
                activeGroup.querySelectorAll('.multi-resident-stack').forEach(function (stack) {
                    var wrap  = stack.closest('.field-wrap');
                    var lab   = wrap && wrap.querySelector('label');
                    if (!lab || !lab.textContent.includes('*')) return;
                    var oldErr = wrap.querySelector('.multi-min-error');
                    if (oldErr) oldErr.remove();
                    var hasSelection = Array.from(stack.querySelectorAll('input[type=hidden]')).some(function (h) { return h.value !== ''; });
                    if (!hasSelection) {
                        valid = false;
                        var err = document.createElement('p');
                        err.className = 'mt-1 text-sm text-red-600 dark:text-red-400 multi-min-error';
                        err.textContent = 'Please select at least one ' + lab.textContent.replace('*', '').trim() + '.';
                        wrap.appendChild(err);
                    }
                });
                if (!valid) { e.preventDefault(); return; }
            }
            // Remove empty hidden inputs from multi stacks so they don't post empty values
            document.querySelectorAll('.multi-resident-stack input[type=hidden]').forEach(function (h) {
                if (!h.value) h.removeAttribute('name');
            });
        });

        // ── Evidence carousel ─────────────────────────────────────────
        var input           = document.getElementById('evidence_pics');
        var previewWrap     = document.getElementById('preview-carousel-wrap');
        var slidesContainer = document.getElementById('preview-slides');
        var thumbsContainer = document.getElementById('preview-thumbs');
        var counter         = document.getElementById('preview-counter');
        var btnPrev         = document.getElementById('preview-prev');
        var btnNext         = document.getElementById('preview-next');
        var countErr        = document.getElementById('evidence-count-error');
        var idx = 0, imgs = [], touchX = 0;

        function goTo(n) {
            imgs.forEach(function (img, i) { img.style.opacity = i === n ? '1' : '0'; });
            Array.from(thumbsContainer.children).forEach(function (t, i) {
                t.style.borderColor = i === n ? 'rgb(99,102,241)' : 'transparent';
            });
            counter.textContent = (n + 1) + ' / ' + imgs.length;
            idx = n;
        }

        if (input) {
            input.addEventListener('change', function () {
                var files = Array.from(this.files);
                slidesContainer.innerHTML = ''; thumbsContainer.innerHTML = ''; imgs = [];
                countErr.classList.add('hidden');
                if (files.length > 5) { countErr.classList.remove('hidden'); this.value = ''; previewWrap.classList.add('hidden'); return; }
                if (!files.length) { previewWrap.classList.add('hidden'); return; }
                var loaded = 0;
                files.forEach(function (file, i) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        var img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:contain;transition:opacity .25s;opacity:' + (i === 0 ? '1' : '0') + ';';
                        slidesContainer.appendChild(img); imgs.push(img);
                        var thumb = document.createElement('button'); thumb.type = 'button';
                        thumb.style.cssText = 'flex-shrink:0;width:56px;height:56px;border-radius:6px;overflow:hidden;border:2px solid ' + (i === 0 ? 'rgb(99,102,241)' : 'transparent') + ';transition:border-color .2s;';
                        thumb.dataset.index = i;
                        var ti = document.createElement('img'); ti.src = e.target.result; ti.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                        thumb.appendChild(ti);
                        thumb.addEventListener('click', function () { goTo(parseInt(this.dataset.index)); });
                        thumbsContainer.appendChild(thumb);
                        loaded++;
                        if (loaded === files.length) {
                            previewWrap.classList.remove('hidden');
                            counter.textContent = '1 / ' + files.length;
                            var showNav = files.length > 1;
                            btnPrev.style.display = showNav ? 'flex' : 'none';
                            btnNext.style.display = showNav ? 'flex' : 'none';
                            idx = 0;
                        }
                    };
                    reader.readAsDataURL(file);
                });
            });
            btnPrev.addEventListener('click', function () { goTo((idx - 1 + imgs.length) % imgs.length); });
            btnNext.addEventListener('click', function () { goTo((idx + 1) % imgs.length); });
            slidesContainer.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
            slidesContainer.addEventListener('touchend', function (e) {
                var diff = touchX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40 && imgs.length > 1) goTo(diff > 0 ? (idx + 1) % imgs.length : (idx - 1 + imgs.length) % imgs.length);
            }, { passive: true });
        }
    })();
    </script>
</x-layouts.app>
