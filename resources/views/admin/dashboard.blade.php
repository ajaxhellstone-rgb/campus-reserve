@extends('layouts.app')

@section('title', 'Admin Dashboard - Campus Reserve')

@section('content')
<!-- Admin dashboard layout: pending approvals, audit logs, and user management sections. -->
<div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
                    <p class="mt-2 text-gray-600">Approve or reject reservations, manage members, and review audit activity.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.facilities.create') }}" class="inline-flex items-center px-4 py-2 bg-green-700 text-white rounded-full hover:bg-green-800 transition-colors">Facility Management</a>
                    <a href="{{ route('admin.property-management.index') }}" class="inline-flex items-center px-4 py-2 bg-green-700 text-white rounded-full hover:bg-green-800 transition-colors">Property Management</a>
                    <a href="/reserve" class="inline-flex items-center px-4 py-2 bg-black text-white rounded-full hover:bg-gray-800 transition-colors">Back to User Portal</a>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Pending Reservations</p>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ $pendingReservations->count() }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Recent Decisions</p>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ $recentReservations->count() }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Audit Log Entries</p>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ $auditLogs->count() }}</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm">
                <p class="text-sm font-medium text-gray-500">Members</p>
                <p class="mt-4 text-3xl font-bold text-gray-900">{{ $users->total() ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <section class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6 xl:col-span-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Pending Reservations</h2>
                        <p class="text-sm text-gray-600">Review and approve or reject requests.</p>
                    </div>
                    <span class="text-sm text-gray-500">{{ $pendingReservations->count() }} pending</span>
                </div>

                @if($pendingReservations->isEmpty())
                    <p class="text-gray-500">No pending reservations at the moment.</p>
                @else
                    <div class="space-y-4 pr-2" style="height: 32rem; overflow-y: scroll; scrollbar-width: auto; scrollbar-color: #6b7280 #e5e7eb;" tabindex="0" aria-label="Scrollable pending reservations">
                        @foreach($pendingReservations as $reservation)
                            <div class="border border-gray-200 rounded-3xl p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                                    <div>
                                        <p class="text-sm text-gray-500">Reservation #{{ $reservation->id }}</p>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $reservation->facility->name }} — {{ $reservation->reservation_date->format('M d, Y') }}</h3>
                                        <p class="text-sm text-gray-600">
                                            @if($reservation->items->isNotEmpty())
                                                Use: {{ $reservation->start_time->format('H:i') }} · Return: {{ $reservation->end_time->format('H:i') }}
                                            @else
                                                {{ $reservation->start_time->format('H:i') }} — {{ $reservation->end_time->format('H:i') }}
                                            @endif
                                        </p>
                                        <p class="text-sm text-gray-600 mt-2">Requested by {{ $reservation->user->name }} ({{ $reservation->user->email }})</p>
                                        <p class="text-sm text-gray-600 mt-1">Purpose: {{ $reservation->purpose }}</p>
                                        @if($reservation->items->isNotEmpty())
                                            <p class="mt-2 text-sm text-gray-700">
                                                Items:
                                                @foreach($reservation->items as $item)
                                                    {{ $item->name }} × {{ $item->pivot->quantity }}@if(!$loop->last), @endif
                                                @endforeach
                                            </p>
                                        @endif
                                    </div>
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-full bg-green-600 text-white hover:bg-green-700 transition">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}">
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-full bg-red-600 text-white hover:bg-red-700 transition">Reject</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" onsubmit="return confirm('Delete this reservation? This will remove it from the calendar and user view.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-4 py-2 rounded-full bg-gray-600 text-white hover:bg-gray-700 transition">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Audit Log</h2>
                    <p class="text-sm text-gray-600">Track administrator actions and reservation activity.</p>
                </div>

                @if($auditLogs->isEmpty())
                    <p class="text-gray-500">No audit log entries yet.</p>
                @else
                    <div class="space-y-4 pr-2" style="height: 32rem; overflow-y: scroll; scrollbar-width: auto; scrollbar-color: #6b7280 #e5e7eb;" tabindex="0" aria-label="Scrollable audit log entries">
                        @foreach($auditLogs as $log)
                            <div class="rounded-3xl border border-gray-200 p-4 bg-gray-50">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $log->created_at->format('M d, Y H:i:s') }}</p>
                                        <p class="mt-1 text-base font-semibold text-gray-900 capitalize">{{ $log->action }}</p>
                                        <p class="text-sm text-gray-700 mt-1">{{ $log->details }}</p>
                                        @if($log->reservation)
                                            <p class="text-xs text-gray-500 mt-2">Reservation #{{ $log->reservation->id }} — {{ $log->reservation->facility->name }}</p>
                                        @endif
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-200 text-xs font-semibold uppercase text-gray-700">{{ $log->user?->name ?? 'Admin' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <section class="bg-white rounded-3xl border border-gray-200 shadow-sm p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Member Management</h2>
                    <p class="text-sm text-gray-600">Search members and update admin access.</p>
                </div>
                <form method="GET" action="{{ route('admin.dashboard') }}" class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    <label class="sr-only" for="role-filter">Filter members by role</label>
                    <select id="role-filter" name="role" onchange="this.form.submit()" class="rounded-full border border-gray-300 bg-white px-4 py-2 text-sm text-gray-700 focus:border-black focus:outline-none focus:ring-1 focus:ring-black">
                        <option value="all" {{ ($role ?? 'all') === 'all' ? 'selected' : '' }}>All roles</option>
                        <option value="user" {{ ($role ?? '') === 'user' ? 'selected' : '' }}>Members only</option>
                        <option value="admin" {{ ($role ?? '') === 'admin' ? 'selected' : '' }}>Admins only</option>
                        <option value="super_admin" {{ ($role ?? '') === 'super_admin' ? 'selected' : '' }}>Head admins only</option>
                    </select>
                    <div class="relative">
                        <label class="sr-only" for="search">Search members</label>
                        <input id="search" name="search" type="text" value="{{ $search ?? '' }}" placeholder="Search name or email" class="w-full rounded-full border border-gray-300 bg-gray-50 py-2 pl-4 pr-20 text-sm focus:border-black focus:ring-black sm:w-80" />
                        <button type="submit" class="absolute inset-y-0 right-0 inline-flex items-center pr-4 text-gray-500 hover:text-gray-900">Search</button>
                    </div>
                </form>
            </div>

            @if($users->isEmpty())
                <p class="text-gray-500">No users found.</p>
            @else
                <div class="rounded-3xl border border-gray-200" style="height: 32rem; overflow: auto; scrollbar-width: auto; scrollbar-color: #6b7280 #e5e7eb;" tabindex="0" aria-label="Scrollable member list">
                    <table class="min-w-full divide-y divide-gray-200 bg-white">
                        <thead class="sticky top-0 z-10 bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Role</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">Joined</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 text-sm text-gray-700">{{ $user->name }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700">{{ $user->email }}</td>
                                    <td class="px-4 py-4 text-sm text-gray-700">
                                        @if($user->isSuperAdmin())
                                            <span class="inline-flex items-center rounded-full bg-black px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Head Admin</span>
                                        @elseif($user->is_admin)
                                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-green-800">Admin</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-700">Member</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">{{ $user->created_at->format('M d, Y') }}</td>
                                    <td class="px-4 py-4 text-right text-sm font-medium space-x-2">
                                        @if(! $user->isSuperAdmin())
                                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="inline-flex">
                                                @csrf
                                                <input type="hidden" name="return_search" value="{{ $search ?? '' }}">
                                                <input type="hidden" name="return_role" value="{{ $role ?? 'all' }}">
                                                <select name="role" class="rounded-full border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:outline-none focus:ring-1 focus:ring-black">
                                                    <option value="user" {{ ! $user->is_admin ? 'selected' : '' }}>Member</option>
                                                    <option value="admin" {{ $user->is_admin ? 'selected' : '' }}>Admin</option>
                                                </select>
                                                <button type="submit" class="ml-2 rounded-full bg-black px-3 py-2 text-xs text-white hover:bg-gray-900 transition">Save</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline-flex" onsubmit="return confirm('Delete this user? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_search" value="{{ $search ?? '' }}">
                                                <input type="hidden" name="return_role" value="{{ $role ?? 'all' }}">
                                                <button type="submit" class="rounded-full bg-red-600 px-3 py-2 text-xs text-white hover:bg-red-700 transition">Delete</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-gray-500">Protected</span>
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
    </div>
</div>
@endsection
