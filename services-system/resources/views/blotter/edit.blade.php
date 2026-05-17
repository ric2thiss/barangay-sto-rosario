<x-layouts.app>
    <div class="max-w-3xl mx-auto py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Edit Blotter Record</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $blotter->form_number }}</p>
        </div>

        <form action="{{ route('blotter.update', $blotter) }}" method="POST"
              class="space-y-6" id="blotter-edit-form" enctype="multipart/form-data">
            @csrf
            @csrf
            @method('PUT')

            <input type="hidden" name="form_number" value="{{ $form['id'] ?? $blotter->form_id }}">
            {{-- Status always Pending for residents --}}
            <input type="hidden" name="status" value="Pending">

            <div class="bg-white dark:bg-zinc-900 p-6 rounded-lg border border-zinc-200 dark:border-zinc-700 space-y-6">
                @if($form)
                    @php $fd = $blotter->form_data ?? []; @endphp
                    @foreach($form['fields'] ?? [] as $field)
                        @php
                            $name  = $field['name'];
                            $label = $field['label'] ?? $name;
                            $req   = $field['required'] ?? true;
                            $type  = $field['type'] ?? 'text';
                            $max   = $field['max'] ?? null;
                            if ($name === 'complainant_id') {
                                $val = old('complainant_id', $blotter->complainant_id);
                            } elseif ($name === 'respondent_id') {
                                $val = old('respondent_id', $blotter->respondent_id);
                            } else {
                                $val = old($name, $fd[$name] ?? null);
                            }
                            $multiVal = is_array($val) ? $val : (isset($fd[$name]) && is_array($fd[$name]) ? $fd[$name] : []);
                        @endphp
                        <div class="field-wrap">
                            @if($type === 'resident')
                                @php $preloaded = $residents->get($val); @endphp
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $label }}{{ $req ? ' *' : '' }}
                                </label>
                                <div class="resident-picker mt-1" data-name="{{ $name }}">
                                    <input type="text"
                                           class="resident-search block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="Type to search resident…"
                                           autocomplete="off"
                                           value="{{ $preloaded ? $preloaded->last_name.', '.$preloaded->first_name : '' }}">
                                    <ul class="resident-dropdown hidden absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-lg text-sm"></ul>
                                    <input type="hidden" name="{{ $name }}" value="{{ $val ?? '' }}">
                                </div>

                            @elseif($type === 'resident_multi')
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $label }}{{ $req ? ' *' : '' }}{{ $max ? " (max {$max})" : '' }}
                                </label>
                                @php $count = in_array($name, ['complainant_ids', 'respondent_ids']) ? ($max ?? 3) : ($max ?? 4); @endphp
                                <div class="space-y-2 multi-resident-stack" data-name="{{ $name }}" data-count="{{ $count }}">
                                    @for($i = 0; $i < $count; $i++)
                                        @php $slotId = $multiVal[$i] ?? null; $slotRes = $slotId ? $residents->get($slotId) : null; @endphp
                                        <div class="resident-picker flex gap-2 items-center" data-name="{{ $name }}[]">
                                            <div class="relative flex-1">
                                                <input type="text"
                                                       class="resident-search block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                       placeholder="Slot {{ $i + 1 }} — type to search"
                                                       autocomplete="off"
                                                       value="{{ $slotRes ? $slotRes->last_name.', '.$slotRes->first_name : '' }}">
                                                <ul class="resident-dropdown hidden absolute z-50 mt-1 max-h-48 w-full overflow-auto rounded-md bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-lg text-sm"></ul>
                                            </div>
                                            <input type="hidden" name="{{ $name }}[]" value="{{ $slotId ?? '' }}">
                                            <button type="button" class="resident-clear text-zinc-400 hover:text-red-500 text-lg leading-none" title="Clear">×</button>
                                        </div>
                                    @endfor
                                </div>

                            @elseif($type === 'date')
                                <label for="field_edit_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $label }}{{ $req ? ' *' : '' }}
                                </label>
                                <input type="date" name="{{ $name }}" id="field_edit_{{ $name }}"
                                       value="{{ $val ? \Carbon\Carbon::parse($val)->format('Y-m-d') : '' }}"
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">

                            @elseif($type === 'textarea')
                                <label for="field_edit_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $label }}{{ $req ? ' *' : '' }}
                                </label>
                                <textarea name="{{ $name }}" id="field_edit_{{ $name }}" rows="3"
                                          class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $val }}</textarea>

                            @else
                                <label for="field_edit_{{ $name }}" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ $label }}{{ $req ? ' *' : '' }}
                                </label>
                                <input type="text" name="{{ $name }}" id="field_edit_{{ $name }}" value="{{ $val }}"
                                       class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @endif

                            @error($name)
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                @endif

      
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

        {{-- Specific Area --}}
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
    </div>

    {{-- Preview pill --}}
    <p id="location-preview" class="text-xs text-indigo-600 dark:text-indigo-400 hidden">
        <svg class="inline w-3 h-3 mr-0.5 -mt-0.5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
        </svg>
        <span id="location-text"></span>
    </p>
</div>

<script>
var PUROK_AREAS = @json($purokAreas);

(function () {
    var purokSel      = document.getElementById('purok_id');
    var areaSelect    = document.getElementById('area_id');
    var preview       = document.getElementById('location-preview');
    var locText       = document.getElementById('location-text');
    var toggleBtn     = document.getElementById('toggle-new-area');
    var newAreaWrap   = document.getElementById('new-area-wrap');
    var newAreaInput  = document.getElementById('new_area_name');
    var cancelNewArea = document.getElementById('cancel-new-area');

    // Existing area_id on the pivot (for pre-selection on edit)
    var OLD_AREA_ID = '{{ old('area_id', $existingPurok?->pivot?->area_id ?? '') }}';

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
            locText.textContent = parts.join(' — ');
            preview.classList.remove('hidden');
        } else {
            preview.classList.add('hidden');
        }
    }

    toggleBtn.addEventListener('click', function () {
        newAreaWrap.classList.remove('hidden');
        areaSelect.value = '';
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

    if (purokSel.value) {
        populateAreas(purokSel.value);
        updatePreview();
    }
})();
</script>
                {{-- Evidence Section --}}
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-5 space-y-4">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Evidence (Optional)</h3>

                    {{-- Existing Photos --}}
                  @php $existingPics = is_array($blotter->evidence_pic) ? $blotter->evidence_pic : json_decode($blotter->evidence_pic ?? '[]', true); @endphp
                   @if(!empty($existingPics))
        <div id="existing-photos-section">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">
                Current photos (<span id="existing-count">{{ count($existingPics) }}</span>) — click ✕ to remove individual photos:
            </p>

            {{-- Hidden inputs to track which photos to keep --}}
            <div id="keep-pics-inputs">
                @foreach($existingPics as $pic)
                    <input type="hidden" name="keep_pics[]" value="{{ $pic }}" data-pic="{{ $pic }}">
                @endforeach
            </div>

            {{-- Carousel --}}
            <div id="existing-carousel">
                <div class="flex items-center gap-2">
                    {{-- Prev button --}}
                    <div class="flex-shrink-0 w-11" id="existing-prev-wrap">
                        <button type="button" id="existing-prev"
                                class="w-11 h-11 rounded-full bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white flex items-center justify-center text-2xl leading-none shadow">‹</button>
                    </div>

                    {{-- Image + remove button --}}
                  <div class="relative flex-1 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-900" style="height:220px;overflow:visible;">
                       {{-- REPLACE WITH --}}
@foreach($existingPics as $i => $pic)
   <div class="existing-slide absolute inset-0 transition-opacity duration-300 {{ $i === 0 ? 'opacity-100' : 'opacity-0 pointer-events-none' }}"
     data-index="{{ $i }}" data-pic="{{ $pic }}"
     style="position:absolute;inset:0;">
          <button type="button"
                class="existing-remove absolute top-2 right-2 w-8 h-8 rounded-full bg-red-600 hover:bg-red-700 text-white flex items-center justify-center text-sm font-bold z-20 shadow"
                data-pic="{{ $pic }}" title="Remove this photo">✕</button>
        <img src="{{ asset('storage/' . $pic) }}"
             alt="Evidence {{ $i + 1 }}"
             class="w-full h-full object-contain">
        {{-- Open in new tab --}}
        <a href="{{ asset('storage/' . $pic) }}" target="_blank"
           class="absolute bottom-2 left-8 bg-black/50 hover:bg-black/70 text-white text-xs px-2 py-0.5 rounded-full z-10">
            View
        </a>
        {{-- Remove button --}}
   
    </div>
@endforeach

                        {{-- Counter --}}
                        <div class="absolute bottom-2 left-2 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full" id="existing-counter">
                            1 / {{ count($existingPics) }}
                        </div>
                    </div>

                    {{-- Next button --}}
                    <div class="flex-shrink-0 w-11" id="existing-next-wrap">
                        <button type="button" id="existing-next"
                                class="w-11 h-11 rounded-full bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-white flex items-center justify-center text-2xl leading-none shadow">›</button>
                    </div>
                </div>

                {{-- Dot indicators --}}
                <div class="flex justify-center gap-1.5 mt-2" id="existing-dots-wrap">
                    @foreach($existingPics as $i => $pic)
                        <button type="button"
                                class="existing-dot w-2.5 h-2.5 rounded-full transition-colors {{ $i === 0 ? 'bg-indigo-500' : 'bg-zinc-300 dark:bg-zinc-600' }}"
                                data-index="{{ $i }}"></button>
                    @endforeach
                </div>
            </div>

            {{-- Empty state --}}
            <div id="existing-empty" class="hidden p-4 text-center text-sm text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800 rounded-lg border border-dashed border-zinc-300 dark:border-zinc-600">
                All existing photos removed. Upload new ones below if needed.
            </div>
        </div>
    
    @endif

    {{-- New Photo Upload --}}
    <div>
      {{-- REPLACE WITH --}}
<label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
    Add Photos
    <span class="text-zinc-400 text-xs">(max 5 total, JPG/PNG/WEBP, 5MB each)</span>
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

        {{-- New upload preview carousel --}}
        <div id="preview-carousel-wrap" class="mt-3 hidden">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">New photos to upload:</p>
            <div class="relative overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-800" style="height:180px;">
                <div id="preview-slides" class="w-full h-full" style="position:relative;"></div>
                <button type="button" id="preview-prev"
                        class="absolute left-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white items-center justify-center transition-colors z-10 hidden">
                    ‹
                </button>
                <button type="button" id="preview-next"
                        class="absolute right-2 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-black/50 hover:bg-black/70 text-white items-center justify-center transition-colors z-10 hidden">
                    ›
                </button>
                <div class="absolute bottom-2 right-2 bg-black/50 text-white text-xs px-2 py-0.5 rounded-full" id="preview-counter"></div>
            </div>
            <div id="preview-thumbs" class="flex gap-2 mt-2 overflow-x-auto pb-1"></div>
        </div>
    </div>
  <div>
                    <label for="status" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300">Status *</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Pending" {{ old('status', $blotter->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                        <option value="Completed" {{ old('status', $blotter->status) == 'Completed' ? 'selected' : '' }}>Completed</option>
                        <option value="Dismissed" {{ old('status', $blotter->status) == 'Dismissed' ? 'selected' : '' }}>Dismissed</option>
                    </select>
                    @error('status')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
    {{-- Evidence Link --}}
    <div>
        <label for="evidence_link" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
            Evidence Link
            <span class="text-zinc-400 text-xs">(Google Drive, YouTube, etc.)</span>
        </label>
        <input type="text" name="evidence_link" id="evidence_link"
               value="{{ old('evidence_link', $blotter->evidence_link ?? '') }}"
               placeholder="https://..."
               class="block w-full rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        @error('evidence_link') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
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
                    Update Record
                </button>
            </div>
        </form>
    </div>

    <script>(function () {

var SEARCH_URL = '{{ route('blotter.residents.search') }}';
var debounceTimers = new WeakMap();

function initPicker(picker) {
    var input    = picker.querySelector('.resident-search');
    var dropdown = picker.querySelector('.resident-dropdown');
    var hidden   = picker.querySelector('input[type=hidden]');
    if (!input || !dropdown || !hidden) return;
    function closeDropdown() { dropdown.classList.add('hidden'); dropdown.innerHTML = ''; }
    input.addEventListener('input', function () {
        clearTimeout(debounceTimers.get(input));
        var q = this.value.trim();
        if (q.length < 1) { closeDropdown(); return; }
        debounceTimers.set(input, setTimeout(function () {
            fetch(SEARCH_URL + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
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
                        li.addEventListener('mousedown', function (e) {
                            e.preventDefault();
                            hidden.value = item.id;
                            input.value  = item.text;
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
    var clearBtn = picker.querySelector('.resident-clear');
    if (clearBtn) clearBtn.addEventListener('click', function () { hidden.value = ''; input.value = ''; closeDropdown(); });
    picker.style.position = 'relative';
}

document.querySelectorAll('.resident-picker').forEach(initPicker);

// ── Existing photos carousel with remove ─────────────────────────
var existingSlides = Array.from(document.querySelectorAll('.existing-slide'));
var existingCounter = document.getElementById('existing-counter');
var existingCountEl = document.getElementById('existing-count');
var existingIdx = 0;

function getVisibleSlides() {
    return existingSlides.filter(function (s) { return !s.classList.contains('removed'); });
}

function getKeptCount() {
    return document.querySelectorAll('#keep-pics-inputs input[name="keep_pics[]"]').length;
}

function goExisting(n) {
    var visible = getVisibleSlides();
    if (!visible.length) return;
    n = ((n % visible.length) + visible.length) % visible.length;

    existingSlides.forEach(function (s) {
        s.style.opacity = '0';
        s.style.pointerEvents = 'none';
    });
    visible[n].style.opacity = '1';
    visible[n].style.pointerEvents = 'auto';

    var visibleDots = Array.from(document.querySelectorAll('.existing-dot'))
        .filter(function (d) { return !d.classList.contains('removed'); });
    visibleDots.forEach(function (d, i) {
        d.style.backgroundColor = i === n ? 'rgb(99,102,241)' : '';
    });

    if (existingCounter) existingCounter.textContent = (n + 1) + ' / ' + visible.length;
    existingIdx = n;
}

// Init
existingSlides.forEach(function (s, i) {
    s.style.transition = 'opacity .25s';
    s.style.opacity = i === 0 ? '1' : '0';
    s.style.pointerEvents = i === 0 ? 'auto' : 'none';
});

if (existingSlides.length <= 1) {
    var pw = document.getElementById('existing-prev-wrap');
    var nw = document.getElementById('existing-next-wrap');
    var dw = document.getElementById('existing-dots-wrap');
    if (pw) pw.style.visibility = 'hidden';
    if (nw) nw.style.visibility = 'hidden';
    if (dw) dw.style.display = 'none';
}

var prevBtn = document.getElementById('existing-prev');
var nextBtn = document.getElementById('existing-next');
if (prevBtn) prevBtn.addEventListener('click', function () { goExisting(existingIdx - 1); });
if (nextBtn) nextBtn.addEventListener('click', function () { goExisting(existingIdx + 1); });

Array.from(document.querySelectorAll('.existing-dot')).forEach(function (d) {
    d.addEventListener('click', function () {
        var visibleDots = Array.from(document.querySelectorAll('.existing-dot'))
            .filter(function (x) { return !x.classList.contains('removed'); });
        var i = visibleDots.indexOf(this);
        if (i >= 0) goExisting(i);
    });
});

document.querySelectorAll('.existing-remove').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        var pic = this.dataset.pic;
        var slide = this.closest('.existing-slide');

        slide.classList.add('removed');
        slide.style.opacity = '0';
        slide.style.pointerEvents = 'none';
        slide.style.display = 'none';

        var dotIdx = parseInt(slide.dataset.index);
        var dot = document.querySelector('.existing-dot[data-index="' + dotIdx + '"]');
        if (dot) { dot.classList.add('removed'); dot.style.display = 'none'; }

        var keepInput = document.querySelector('#keep-pics-inputs input[data-pic="' + CSS.escape(pic) + '"]');
        if (keepInput) keepInput.remove();

        var remaining = getVisibleSlides().length;
        if (existingCountEl) existingCountEl.textContent = remaining;

        if (remaining === 0) {
            document.getElementById('existing-carousel').classList.add('hidden');
            document.getElementById('existing-empty').classList.remove('hidden');
        } else {
            existingIdx = Math.min(existingIdx, remaining - 1);
            goExisting(existingIdx);
            if (remaining <= 1) {
                var pw2 = document.getElementById('existing-prev-wrap');
                var nw2 = document.getElementById('existing-next-wrap');
                var dw2 = document.getElementById('existing-dots-wrap');
                if (pw2) pw2.style.visibility = 'hidden';
                if (nw2) nw2.style.visibility = 'hidden';
                if (dw2) dw2.style.display = 'none';
            }
        }

        // Re-validate combined count
        validateTotalCount();
    });
});

var existingCarousel = document.getElementById('existing-carousel');
if (existingCarousel) {
    var etx = 0;
    existingCarousel.addEventListener('touchstart', function (e) { etx = e.touches[0].clientX; }, { passive: true });
    existingCarousel.addEventListener('touchend', function (e) {
        var diff = etx - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) goExisting(diff > 0 ? existingIdx + 1 : existingIdx - 1);
    }, { passive: true });
}

// ── New upload preview carousel (ADD mode) ────────────────────────
var input           = document.getElementById('evidence_pics');
var previewWrap     = document.getElementById('preview-carousel-wrap');
var slidesContainer = document.getElementById('preview-slides');
var thumbsContainer = document.getElementById('preview-thumbs');
var previewCounter  = document.getElementById('preview-counter');
var btnPrev         = document.getElementById('preview-prev');
var btnNext         = document.getElementById('preview-next');
var countErr        = document.getElementById('evidence-count-error');
var previewIdx      = 0;
var previewFiles    = [];
var previewImgs     = [];

function validateTotalCount() {
    var total = getKeptCount() + previewFiles.length;
    if (total > 5) {
        countErr.textContent = 'Total photos cannot exceed 5 (kept: ' + getKeptCount() + ', new: ' + previewFiles.length + ').';
        countErr.classList.remove('hidden');
        return false;
    }
    countErr.classList.add('hidden');
    return true;
}

function renderPreviews() {
    slidesContainer.innerHTML = '';
    thumbsContainer.innerHTML = '';
    previewImgs = [];

    if (!previewFiles.length) {
        previewWrap.classList.add('hidden');
        return;
    }

    previewWrap.classList.remove('hidden');
    previewIdx = Math.min(previewIdx, previewFiles.length - 1);

    previewFiles.forEach(function (file, i) {
        var url = URL.createObjectURL(file);

        // Slide
        var slide = document.createElement('div');
        slide.style.cssText = 'position:absolute;inset:0;transition:opacity .25s;opacity:' + (i === 0 ? '1' : '0') + ';pointer-events:' + (i === 0 ? 'auto' : 'none') + ';';

        var img = document.createElement('img');
        img.src = url;
        img.style.cssText = 'width:100%;height:100%;object-fit:contain;background:#18181b;';
        slide.appendChild(img);

        // ✕ remove button
        var removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.title = 'Remove this photo';
        removeBtn.style.cssText = 'position:absolute;top:8px;right:8px;width:30px;height:30px;border-radius:50%;background:rgba(220,38,38,0.9);color:white;border:none;cursor:pointer;font-size:14px;font-weight:bold;display:flex;align-items:center;justify-content:center;z-index:20;box-shadow:0 1px 4px rgba(0,0,0,.4);';
        removeBtn.textContent = '✕';
        (function(capturedIndex) {
    removeBtn.addEventListener('click', function () {
        previewFiles.splice(capturedIndex, 1);
        previewIdx = Math.min(previewIdx, Math.max(0, previewFiles.length - 1));
        rebuildFileInput();
        renderPreviews();
        if (previewFiles.length) goPreview(previewIdx);
        validateTotalCount();
    });
})(i);
        slide.appendChild(removeBtn);

        // File name label
        var label = document.createElement('span');
        label.style.cssText = 'position:absolute;bottom:6px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.5);color:white;font-size:11px;padding:2px 8px;border-radius:999px;white-space:nowrap;max-width:80%;overflow:hidden;text-overflow:ellipsis;';
        label.textContent = file.name;
        slide.appendChild(label);

        slidesContainer.appendChild(slide);
        previewImgs.push(slide);

        // Thumbnail
        var thumb = document.createElement('button');
        thumb.type = 'button';
        thumb.style.cssText = 'flex-shrink:0;width:56px;height:56px;border-radius:6px;overflow:hidden;border:2px solid ' + (i === 0 ? 'rgb(99,102,241)' : 'transparent') + ';transition:border-color .2s;cursor:pointer;';
        var tImg = document.createElement('img');
        tImg.src = url;
        tImg.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        thumb.appendChild(tImg);
        thumb.addEventListener('click', (function(idx){ return function(){ goPreview(idx); }; })(i));
        thumbsContainer.appendChild(thumb);
    });

    var showNav = previewFiles.length > 1;
    btnPrev.style.display = showNav ? 'flex' : 'none';
    btnNext.style.display = showNav ? 'flex' : 'none';
    goPreview(previewIdx);
}

function goPreview(n) {
    if (!previewImgs.length) return;
    n = ((n % previewImgs.length) + previewImgs.length) % previewImgs.length;
    previewImgs.forEach(function (s, i) {
        s.style.opacity = i === n ? '1' : '0';
        s.style.pointerEvents = i === n ? 'auto' : 'none';
    });
    Array.from(thumbsContainer.children).forEach(function (t, i) {
        t.style.borderColor = i === n ? 'rgb(99,102,241)' : 'transparent';
    });
    previewCounter.textContent = (n + 1) + ' / ' + previewImgs.length;
    previewIdx = n;
}

function rebuildFileInput() {
    try {
        var dt = new DataTransfer();
        previewFiles.forEach(function (f) { dt.items.add(f); });
        input.files = dt.files;
        console.log('FileInput rebuilt, count:', input.files.length, previewFiles.map(function(f){ return f.name; }));
    } catch (e) {
        console.error('DataTransfer failed:', e);
        input.value = '';
    }
}

if (input) {
    input.addEventListener('change', function () {
        var incoming = Array.from(this.files);
        // MERGE incoming with existing previewFiles (add mode)
        incoming.forEach(function (f) {
            // Avoid exact duplicate names already in queue
            var exists = previewFiles.some(function (p) { return p.name === f.name && p.size === f.size; });
            if (!exists) previewFiles.push(f);
        });

        previewIdx = 0;
        renderPreviews();
        validateTotalCount();

        // Reset input so same file can be re-added after removal
        
    });

    if (btnPrev) btnPrev.addEventListener('click', function () { goPreview(previewIdx - 1); });
    if (btnNext) btnNext.addEventListener('click', function () { goPreview(previewIdx + 1); });

    var ptx = 0;
    slidesContainer.addEventListener('touchstart', function (e) { ptx = e.touches[0].clientX; }, { passive: true });
    slidesContainer.addEventListener('touchend', function (e) {
        var diff = ptx - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40 && previewImgs.length > 1) goPreview(diff > 0 ? previewIdx + 1 : previewIdx - 1);
    }, { passive: true });
}

// ── Block submit if total exceeds 5 ──────────────────────────────
var form = document.getElementById('blotter-edit-form');
if (form) {
    form.addEventListener('submit', function (e) {
        if (!validateTotalCount()) {
            e.preventDefault();
            countErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        // Rebuild file input right before submit to ensure files are attached
        rebuildFileInput();
        document.querySelectorAll(
            '.multi-select-stack select, select[id^="field_edit_respondent_ids_"], select[id^="field_edit_complainant_ids_"]'
        ).forEach(function (sel) {
            if (!sel.value) sel.removeAttribute('name');
        });
    });
}

})();
</script>
</x-layouts.app>