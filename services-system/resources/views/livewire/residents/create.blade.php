<div class="max-w-5xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Add New Resident</h1>
        <a href="{{ route('residents.index') }}" class="text-sm text-neutral-600 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-neutral-100">
            &larr; Back to List
        </a>
    </div>

    <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 p-6 shadow-sm">
        <form wire:submit.prevent="save">
            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700">Personal Information</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                <!-- First Name -->
                <div class="col-span-1">
                    <label for="first_name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">First Name <span class="text-red-500">*</span></label>
                    <input type="text" id="first_name" wire:model="first_name" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Middle Name -->
                <div class="col-span-1">
                    <label for="middle_name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Middle Name</label>
                    <input type="text" id="middle_name" wire:model="middle_name" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('middle_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Last Name -->
                <div class="col-span-1">
                    <label for="last_name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Last Name <span class="text-red-500">*</span></label>
                    <input type="text" id="last_name" wire:model="last_name" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Suffix -->
                <div class="col-span-1">
                    <label for="suffix" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Suffix</label>
                    <input type="text" id="suffix" wire:model="suffix" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Jr., III">
                    @error('suffix') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Birth Date -->
                <div>
                    <label for="birth_date" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Birth Date</label>
                    <input type="date" id="birth_date" wire:model="birth_date" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('birth_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Birth Place -->
                <div>
                    <label for="birth_place" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Birth Place</label>
                    <input type="text" id="birth_place" wire:model="birth_place" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('birth_place') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Sex -->
                <div>
                    <label for="sex" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Sex <span class="text-red-500">*</span></label>
                    <select id="sex" wire:model="sex" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Sex</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                    @error('sex') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Civil Status -->
                <div>
                    <label for="civil_status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Civil Status <span class="text-red-500">*</span></label>
                    <select id="civil_status" wire:model="civil_status" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Status</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                        <option value="Divorced">Divorced</option>
                    </select>
                    @error('civil_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Relation to Head -->
                <div>
                    <label for="relation_to_head" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Relation to Head</label>
                    <input type="text" id="relation_to_head" wire:model="relation_to_head" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Head, Son, Daughter, Spouse">
                    @error('relation_to_head') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Educational Attainment -->
                <div>
                    <label for="educational_attainment" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Educational Attainment</label>
                    <select id="educational_attainment" wire:model="educational_attainment" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Education Level</option>
                        <option value="None">None</option>
                        <option value="Elementary">Elementary</option>
                        <option value="High School">High School</option>
                        <option value="Senior High">Senior High</option>
                        <option value="Vocational">Vocational</option>
                        <option value="College">College</option>
                    </select>
                    @error('educational_attainment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Grade/Course -->
                <div>
                    <label for="grade_course" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Grade/Course</label>
                    <input type="text" id="grade_course" wire:model="grade_course" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. BS Computer Science, Grade 10">
                    @error('grade_course') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- School -->
                <div>
                    <label for="school" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">School</label>
                    <input type="text" id="school" wire:model="school" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="School name">
                    @error('school') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Profession/Occupation -->
                <div>
                    <label for="profession_occupation" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Profession/Occupation</label>
                    <input type="text" id="profession_occupation" wire:model="profession_occupation" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Current job/profession">
                    @error('profession_occupation') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Employment Type -->
                <div>
                    <label for="employment_type" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Employment Type</label>
                    <select id="employment_type" wire:model="employment_type" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Employment Type</option>
                        <option value="PRIVATE">Private</option>
                        <option value="GOVERNMENT">Government</option>
                    </select>
                    @error('employment_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700 mt-8">Address & Contact</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Address -->
                <div class="col-span-1">
                    <label for="address" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">House No. / Street Address</label>
                    <input type="text" id="address" wire:model="address" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Purok -->
                <div class="col-span-1">
                    <label for="purok_id" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Purok <span class="text-red-500">*</span></label>
                    <select id="purok_id" wire:model="purok_id" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Purok</option>
                        @foreach($puroks as $purok)
                            <option value="{{ $purok->purok_id }}">{{ $purok->purok_name }}</option>
                        @endforeach
                    </select>
                    @error('purok_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Contact Number -->
                <div>
                    <label for="contact_number" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Contact Number</label>
                    <input type="text" id="contact_number" wire:model="contact_number" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('contact_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- FB/Email Address -->
                <div>
                    <label for="fb_email_address" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">FB/Email Address</label>
                    <input type="text" id="fb_email_address" wire:model="fb_email_address" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Facebook name or email">
                    @error('fb_email_address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Household Number -->
                <div>
                    <label for="household_number" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Household Number</label>
                    <input type="text" id="household_number" wire:model="household_number" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. HH-001">
                    @error('household_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- PHIC No. -->
                <div>
                    <label for="phic_no" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PHIC No.</label>
                    <input type="text" id="phic_no" wire:model="phic_no" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="PhilHealth Identification Card Number">
                    @error('phic_no') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Membership -->
                <div>
                    <label for="membership" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Membership</label>
                    <input type="text" id="membership" wire:model="membership" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Membership details">
                    @error('membership') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Family Planning Method Used -->
                <div>
                    <label for="family_planning_method" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Family Planning Method Used</label>
                    <input type="text" id="family_planning_method" wire:model="family_planning_method" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Contraceptives, Natural Methods">
                    @error('family_planning_method') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <h2 class="text-lg font-medium mb-4 pb-2 border-b border-neutral-200 dark:border-neutral-700 mt-8">Health & Social Information</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Sanitary Toilet (YES/NO) -->
                <div>
                    <label for="sanitary_toilet" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Sanitary Toilet</label>
                    <select id="sanitary_toilet" wire:model="sanitary_toilet" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('sanitary_toilet') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Water Supply -->
                <div>
                    <label for="water_supply" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Water Supply</label>
                    <select id="water_supply" wire:model="water_supply" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Level</option>
                        <option value="I">I</option>
                        <option value="II">II</option>
                        <option value="III">III</option>
                    </select>
                    @error('water_supply') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Smoker (YES/NO) -->
                <div>
                    <label for="smoker" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Smoker</label>
                    <select id="smoker" wire:model="smoker" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('smoker') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Binge Drinker (YES/NO) -->
                <div>
                    <label for="binge_drinker" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Binge Drinker</label>
                    <select id="binge_drinker" wire:model="binge_drinker" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('binge_drinker') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- HPN (YES/NO) -->
                <div>
                    <label for="hpn" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">HPN (Hypertension)</label>
                    <select id="hpn" wire:model="hpn" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('hpn') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- DM (YES/NO) -->
                <div>
                    <label for="dm" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">DM (Diabetes Mellitus)</label>
                    <select id="dm" wire:model="dm" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('dm') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- PWD (YES/NO) -->
                <div>
                    <label for="pwd" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PWD (Person with Disability)</label>
                    <select id="pwd" wire:model.live="pwd" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Select Option</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                    @error('pwd') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- PWD Type (Conditional) -->
                @if($pwd == 1)
                <div>
                    <label for="pwd_type" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">PWD Type <span class="text-red-500">*</span></label>
                    <input type="text" id="pwd_type" wire:model="pwd_type" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Visual, Hearing, Mobility">
                    @error('pwd_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                @endif
            </div>

            <div class="mb-6">
                <!-- Mother's Maiden Name (LAST, FIRST, MIDDLE) -->
                <label for="mothers_maiden_name" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Mother's Maiden Name (LAST, FIRST, MIDDLE)</label>
                <input type="text" id="mothers_maiden_name" wire:model="mothers_maiden_name" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Dela Cruz, Maria Santos">
                @error('mothers_maiden_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Date Registered -->
                <div>
                    <label for="date_registered" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Date Registered <span class="text-red-500">*</span></label>
                    <input type="date" id="date_registered" wire:model="date_registered" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('date_registered') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <!-- Residency Status -->
                <div>
                    <label for="residency_status" class="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">Residency Status <span class="text-red-500">*</span></label>
                    <select id="residency_status" wire:model="residency_status" class="w-full rounded-md border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="Active">Active</option>
                        <option value="Deceased">Deceased</option>
                        <option value="Moved Out">Moved Out</option>
                    </select>
                    @error('residency_status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

            </div>

            <div class="flex justify-end pt-6 border-t border-neutral-200 dark:border-neutral-700">
                <a href="{{ route('residents.index') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700 mr-3">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Save Resident
                </button>
            </div>
        </form>
    </div>
</div>