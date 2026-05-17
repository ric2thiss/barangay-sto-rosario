<x-layouts.app>
    <div class="max-w-2xl mx-auto py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-semibold">{{ isset($user) ? 'Edit User' : 'Create User' }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ isset($user) ? 'Update account details and role' : 'Add a new user to the system' }}
                </p>
            </div>
            <a href="{{ route('admin.users.index') }}"
               class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600 dark:hover:bg-zinc-700">
                Back to List
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                <ul class="text-sm text-red-800 dark:text-red-300 space-y-1 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 p-6">
            <form method="POST"
                  action="{{ isset($user) ? route('admin.users.update', $user) : route('admin.users.store') }}">
                @csrf
                @if(isset($user))
                    @method('PUT')
                @endif

                <div class="space-y-5">
                    {{-- Name --}}
                    <div>
                        <label for="name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name"
                               value="{{ old('name', $user->name ?? '') }}"
                               required
                               class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Username --}}
                    <div>
                        <label for="username" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Username <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="username" name="username"
                               value="{{ old('username', $user->username ?? '') }}"
                               required
                               class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Email Address <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email"
                               value="{{ old('email', $user->email ?? '') }}"
                               required
                               class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    {{-- Role --}}
                    <div>
                        <label for="role_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Role <span class="text-red-500">*</span>
                        </label>
                        <select id="role_id" name="role_id" required
                                class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select a role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->role_id }}"
                                    @selected(old('role_id', $user->role_id ?? '') == $role->role_id)>
                                    {{ $role->role_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                            Status
                        </label>
                        <select id="status" name="status"
                                class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="active" @selected(old('status', $user->status ?? 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status', $user->status ?? '') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    {{-- Password --}}
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-5">
                        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-3">
                            {{ isset($user) ? 'Change Password (leave blank to keep current)' : 'Password' }}
                        </p>
                        <div class="space-y-4">
                            <div>
                                <label for="password" class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                    Password @if(!isset($user))<span class="text-red-500">*</span>@endif
                                </label>
                                <input type="password" id="password" name="password"
                                       @if(!isset($user)) required @endif
                                       class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">
                                    Confirm Password @if(!isset($user))<span class="text-red-500">*</span>@endif
                                </label>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       @if(!isset($user)) required @endif
                                       class="w-full rounded-md border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    
            {{-- Resident confirmation --}}
<div class="flex items-start gap-3">
    <input type="hidden" name="is_resident" value="0">
    <input type="checkbox" id="is_resident" name="is_resident" value="1"
           {{ old('is_resident', $user->is_resident ?? false) ? 'checked' : '' }}
           class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800">
    <label for="is_resident" class="text-sm text-zinc-600 dark:text-zinc-400 leading-snug cursor-pointer">
        The user is a <span class="font-medium text-zinc-800 dark:text-zinc-200">resident</span> of Rosario
    </label>
</div>

{{-- Age confirmation --}}
<div class="flex items-start gap-3">
    <input type="hidden" name="is_of_age" value="0">
    <input type="checkbox" id="is_of_age" name="is_of_age" value="1"
           {{ old('is_of_age', $user->is_of_age ?? false) ? 'checked' : '' }}
           class="mt-0.5 h-4 w-4 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-800">
    <label for="is_of_age" class="text-sm text-zinc-600 dark:text-zinc-400 leading-snug cursor-pointer">
        The user is <span class="font-medium text-zinc-800 dark:text-zinc-200">18 years of age or older</span>
    </label>
</div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-5 border-t border-zinc-200 dark:border-zinc-700">
                    <a href="{{ route('admin.users.index') }}"
                       class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:border-zinc-600">
                        Cancel
                    </a>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ isset($user) ? 'Update User' : 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>