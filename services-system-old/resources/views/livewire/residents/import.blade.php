<div class="max-w-7xl mx-auto py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold">Import Residents</h1>
        <p class="text-sm text-neutral-500 dark:text-neutral-400">Upload an Excel or CSV file to import residents. Data will be staged for review before being added to the main list.</p>
        <p class="text-sm text-blue-500 dark:text-blue-400 mt-1">Required fields: First Name, Last Name, Purok, Gender, Birth Date</p>
    </div>

    <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6 mb-8 shadow-sm">
        <form id="import-form" enctype="multipart/form-data">
            @csrf
            <div class="flex items-end gap-4">
                <div class="flex-1">
                    <label for="file" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Select File</label>
                    <input type="file" name="file" id="file" accept=".xlsx,.xls,.csv" class="block w-full text-sm text-neutral-500
                        file:mr-4 file:py-2 file:px-4
                        file:rounded-md file:border-0
                        file:text-sm file:font-semibold
                        file:bg-indigo-50 file:text-indigo-700
                        hover:file:bg-indigo-100
                        dark:file:bg-indigo-900 dark:file:text-indigo-300
                    ">
                    <div id="file-error-container" class="mt-1">
                        <span id="file-error" class="text-red-500 text-xs block hidden"></span>
                    </div>
                </div>
                <button type="button" onclick="submitImport()" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span id="upload-text">Upload & Process</span>
                    <span id="uploading-text" class="hidden">Uploading...</span>
                </button>
                <a href="{{ route('residents.download-template') }}" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Download Template
                </a>
            </div>
        </form>
        
        <script>
            function submitImport() {
                const fileInput = document.getElementById('file');
                const file = fileInput.files[0];
                const errorSpan = document.getElementById('file-error');
                const uploadText = document.getElementById('upload-text');
                const uploadingText = document.getElementById('uploading-text');
                
                // Reset error display
                errorSpan.classList.add('hidden');
                errorSpan.textContent = '';
                
                // Validate file
                if (!file) {
                    showError('Please select a file to upload.');
                    return;
                }
                
                // Check file type
                const allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel', 'text/csv'];
                if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
                    showError('Please select a valid Excel or CSV file.');
                    return;
                }
                
                // Check file size (10MB limit)
                if (file.size > 10 * 1024 * 1024) {
                    showError('File size exceeds 10MB limit.');
                    return;
                }
                
                // Show loading state
                uploadText.classList.add('hidden');
                uploadingText.classList.remove('hidden');
                
                // Create FormData
                const formData = new FormData();
                formData.append('file', file);
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                
                // Send request
                fetch('{{ route("residents.ajax-upload") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name=\"_token\"]').value
                    }
                })
                .then(async response => {
                    // Reset button state early
                    uploadText.classList.remove('hidden');
                    uploadingText.classList.add('hidden');
                    
                    let data = null;
                    const status = response.status;
                    const isJson = (response.headers.get('content-type') || '').includes('application/json');
                    if (isJson) {
                        try {
                            data = await response.json();
                        } catch (e) {
                            console.error('JSON parsing error:', e);
                        }
                    }

                    if (!response.ok) {
                        if (data && data.message) {
                            showError(data.message);
                        } else if (status === 422) {
                            showError('Invalid file. Please upload a valid Excel/CSV.');
                        } else if (status === 419) {
                            showError('Session expired. Please refresh and try again.');
                        } else {
                            showError('Server error. Please check your file and try again.');
                        }
                        return;
                    }

                    if (data && data.success) {
                        showSuccess(data.message || 'File imported successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        showError((data && data.message) || 'An error occurred during upload.');
                    }
                })
                .catch(error => {
                    // Reset button state
                    uploadText.classList.remove('hidden');
                    uploadingText.classList.add('hidden');
                    
                    // Different types of errors
                    if (error.message.includes('HTTP error')) {
                        showError('Server error. Please check your file and try again.');
                    } else if (error.message.includes('Invalid response')) {
                        showError('Server sent invalid response. Please try again.');
                    } else {
                        // Network or parsing error
                        showError('Network error or server unavailable. Please try again.');
                    }
                    
                    console.error('Upload error:', error);
                });
            }
            
            function showError(message) {
                const errorContainer = document.getElementById('file-error-container');
                const errorSpan = document.getElementById('file-error');
                
                errorSpan.textContent = message;
                errorSpan.classList.remove('hidden');
                errorSpan.className = 'text-red-500 text-xs block font-medium'; // Ensure it's visible and styled
                errorContainer.className = 'mt-1'; // Ensure container is visible
                
                console.error('Import Error:', message);
            }
            
            function showSuccess(message) {
                const errorContainer = document.getElementById('file-error-container');
                const errorSpan = document.getElementById('file-error');
                
                errorSpan.textContent = message;
                errorSpan.classList.remove('hidden');
                errorSpan.className = 'text-green-500 text-xs block font-medium'; // Green for success
                errorContainer.className = 'mt-1'; // Ensure container is visible
                
                console.log('Import Success:', message);
            }
        </script>

        @if (session()->has('message'))
            <div class="mt-4 p-4 rounded-md bg-green-50 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm">
                {{ session('message') }}
            </div>
        @endif
        
        @if (session()->has('error'))
            <div class="mt-4 p-4 rounded-md bg-red-500 dark:bg-red-900 text-white text-sm">
                {{ session('error') }}
            </div>
        @endif
    </div>

    @if($tempRecords->count() > 0)
        <div class="mb-4 flex justify-between items-center">
            <h2 class="text-lg font-medium">Staged Data</h2>
            <!-- <div class="flex items-center gap-2">
                <button type="button" wire:click="commit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700">
                    Commit All Valid
                </button>
            </div> -->
        </div>

        <div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent table-modern table-theme-blue table-force-dark">
            <table class="min-w-full text-sm dark:bg-[#0f1e2e]">
                <thead class="bg-neutral-50 dark:bg-[#15233b] dark:text-[#e5e7eb]">
                    <tr>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Name (Raw)</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Age</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Sex</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Address</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Purok</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Health</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Status</th>
                        <th class="px-4 py-2 text-left font-medium dark:text-[#e5e7eb]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tempRecords as $record)
                        @if($editingRecordId == $record->temp_id)
                            <!-- Edit Row -->
                            <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e] bg-yellow-50 dark:bg-yellow-900/20">
                                <td class="px-4 py-2 dark:text-[#e5e7eb]" colspan="8">
                                    <div class="p-4 border border-yellow-300 rounded bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-700">
                                        <h3 class="font-medium text-lg mb-3">Editing Record #{{ $record->temp_id }}</h3>
                                        
                                        <form wire:submit.prevent="saveEdit">
                                            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700">Personal Information</h2>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                                                <!-- First Name -->
                                                <div class="col-span-1">
                                                    <label for="first_name_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">First Name <span class="text-red-500">*</span></label>
                                                    <input type="text" id="first_name_{{ $record->temp_id }}" wire:model="editingData.first_name_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.first_name_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Middle Name -->
                                                <div class="col-span-1">
                                                    <label for="middle_name_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Middle Name</label>
                                                    <input type="text" id="middle_name_{{ $record->temp_id }}" wire:model="editingData.middle_name_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.middle_name_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Last Name -->
                                                <div class="col-span-1">
                                                    <label for="last_name_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Last Name <span class="text-red-500">*</span></label>
                                                    <input type="text" id="last_name_{{ $record->temp_id }}" wire:model="editingData.last_name_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.last_name_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Suffix -->
                                                <div class="col-span-1">
                                                    <label for="suffix_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Suffix</label>
                                                    <input type="text" id="suffix_{{ $record->temp_id }}" wire:model="editingData.suffix_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Jr., III">
                                                    @error('editingData.suffix_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                                <!-- Age Raw -->
                                                <div>
                                                    <label for="age_raw_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Age (Raw)</label>
                                                    <input type="text" id="age_raw_{{ $record->temp_id }}" wire:model="editingData.age_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Enter raw age value">
                                                    @error('editingData.age_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Birth Date (Used for Age Calculation) -->
                                                <div>
                                                    <label for="birth_date_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Birth Date</label>
                                                    <input type="date" id="birth_date_{{ $record->temp_id }}" wire:model="editingData.birth_date_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.birth_date_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Empty column to maintain layout -->
                                                <div>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                                <!-- Birth Place -->
                                                <div>
                                                    <label for="birth_place_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Birth Place</label>
                                                    <input type="text" id="birth_place_{{ $record->temp_id }}" wire:model="editingData.birth_place_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.birth_place_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Sex -->
                                                <div>
                                                    <label for="sex_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Sex <span class="text-red-500">*</span></label>
                                                    <select id="sex_{{ $record->temp_id }}" wire:model="editingData.sex_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Sex</option>
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                    </select>
                                                    @error('editingData.sex_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Civil Status -->
                                                <div>
                                                    <label for="civil_status_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Civil Status <span class="text-red-500">*</span></label>
                                                    <select id="civil_status_{{ $record->temp_id }}" wire:model="editingData.civil_status_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Status</option>
                                                        <option value="Single">Single</option>
                                                        <option value="Married">Married</option>
                                                        <option value="Widowed">Widowed</option>
                                                        <option value="Separated">Separated</option>
                                                        <option value="Divorced">Divorced</option>
                                                    </select>
                                                    @error('editingData.civil_status_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Relation to Head -->
                                                <div>
                                                    <label for="relation_to_head_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Relation to Head</label>
                                                    <input type="text" id="relation_to_head_{{ $record->temp_id }}" wire:model="editingData.relation_to_head_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Head, Son, Daughter, Spouse">
                                                    @error('editingData.relation_to_head_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Educational Attainment -->
                                                <div>
                                                    <label for="educational_attainment_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Educational Attainment</label>
                                                    <select id="educational_attainment_{{ $record->temp_id }}" wire:model="editingData.educational_attainment_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Education Level</option>
                                                        <option value="None">None</option>
                                                        <option value="Elementary">Elementary</option>
                                                        <option value="High School">High School</option>
                                                        <option value="Senior High">Senior High</option>
                                                        <option value="Vocational">Vocational</option>
                                                        <option value="College">College</option>
                                                    </select>
                                                    @error('editingData.educational_attainment_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Grade/Course -->
                                                <div>
                                                    <label for="grade_course_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Grade/Course</label>
                                                    <input type="text" id="grade_course_{{ $record->temp_id }}" wire:model="editingData.grade_course_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. BS Computer Science, Grade 10">
                                                    @error('editingData.grade_course_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- School -->
                                                <div>
                                                    <label for="school_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">School</label>
                                                    <input type="text" id="school_{{ $record->temp_id }}" wire:model="editingData.school_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="School name">
                                                    @error('editingData.school_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Profession/Occupation -->
                                                <div>
                                                    <label for="profession_occupation_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Profession/Occupation</label>
                                                    <input type="text" id="profession_occupation_{{ $record->temp_id }}" wire:model="editingData.profession_occupation_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Current job/profession">
                                                    @error('editingData.profession_occupation_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Employment Type -->
                                                <div>
                                                    <label for="employment_type_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Employment Type</label>
                                                    <select id="employment_type_{{ $record->temp_id }}" wire:model="editingData.employment_type_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Employment Type</option>
                                                        <option value="PRIVATE">Private</option>
                                                        <option value="GOVERNMENT">Government</option>
                                                    </select>
                                                    @error('editingData.employment_type_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700 mt-8">Address & Contact</h2>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Address -->
                                                <div class="col-span-1">
                                                    <label for="address_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">House No. / Street Address <span class="text-red-500">*</span></label>
                                                    <input type="text" id="address_{{ $record->temp_id }}" wire:model="editingData.address_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.address_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Purok -->
                                                <div class="col-span-1">
                                                    <label for="purok_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Purok <span class="text-red-500">*</span></label>
                                                    <select id="purok_{{ $record->temp_id }}" wire:model="editingData.purok_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Purok</option>
                                                        @foreach($puroks as $purok)
                                                            <option value="{{ $purok->purok_name }}">{{ $purok->purok_name }}</option>
                                                        @endforeach
                                                    </select>
                                                    @error('editingData.purok_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Contact Number -->
                                                <div>
                                                    <label for="contact_number_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Contact Number</label>
                                                    <input type="text" id="contact_number_{{ $record->temp_id }}" wire:model="editingData.contact_number_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.contact_number_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- FB/Email Address -->
                                                <div>
                                                    <label for="fb_email_address_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">FB/Email Address</label>
                                                    <input type="text" id="fb_email_address_{{ $record->temp_id }}" wire:model="editingData.fb_email_address_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Facebook name or email">
                                                    @error('editingData.fb_email_address_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Household Number -->
                                                <div>
                                                    <label for="household_number_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Household Number</label>
                                                    <input type="text" id="household_number_{{ $record->temp_id }}" wire:model="editingData.household_number_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. HH-001">
                                                    @error('editingData.household_number_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- PHIC No. -->
                                                <div>
                                                    <label for="phic_no_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PHIC No.</label>
                                                    <input type="text" id="phic_no_{{ $record->temp_id }}" wire:model="editingData.phic_no_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="PhilHealth Identification Card Number">
                                                    @error('editingData.phic_no_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Membership -->
                                                <div>
                                                    <label for="membership_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Membership</label>
                                                    <input type="text" id="membership_{{ $record->temp_id }}" wire:model="editingData.membership_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Membership details">
                                                    @error('editingData.membership_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Family Planning Method Used -->
                                                <div>
                                                    <label for="family_planning_method_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Family Planning Method Used</label>
                                                    <input type="text" id="family_planning_method_{{ $record->temp_id }}" wire:model="editingData.family_planning_method_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Contraceptives, Natural Methods">
                                                    @error('editingData.family_planning_method_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700 mt-8">Health & Social Information</h2>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Sanitary Toilet (YES/NO) -->
                                                <div>
                                                    <label for="sanitary_toilet_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Sanitary Toilet</label>
                                                    <select id="sanitary_toilet_{{ $record->temp_id }}" wire:model="editingData.sanitary_toilet_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.sanitary_toilet_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Water Supply -->
                                                <div>
                                                    <label for="water_supply_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Water Supply</label>
                                                    <select id="water_supply_{{ $record->temp_id }}" wire:model="editingData.water_supply_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Level</option>
                                                        <option value="I">I</option>
                                                        <option value="II">II</option>
                                                        <option value="III">III</option>
                                                    </select>
                                                    @error('editingData.water_supply_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Smoker (YES/NO) -->
                                                <div>
                                                    <label for="smoker_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Smoker</label>
                                                    <select id="smoker_{{ $record->temp_id }}" wire:model="editingData.smoker_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.smoker_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- Binge Drinker (YES/NO) -->
                                                <div>
                                                    <label for="binge_drinker_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Binge Drinker</label>
                                                    <select id="binge_drinker_{{ $record->temp_id }}" wire:model="editingData.binge_drinker_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.binge_drinker_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- HPN (YES/NO) -->
                                                <div>
                                                    <label for="hpn_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">HPN (Hypertension)</label>
                                                    <select id="hpn_{{ $record->temp_id }}" wire:model="editingData.hpn_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.hpn_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- DM (YES/NO) -->
                                                <div>
                                                    <label for="dm_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">DM (Diabetes Mellitus)</label>
                                                    <select id="dm_{{ $record->temp_id }}" wire:model="editingData.dm_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.dm_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- PWD (YES/NO) -->
                                                <div>
                                                    <label for="pwd_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PWD (Person with Disability)</label>
                                                    <select id="pwd_{{ $record->temp_id }}" wire:model.live="editingData.pwd_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">Select Option</option>
                                                        <option value="1">Yes</option>
                                                        <option value="0">No</option>
                                                    </select>
                                                    @error('editingData.pwd_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                                <!-- PWD Type -->
                                                @if($editingData['pwd_raw'] == 1)
                                                <div>
                                                    <label for="pwd_type_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PWD Type <span class="text-red-500">*</span></label>
                                                    <input type="text" id="pwd_type_{{ $record->temp_id }}" wire:model="editingData.pwd_type_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Visual, Hearing, Mobility">
                                                    @error('editingData.pwd_type_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>
                                                @endif
                                            </div>

                                            <div class="mb-6">
                                                <!-- Mother's Maiden Name (LAST, FIRST, MIDDLE) -->
                                                <label for="mothers_maiden_name_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Mother's Maiden Name (LAST, FIRST, MIDDLE)</label>
                                                <input type="text" id="mothers_maiden_name_{{ $record->temp_id }}" wire:model="editingData.mothers_maiden_name_raw" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Dela Cruz, Maria Santos">
                                                @error('editingData.mothers_maiden_name_raw') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                            </div>

                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                                <!-- Date Registered -->
                                                <div>
                                                    <label for="date_registered_{{ $record->temp_id }}" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Date Registered <span class="text-red-500">*</span></label>
                                                    <input type="date" id="date_registered_{{ $record->temp_id }}" wire:model="editingData.date_registered" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                    @error('editingData.date_registered') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                                </div>

                                            </div>

                                            <div class="flex justify-end pt-6 border-t border-neutral-200 dark:border-neutral-700">
                                                <button type="button" wire:click="deleteEditingRecord" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 mr-3">
                                                    Delete
                                                </button>
                                                <button type="button" wire:click="cancelEdit" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700 mr-3">
                                                    Cancel
                                                </button>
                                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    Save Changes
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @else
                            <!-- Normal Row -->
                            <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e]">
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->last_name_raw }}, {{ $record->first_name_raw }} {{ $record->middle_name_raw }}</td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->age_raw }}</td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->sex_raw }}</td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->address_raw }}</td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">{{ $record->purok_raw }}</td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">
                                    <span class="inline-flex gap-2">
                                        <span>Sanitary: {{ $record->sanitary_toilet_raw === null ? '-' : ($record->sanitary_toilet_raw ? 'Y' : 'N') }}</span>
                                        <span>Smoker: {{ $record->smoker_raw === null ? '-' : ($record->smoker_raw ? 'Y' : 'N') }}</span>
                                        <span>Binge: {{ $record->binge_drinker_raw === null ? '-' : ($record->binge_drinker_raw ? 'Y' : 'N') }}</span>
                                        <span>HPN: {{ $record->hpn_raw === null ? '-' : ($record->hpn_raw ? 'Y' : 'N') }}</span>
                                        <span>DM: {{ $record->dm_raw === null ? '-' : ($record->dm_raw ? 'Y' : 'N') }}</span>
                                        <span>PWD: {{ $record->pwd_raw === null ? '-' : ($record->pwd_raw ? 'Y' : 'N') }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                        @if($record->import_status === 'VALID') bg-green-100 text-green-800
                                        @else bg-yellow-100 text-yellow-800 @endif">
                                        {{ $record->import_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 dark:text-[#e5e7eb]">
                                    <button wire:click="editRecord({{ $record->temp_id }})" class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 mr-2">Edit</button>
                                    <flux:modal.trigger name="confirm-commit">
                                        <button type="button" class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700" x-data x-on:click.prevent="$wire.set('confirmCommitId', {{ $record->temp_id }}); $dispatch('open-modal', 'confirm-commit')">Commit</button>
                                    </flux:modal.trigger>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">
            {{ $tempRecords->links() }}
        </div>
    @endif
    <flux:modal name="confirm-commit" class="max-w-lg" closeable="false">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Are you sure you want to commit this resident?</flux:heading>
                <flux:subheading>This will add the staged data to Residents.</flux:subheading>
            </div>
            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button>Cancel</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" x-data x-on:click="$wire.commitIndividual($wire.confirmCommitId); $dispatch('close')">Confirm Commit</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
