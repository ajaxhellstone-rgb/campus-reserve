<section id="member-management-section" class="scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
    <div class="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Member Management</h2>
            <p class="text-sm text-gray-600">Search members and update staff access.</p>
        </div>
        <form id="member-filter-form" method="GET" action="{{ route('admin.dashboard') }}" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
            <label class="sr-only" for="role-filter">Filter members by role</label>
            <select id="role-filter" name="role" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-100">
                <option value="all" {{ ($role ?? 'all') === 'all' ? 'selected' : '' }}>All roles</option>
                <option value="user" {{ ($role ?? '') === 'user' ? 'selected' : '' }}>Members only</option>
                <option value="admin" {{ ($role ?? '') === 'admin' ? 'selected' : '' }}>Staff only</option>
                <option value="super_admin" {{ ($role ?? '') === 'super_admin' ? 'selected' : '' }}>Admins only</option>
            </select>
            <div class="relative">
                <label class="sr-only" for="search">Search members</label>
                <input id="search" name="search" type="search" value="{{ $search ?? '' }}" placeholder="Search name or email" class="w-full rounded-full border border-slate-300 bg-slate-50 py-2 pl-4 pr-20 text-sm focus:border-indigo-400 focus:ring-indigo-200 sm:w-80" autocomplete="off">
                <button type="submit" class="absolute inset-y-0 right-0 inline-flex items-center pr-4 text-slate-500 hover:text-indigo-800">Search</button>
            </div>
        </form>
    </div>

    @if($users->isEmpty())
        <p class="text-gray-500">No users found.</p>
    @else
        <div class="rounded-3xl border border-slate-200" style="height: 32rem; overflow: auto; scrollbar-width: auto; scrollbar-color: #fbbf24 #fffbeb;" tabindex="0" aria-label="Scrollable member list">
            <table class="min-w-full divide-y divide-slate-200 bg-white">
                <thead class="sticky top-0 z-10 bg-indigo-50/80">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Joined</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($users as $user)
                        <tr class="hover:bg-indigo-50/50">
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $user->name }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">{{ $user->email }}</td>
                            <td class="px-4 py-4 text-sm text-gray-700">
                                @if($user->isSuperAdmin())
                                    <span class="inline-flex items-center rounded-full bg-indigo-800 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Admin</span>
                                @elseif($user->is_admin)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-800">Staff</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">Member</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                            <td class="space-x-2 px-4 py-4 text-right text-sm font-medium">
                                @if(auth()->user()->isSuperAdmin())
                                    @if(! $user->isSuperAdmin())
                                        <form method="POST" action="{{ route('admin.users.role', $user) }}" class="inline-flex">
                                            @csrf
                                            <input type="hidden" name="return_search" value="{{ $search ?? '' }}">
                                            <input type="hidden" name="return_role" value="{{ $role ?? 'all' }}">
                                            <select name="role" class="rounded-full border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                                <option value="user" {{ ! $user->is_admin ? 'selected' : '' }}>Member</option>
                                                <option value="admin" {{ $user->is_admin ? 'selected' : '' }}>Staff</option>
                                            </select>
                                            <button type="submit" class="ml-2 rounded-full bg-indigo-700 px-3 py-2 text-xs text-white transition hover:bg-indigo-800">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-flex" data-confirm="Delete this user? This cannot be undone.">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="return_search" value="{{ $search ?? '' }}">
                                            <input type="hidden" name="return_role" value="{{ $role ?? 'all' }}">
                                            <button type="submit" class="rounded-full bg-red-600 px-3 py-2 text-xs text-white transition hover:bg-red-700">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">Protected</span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-500">Admin only</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
</section>
