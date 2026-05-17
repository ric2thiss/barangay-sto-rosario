<div class="max-w-5xl mx-auto py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold">Resident Details</h1>
        <div>
            <a href="{{ route('residents.index') }}" class="px-4 py-2 text-sm font-medium text-neutral-700 bg-white border border-neutral-300 rounded-md hover:bg-neutral-50 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-600 dark:hover:bg-neutral-700 mr-2">
                Back to List
            </a>
            <a href="{{ route('residents.edit', $resident) }}" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                Edit Resident
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 flex justify-between items-center">
            <div>
                <h2 class="text-xl font-bold text-neutral-800 dark:text-neutral-100">{{ $resident->full_name }}</h2>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">ID: {{ $resident->resident_id }}</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                {{ $resident->residency_status === 'Active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                {{ $resident->residency_status === 'Moved Out' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '' }}
                {{ $resident->residency_status === 'Deceased' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : '' }}">
                {{ $resident->residency_status }}
            </span>
        </div>
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                
                <!-- Personal Information -->
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Personal Information</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-3 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">First Name</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->first_name }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Middle Name</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->middle_name ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Name</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->last_name }} {{ $resident->suffix }}</dd>
                        </div>
                        
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Gender</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->sex }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Birth Date</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ optional($resident->getAttribute('birth_date'))->format('m/d/Y') ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Age</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->age ?? '-' }}</dd>
                        </div>

                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Birth Place</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->birth_place ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Civil Status</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->civil_status }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Relation to Head</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->relation_to_head ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Education & Profession -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Education & Profession</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Grade/Course</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->grade_course ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">School</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->school ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Profession/Occupation</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->profession_occupation ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Employment Type</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->employment_type ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Contact & Insurance -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Contact & Insurance</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Contact Number</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->contact_number ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">FB/Email Address</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->fb_email_address ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">PHIC No.</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->phic_no ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Membership</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->membership ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Health & Family Planning -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Health & Family Planning</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Family Planning Method</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->family_planning_method ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Sanitary</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->sanitary_toilet ? 'Y' : 'N' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Water Supply</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->water_supply ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Smoker</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->smoker ? 'Y' : 'N' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Health Conditions -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Health Conditions</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Binge Drinker</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->binge_drinker ? 'Y' : 'N' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">HPN</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->hpn ? 'Y' : 'N' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">DM</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->dm ? 'Y' : 'N' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">PWD</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->pwd ? 'Y' : 'N' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">PWD Type</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->pwd_type ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Mother's Maiden Name</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->mothers_maiden_name ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Location Information -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Location Information</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Household Number</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->household_number ?? '-' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Address</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->address }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Purok</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->purok->purok_name ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- System Info -->
                <div class="col-span-1 md:col-span-2 mt-2">
                    <h3 class="text-lg font-medium text-neutral-900 dark:text-neutral-100 mb-4 border-b pb-2 border-neutral-100 dark:border-neutral-700">Registration Details</h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4">
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Date Registered</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ optional($resident->date_registered)->format('F j, Y') }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">Last Updated</dt>
                            <dd class="mt-1 text-sm text-neutral-900 dark:text-neutral-200">{{ $resident->updated_at->format('F j, Y g:i A') }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</div>
