<x-layouts.app>
    <div class="max-w-7xl mx-auto py-8">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-semibold">User Management</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Manage system accounts and roles</p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                + New User
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900 p-4">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900 p-4">
                <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            @foreach ($stats as $label => $count)
                <div class="bg-white dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700 px-4 py-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">{{ $label }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white mt-1">{{ $count }}</p>
                </div>
            @endforeach
        </div>

        {{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-3 mb-4">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Search name, email, username..."
           onkeydown="if(event.key==='Enter')this.form.submit()"
           class="rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    <select name="role" onchange="this.form.submit()"
            class="rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <option value="">All Roles</option>
        @foreach ($roles as $role)
            <option value="{{ $role->role_id }}" @selected(request('role') == $role->role_id)>
                {{ $role->role_name }}
            </option>
        @endforeach
    </select>
    <select name="status" onchange="this.form.submit()"
            class="rounded-md border-zinc-300 dark:border-zinc-700 dark:bg-zinc-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        <option value="">All Status</option>
        <option value="active" @selected(request('status') === 'active')>Active</option>
        <option value="deactivated" @selected(request('status') === 'deactivated')>Deactivated</option>
    </select>
    @if(request()->hasAny(['search','role','status']))
        <a href="{{ route('admin.users.index') }}" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-md hover:bg-zinc-50">
            Clear
        </a>
    @endif
</form>

        {{-- Table --}}{{-- Table --}}
<div class="overflow-x-auto rounded-lg border border-neutral-200 dark:border-neutral-700 bg-transparent">
    <table class="min-w-full text-sm dark:bg-[#0f1e2e]">
        <thead class="bg-neutral-50 dark:bg-[#15233b]">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">User</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Username</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Email</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Role</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Status</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Joined</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Resident</th>
                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-[#e5e7eb]">Age</th>
                <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-[#e5e7eb]">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr class="border-t border-neutral-200 dark:border-neutral-700 dark:bg-[#0f1e2e] s">
                    <td class="px-4 py-3 dark:text-[#e5e7eb]">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-xs font-semibold text-indigo-700 dark:text-indigo-300 flex-shrink-0">
                                {{ $user->initials() }}
                            </div>
                            <span class="font-medium text-zinc-900 dark:text-[#e5e7eb]">{{ $user->name }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-[#e5e7eb]">{{ $user->username }}</td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-[#e5e7eb]">{{ $user->email }}</td>
                    <td class="px-4 py-3">
                        <span class="text-xs font-medium text-zinc-700 dark:text-[#e5e7eb]">
                            {{ $user->role?->role_name ?? '—' }}
                        </span>
                    </td>
                   <td class="px-4 py-3">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
        @if(strtolower($user->status ?? 'active') === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
        @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 @endif">
        {{ ucfirst(strtolower($user->status ?? 'active')) }}
    </span>
</td>
                    <td class="px-4 py-3 text-zinc-500 dark:text-[#e5e7eb] text-xs">
                        {{ $user->created_at->format('M d, Y') }}
                    </td>

                    {{-- Resident --}}
<td class="px-4 py-3">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
        {{ $user->is_resident ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
        {{ $user->is_resident ? 'Resident' : 'Alien' }}
    </span>
</td>

{{-- Age --}}
<td class="px-4 py-3">
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
        {{ $user->is_of_age ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200' }}">
        {{ $user->is_of_age ? '18+' : 'Under 18' }}
    </span>
</td>
                    <td class="px-4 py-3 text-right space-x-2">
    <a href="{{ route('admin.users.edit', $user) }}"
       class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-green-700 bg-green-50 hover:bg-green-100 dark:text-green-400 dark:bg-green-900/30 dark:hover:bg-green-900/50 transition-colors">
        Edit
    </a>
    @if($user->user_id !== auth()->id())
        @if(($user->status ?? 'active') === 'deactivated')
            {{-- Reactivate button --}}
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline">
                @csrf
                @method('DELETE')
                <input type="hidden" name="action" value="activate">
                <button type="submit"
                        class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30 dark:hover:bg-blue-900/50 transition-colors">
                    Activate
                </button>
            </form>
        @else
            {{-- Deactivate button --}}
            <button type="button"
                    onclick="confirmDelete({{ $user->user_id }}, '{{ $user->name }}')"
                    class="inline-flex items-center justify-center px-3 py-1.5 text-xs font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/30 dark:hover:bg-red-900/50 transition-colors">
                Deactivate
            </button>
        @endif
    @endif
</td>
                </tr>
            @empty
                <tr class="dark:bg-[#0f1e2e]">
                    <td colspan="7" class="px-4 py-8 text-center text-zinc-500 dark:text-[#cbd5e1]">
                        No users found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

        <div class="mt-4">{{ $users->withQueryString()->links() }}</div>
    </div>

    {{-- Delete Modal --}}
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white mb-2">Confirm Deactivation</h3>
<p class="text-zinc-600 dark:text-zinc-300 mb-4">
    Are you sure you want to deactivate <span id="deleteUserName" class="font-medium"></span>? They will no longer be able to log in.
</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-700 bg-white border border-zinc-300 rounded-md hover:bg-zinc-50 dark:bg-zinc-700 dark:text-zinc-300 dark:border-zinc-600">
                    Cancel
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete(userId, name) {
            document.getElementById('deleteUserName').textContent = name;
            document.getElementById('deleteForm').action = `/admin/users/${userId}`;
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) closeDeleteModal();
        });
    </script>
</x-layouts.app>