@extends('layouts.app')

@section('title', 'Staff Dashboard - Campus Reserve')

@section('content')
<!-- Admin overview groups existing reservation, member, and audit tools beside contextual navigation. -->
<div class="min-h-screen bg-[#f5f3f7]">
    <div class="flex min-h-screen flex-col md:flex-row">
        @include('admin.partials.sidebar')

        <main class="min-w-0 flex-1 px-3 py-5 sm:px-6 sm:py-8 xl:px-10">
            <div class="mx-auto max-w-[1600px]">
                <section id="overview" data-admin-section="overview">
                <div class="mb-7 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-sm font-semibold text-green-800">Campus Reserve / Workspace</p>
                        <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Dashboard overview</h1>
                        <p class="mt-2 text-sm text-slate-500 sm:text-base">Reservations, members, and administration activity in one place.</p>
                    </div>
                    <a href="/reserve" class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-green-300 hover:text-green-900 md:hidden">
                        Open user portal
                    </a>
                </div>

        @if(session('status'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->has('current_password'))
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ $errors->first('current_password') }}</div>
        @elseif($errors->any())
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-6 grid grid-cols-2 gap-3 sm:mb-8 sm:gap-5 xl:grid-cols-4">
            <div class="rounded-2xl border border-orange-100 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-gray-500">Pending Reservations</p>
                <p id="pending-reservations-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $pendingReservations->total() }}</p>
            </div>
            <div class="rounded-2xl border border-green-100 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-gray-500">Recent Decisions</p>
                <p id="recent-decisions-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $recentReservations->count() }}</p>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-gray-500">Audit Log Entries</p>
                <p id="audit-log-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $auditLogs->count() }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <p class="text-sm font-medium text-gray-500">Members</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $users->total() ?? 0 }}</p>
            </div>
        </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    <a href="#reservations" data-admin-section-link="reservations" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-green-300 hover:text-green-900">Review reservations <span class="ml-1 text-slate-400">→</span></a>
                    <a href="#members" data-admin-section-link="members" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-green-300 hover:text-green-900">Manage members <span class="ml-1 text-slate-400">→</span></a>
                    <a href="#audit" data-admin-section-link="audit" class="rounded-2xl border border-slate-200 bg-white p-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-green-300 hover:text-green-900">View audit activity <span class="ml-1 text-slate-400">→</span></a>
                </div>

                @php
                    $maxDecisionCount = max($approvedReservationCount, $rejectedReservationCount, 1);
                @endphp
                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="reservation-outcomes-title">
                    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h2 id="reservation-outcomes-title" class="text-xl font-semibold text-slate-900">Reservation outcomes</h2>
                            <p class="mt-1 text-sm text-slate-500">Total approved and rejected reservations.</p>
                        </div>
                        <span class="text-xs font-medium text-slate-400">All time</span>
                    </div>

                    <div class="space-y-5" role="img" aria-label="Bar chart comparing total approved and rejected reservations">
                        <div data-reservation-outcome="approved" data-total="{{ $approvedReservationCount }}">
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full bg-green-600" aria-hidden="true"></span>
                                    Approved
                                </span>
                                <span class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format($approvedReservationCount) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-green-600 transition-[width]" style="width: {{ round(($approvedReservationCount / $maxDecisionCount) * 100, 1) }}%"></div>
                            </div>
                        </div>

                        <div data-reservation-outcome="rejected" data-total="{{ $rejectedReservationCount }}">
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                                    <span class="h-2.5 w-2.5 rounded-full bg-rose-500" aria-hidden="true"></span>
                                    Rejected
                                </span>
                                <span class="text-sm font-semibold tabular-nums text-slate-900">{{ number_format($rejectedReservationCount) }}</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-rose-500 transition-[width]" style="width: {{ round(($rejectedReservationCount / $maxDecisionCount) * 100, 1) }}%"></div>
                            </div>
                        </div>
                    </div>
                </section>

                    </section>

                <section id="reservations" data-admin-section="reservations" class="hidden">
            <section id="pending-reservations-section" class="scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Pending Reservations</h2>
                        <p class="text-sm text-gray-600">Review and approve or reject requests.</p>
                    </div>
                    <span id="pending-reservations-label" class="text-sm text-gray-500">{{ $pendingReservations->total() }} pending</span>
                </div>
                <p id="dashboard-action-status" class="mb-3 hidden text-sm text-emerald-700" role="status" aria-live="polite"></p>
                <p id="dashboard-action-error" class="mb-3 hidden text-sm text-rose-700" role="alert"></p>

                @if($pendingReservations->isEmpty())
                    <p class="text-gray-500">No pending reservations at the moment.</p>
                @else
                    <div id="pending-reservations-list" class="space-y-4 pr-2" style="height: 32rem; overflow-y: scroll; scrollbar-width: auto; scrollbar-color: #6b7280 #e5e7eb;" tabindex="0" aria-label="Scrollable pending reservations">
                        @foreach($pendingReservations as $reservation)
                            <div data-reservation-card class="rounded-3xl border border-slate-200 bg-white p-4 transition hover:border-indigo-200">
                                <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-start">
                                    <div>
                                        <p class="text-sm text-gray-500">Reservation #{{ $reservation->id }}</p>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $reservation->items->isNotEmpty() ? 'Property items' : ($reservation->facility->name ?? 'Facility') }} — {{ $reservation->reservation_date->format('M d, Y') }}</h3>
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
                                        <form method="POST" action="{{ route('admin.reservations.approve', $reservation) }}" data-pending-action>
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-full bg-green-600 text-white hover:bg-green-700 transition">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reservations.reject', $reservation) }}" data-pending-action>
                                            @csrf
                                            <button type="submit" class="px-4 py-2 rounded-full bg-red-600 text-white hover:bg-red-700 transition">Reject</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" data-pending-action data-confirm="Delete this reservation? This will remove it from the calendar and user view.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-4 py-2 rounded-full bg-gray-600 text-white hover:bg-gray-700 transition">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4">
                        {{ $pendingReservations->links() }}
                    </div>
                @endif
            </section>

            <section id="edit-reservations-section" class="mt-6 scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Edit Reservations</h2>
                    <p class="text-sm text-gray-600">Adjust approved reservation dates, schedules, or purpose. Deleting a reservation removes it from the client’s list and sends them a notification.</p>
                </div>
                <form id="approved-reservation-filter-form" method="GET" action="{{ route('admin.dashboard') }}#reservations" class="mb-4 flex flex-col gap-2 sm:w-64">
                    <input type="hidden" name="role" value="{{ $role ?? 'all' }}">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <input type="hidden" name="member_sort" value="{{ $memberSort ?? 'joined_newest' }}">
                    <label for="reservation-sort" class="text-sm font-medium text-slate-700">Sort approved reservations</label>
                    <select id="reservation-sort" name="reservation_sort" class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm text-slate-700 focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-100">
                        <option value="recent" {{ ($reservationSort ?? 'recent') === 'recent' ? 'selected' : '' }}>Most recent date</option>
                        <option value="oldest" {{ ($reservationSort ?? '') === 'oldest' ? 'selected' : '' }}>Oldest date</option>
                        <option value="alphabetical" {{ ($reservationSort ?? '') === 'alphabetical' ? 'selected' : '' }}>Alphabetical (room / item)</option>
                    </select>
                </form>

                @if($approvedReservations->isEmpty())
                    <p class="text-gray-500">No approved reservations to edit.</p>
                @else
                    <div class="overflow-hidden rounded-2xl border border-slate-200">
                        <div class="h-[32rem] overflow-y-scroll overflow-x-auto" style="scrollbar-width: auto; scrollbar-color: #fbbf24 #fffbeb;" tabindex="0" aria-label="Scrollable approved reservations">
                            <table class="min-w-full divide-y divide-slate-200 bg-white">
                                <thead class="sticky top-0 z-10 bg-green-50">
                                    <tr>
                                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Reservation</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Room / Items</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Requested by</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Date</th>
                                        <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($approvedReservations as $reservation)
                                        <tr class="hover:bg-green-50/40">
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-800">#{{ $reservation->id }}</td>
                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $reservation->facility?->name ?? ($reservation->items->isNotEmpty() ? 'Property items' : 'Facility') }}
                                                @if($reservation->items->isNotEmpty())
                                                    <span class="block max-w-xs truncate text-xs text-slate-500">
                                                        @foreach($reservation->items as $item)
                                                            {{ $item->name }} × {{ $item->pivot->quantity }}@if(!$loop->last), @endif
                                                        @endforeach
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $reservation->user?->name ?? 'Unknown user' }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">{{ $reservation->reservation_date->format('M d, Y') }}</td>
                                            <td class="whitespace-nowrap px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-2">
                                                    <button
                                                        type="button"
                                                        data-edit-reservation
                                                        data-edit-action="{{ route('admin.reservations.update', $reservation) }}"
                                                        data-reservation-id="{{ $reservation->id }}"
                                                        data-reservation-room="{{ $reservation->facility?->name ?? ($reservation->items->isNotEmpty() ? 'Property items' : 'Facility') }}"
                                                        data-reservation-date="{{ $reservation->reservation_date->format('Y-m-d') }}"
                                                        data-reservation-start="{{ $reservation->start_time->format('H:i') }}"
                                                        data-reservation-end="{{ $reservation->end_time->format('H:i') }}"
                                                        data-reservation-purpose="{{ $reservation->purpose }}"
                                                        class="rounded-full bg-green-700 px-4 py-2 text-xs font-semibold text-white transition hover:bg-green-800">
                                                        Edit
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.reservations.destroy', $reservation) }}" data-confirm="Delete this approved reservation? The client will be notified.">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="rounded-full bg-red-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-red-700">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="mt-4">
                        {{ $approvedReservations->links() }}
                    </div>
                @endif
            </section>

            <div id="approved-reservation-edit-modal" class="fixed inset-0 z-[90] hidden items-center justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="approved-reservation-edit-title">
                <div class="my-auto w-full max-w-3xl rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-7">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-medium text-slate-500">Approved reservation</p>
                            <h2 id="approved-reservation-edit-title" class="mt-1 text-2xl font-semibold text-slate-900">Edit reservation</h2>
                            <p id="approved-reservation-edit-summary" class="mt-1 text-sm text-slate-600"></p>
                        </div>
                        <button id="approved-reservation-edit-close" type="button" class="rounded-full p-2 text-xl leading-none text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Close edit reservation dialog">&times;</button>
                    </div>

                    <form id="approved-reservation-edit-form" method="POST" action="#" data-confirm="Save these changes to the approved reservation?" class="space-y-5">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block text-sm font-medium text-slate-700">
                                Date
                                <input id="approved-reservation-edit-date" name="reservation_date" type="date" required min="{{ now()->toDateString() }}" max="{{ $maximumReservationDate }}" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">
                                Start time
                                <input id="approved-reservation-edit-start" name="start_time" type="time" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">
                                End time
                                <input id="approved-reservation-edit-end" name="end_time" type="time" required class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </label>
                            <label class="block text-sm font-medium text-slate-700">
                                Purpose
                                <input id="approved-reservation-edit-purpose" name="purpose" type="text" required maxlength="255" class="mt-1 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            </label>
                        </div>
                        <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                            <button id="approved-reservation-edit-cancel" type="button" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-xl bg-green-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Save changes</button>
                        </div>
                    </form>
                </div>
            </div>
                </section>

                <section id="audit" data-admin-section="audit" class="hidden">
            <section id="audit-activity-section" class="scroll-mt-6 rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm sm:p-6">
                <div class="mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Audit Log</h2>
                    <p class="text-sm text-gray-600">Track administrator actions and reservation activity.</p>
                </div>

                <div id="audit-log-list" class="space-y-4 pr-2" style="height: 32rem; overflow-y: scroll; scrollbar-width: auto; scrollbar-color: #6b7280 #e5e7eb;" tabindex="0" aria-label="Scrollable audit log entries">
                    @if($auditLogs->isEmpty())
                        <p data-empty-audit class="text-gray-500">No audit log entries yet.</p>
                    @else
                        @foreach($auditLogs as $log)
                            <div class="rounded-3xl border border-slate-200 bg-indigo-50/40 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $log->created_at->format('M d, Y H:i:s') }}</p>
                                        <p class="mt-1 text-base font-semibold text-gray-900 capitalize">{{ $log->action }}</p>
                                        <p class="text-sm text-gray-700 mt-1">{{ $log->details }}</p>
                                        @if($log->reservation)
                                            <p class="text-xs text-gray-500 mt-2">Reservation #{{ $log->reservation->id }}{{ $log->reservation->facility ? ' — '.$log->reservation->facility->name : ' — Property items' }}</p>
                                        @endif
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-200 text-xs font-semibold uppercase text-gray-700">{{ $log->user?->name ?? 'Admin' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>
                </section>

                <section id="members" data-admin-section="members" class="hidden">
        <p id="member-management-status" class="mt-3 hidden text-sm text-emerald-700" role="status" aria-live="polite"></p>
        <p id="member-management-error" class="mt-3 hidden text-sm text-rose-700" role="alert"></p>
        <div id="member-management-content" data-endpoint="{{ route('admin.dashboard') }}" aria-busy="false">
            @include('admin.partials.members')
        </div>
                </section>
            </div>
        </main>
    </div>
</div>
@include('admin.partials.password-confirmation')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const memberContent = document.getElementById('member-management-content');
        const reservationEditModal = document.getElementById('approved-reservation-edit-modal');
        const reservationEditForm = document.getElementById('approved-reservation-edit-form');
        const reservationEditSummary = document.getElementById('approved-reservation-edit-summary');
        const reservationEditDate = document.getElementById('approved-reservation-edit-date');
        const reservationEditStart = document.getElementById('approved-reservation-edit-start');
        const reservationEditEnd = document.getElementById('approved-reservation-edit-end');
        const reservationEditPurpose = document.getElementById('approved-reservation-edit-purpose');
        let reservationEditTrigger = null;
        const errorMessage = document.getElementById('member-management-error');
        const memberStatus = document.getElementById('member-management-status');
        const dashboardStatus = document.getElementById('dashboard-action-status');
        const dashboardError = document.getElementById('dashboard-action-error');
        let activeRequest = null;
        let searchTimer = null;
        const dashboardSections = Array.from(document.querySelectorAll('[data-admin-section]'));
        const dashboardSectionLinks = Array.from(document.querySelectorAll('[data-admin-section-link]'));
        const legacySectionNames = {
            'pending-reservations-section': 'reservations',
            'member-management-section': 'members',
            'audit-activity-section': 'audit',
        };

        function closeReservationEditModal() {
            reservationEditModal.classList.add('hidden');
            reservationEditModal.classList.remove('flex');
            reservationEditTrigger?.focus();
            reservationEditTrigger = null;
        }

        document.addEventListener('click', function(event) {
            if (!(event.target instanceof Element)) return;

            const editButton = event.target.closest('[data-edit-reservation]');
            if (editButton) {
                reservationEditTrigger = editButton;
                reservationEditForm.action = editButton.dataset.editAction;
                reservationEditSummary.textContent = `Reservation #${editButton.dataset.reservationId} — ${editButton.dataset.reservationRoom}`;
                reservationEditDate.value = editButton.dataset.reservationDate;
                reservationEditStart.value = editButton.dataset.reservationStart;
                reservationEditEnd.value = editButton.dataset.reservationEnd;
                reservationEditPurpose.value = editButton.dataset.reservationPurpose;
                reservationEditModal.classList.remove('hidden');
                reservationEditModal.classList.add('flex');
                reservationEditDate.focus();
                return;
            }

            if (event.target === reservationEditModal
                || event.target.closest('#approved-reservation-edit-close, #approved-reservation-edit-cancel')) {
                closeReservationEditModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            const passwordDialog = document.getElementById('admin-password-confirmation');
            if (event.key === 'Escape'
                && !reservationEditModal.classList.contains('hidden')
                && (!passwordDialog || passwordDialog.classList.contains('hidden'))) {
                closeReservationEditModal();
            }
        });

        function activateDashboardSection(sectionName, updateHistory) {
            const section = dashboardSections.find(function(item) {
                return item.dataset.adminSection === sectionName;
            });
            if (!section) return;

            dashboardSections.forEach(function(item) {
                item.classList.toggle('hidden', item !== section);
            });
            dashboardSectionLinks.forEach(function(link) {
                const isActive = link.dataset.adminSectionLink === sectionName;
                link.classList.toggle('bg-green-50', isActive);
                link.classList.toggle('text-green-900', isActive);
                link.classList.toggle('font-semibold', isActive);
                link.classList.toggle('text-slate-600', !isActive);
                link.classList.toggle('font-medium', !isActive);
                link.setAttribute('aria-current', isActive ? 'page' : 'false');
            });

            if (updateHistory) {
                window.history.pushState({}, '', `#${sectionName}`);
            }
            window.scrollTo(0, 0);
        }

        function loadApprovedReservations(url, updateHistory) {
            url.hash = '#reservations';
            fetch(url, {
                headers: { 'Accept': 'text/html' },
                credentials: 'same-origin',
            })
                .then(function(response) {
                    if (!response.ok) throw new Error('Approved reservations could not be updated. Please try again.');
                    return response.text();
                })
                .then(function(html) {
                    const updatedDocument = new DOMParser().parseFromString(html, 'text/html');
                    const updatedSection = updatedDocument.querySelector('#edit-reservations-section');
                    const currentSection = document.querySelector('#edit-reservations-section');
                    if (!updatedSection || !currentSection) {
                        throw new Error('The updated approved reservation list could not be displayed.');
                    }
                    currentSection.replaceWith(updatedSection);
                    if (updateHistory) {
                        window.history.pushState({}, '', url);
                    }
                    activateDashboardSection('reservations', false);
                })
                .catch(function(error) {
                    dashboardError.textContent = error.message;
                    dashboardError.classList.remove('hidden');
                });
        }

        function approvedReservationsUrl(form) {
            const url = new URL(form.action, window.location.origin);
            const params = new URLSearchParams(new FormData(form));
            url.search = params.toString();
            url.hash = '#reservations';
            return url;
        }

        document.addEventListener('change', function(event) {
            if (!(event.target instanceof HTMLSelectElement) || event.target.id !== 'reservation-sort') return;
            const form = event.target.form;
            if (!form) return;
            const url = approvedReservationsUrl(form);
            url.searchParams.delete('approvedPage');
            loadApprovedReservations(url, true);
        });

        document.addEventListener('click', function(event) {
            if (!(event.target instanceof Element)) return;
            const link = event.target.closest('#edit-reservations-section nav[aria-label="Pagination Navigation"] a');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            const url = new URL(link.href);
            url.hash = '#reservations';
            loadApprovedReservations(url, true);
        });

        function activateDashboardSectionFromLocation() {
            const hash = window.location.hash.slice(1);
            const sectionName = legacySectionNames[hash] || hash || 'overview';
            activateDashboardSection(sectionName, false);
        }

        dashboardSectionLinks.forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                activateDashboardSection(link.dataset.adminSectionLink, true);
            });
        });
        window.addEventListener('popstate', activateDashboardSectionFromLocation);
        activateDashboardSectionFromLocation();

        function showMessage(element, message) {
            element.textContent = message;
            element.classList.remove('hidden');
        }

        function setLoading(loading) {
            memberContent.setAttribute('aria-busy', String(loading));
        }

        function loadMembers(url, historyMode) {
            if (activeRequest) activeRequest.abort();
            const controller = new AbortController();
            activeRequest = controller;
            setLoading(true);
            errorMessage.classList.add('hidden');

            fetch(url, {
                headers: {
                    'Accept': 'text/html',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Member list could not be updated. Please try again.');
                    }
                    return response.text();
                })
                .then(function(html) {
                    const previousScrollTop = memberContent.querySelector('[aria-label="Scrollable member list"]')?.scrollTop || 0;
                    const activeElement = document.activeElement;
                    const preserveSearchFocus = activeElement instanceof HTMLInputElement
                        && activeElement.name === 'search'
                        && memberContent.contains(activeElement);
                    const currentSearch = preserveSearchFocus
                        ? activeElement.value
                        : memberContent.querySelector('#search')?.value;
                    const selectionStart = preserveSearchFocus ? activeElement.selectionStart : null;
                    const selectionEnd = preserveSearchFocus ? activeElement.selectionEnd : null;

                    memberContent.innerHTML = html;
                    const updatedSearch = memberContent.querySelector('#search');
                    if (updatedSearch && currentSearch !== undefined) {
                        updatedSearch.value = currentSearch;
                    }
                    if (preserveSearchFocus && updatedSearch) {
                        updatedSearch.focus();
                        if (selectionStart !== null && selectionEnd !== null) {
                            updatedSearch.setSelectionRange(selectionStart, selectionEnd);
                        }
                    }
                    const updatedList = memberContent.querySelector('[aria-label="Scrollable member list"]');
                    if (updatedList) updatedList.scrollTop = previousScrollTop;
                    if (historyMode === 'push') {
                        window.history.pushState({}, '', url);
                    } else if (historyMode === 'replace') {
                        window.history.replaceState({}, '', url);
                    }
                })
                .catch(function(error) {
                    if (error.name !== 'AbortError') {
                        errorMessage.textContent = error.message;
                        errorMessage.classList.remove('hidden');
                    }
                })
                .finally(function() {
                    if (activeRequest === controller) {
                        activeRequest = null;
                        setLoading(false);
                    }
                });
        }

        function membersUrl(pageUrl) {
            const url = new URL(pageUrl || memberContent.dataset.endpoint, window.location.origin);
            const form = memberContent.querySelector('#member-filter-form');
            if (form) {
                const formData = new FormData(form);
                url.searchParams.set('search', String(formData.get('search') || '').trim());
                url.searchParams.set('role', String(formData.get('role') || 'all'));
                url.searchParams.set('member_sort', String(formData.get('member_sort') || 'joined_newest'));
                const currentUrl = new URL(window.location.href);
                if (currentUrl.searchParams.has('reservation_sort')) {
                    url.searchParams.set('reservation_sort', currentUrl.searchParams.get('reservation_sort'));
                }
                if (!pageUrl) url.searchParams.delete('page');
            }
            url.hash = '#members';
            return url;
        }

        memberContent.addEventListener('submit', function(event) {
            const form = event.target;
            if (event.defaultPrevented) return;
            if (!(form instanceof HTMLFormElement)) return;
            if (form.id !== 'member-filter-form') {
                if (!form.action.includes('/users/')) return;
                event.preventDefault();
                memberStatus.classList.add('hidden');
                errorMessage.classList.add('hidden');
                const button = form.querySelector('button[type="submit"]');
                const originalText = button?.textContent;
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Saving...';
                }
                const requestBody = new FormData(form);
                form.querySelector('input[name="current_password"]')?.remove();
                delete form.dataset.adminPasswordConfirmed;

                fetch(form.action, {
                    method: form.method.toUpperCase(),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: requestBody,
                    credentials: 'same-origin',
                })
                    .then(async function(response) {
                        if (response.status === 419) throw new Error('Your session expired. Refresh this page and try again.');
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Member update failed.');
                        }
                        return data;
                    })
                    .then(function(data) {
                        showMessage(memberStatus, data.status || 'Member updated.');
                        loadMembers(membersUrl().toString(), false);
                    })
                    .catch(function(error) {
                        showMessage(errorMessage, error.message);
                    })
                    .finally(function() {
                        if (button) {
                            button.disabled = false;
                            button.textContent = originalText;
                        }
                    });
                return;
            }
            event.preventDefault();
            window.clearTimeout(searchTimer);
            loadMembers(membersUrl().toString(), 'push');
        });

        memberContent.addEventListener('input', function(event) {
            if (!(event.target instanceof HTMLInputElement) || event.target.name !== 'search') return;
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function() {
                loadMembers(membersUrl().toString(), 'replace');
            }, 300);
        });

        memberContent.addEventListener('change', function(event) {
            if (!(event.target instanceof HTMLSelectElement)
                || !['role', 'member_sort'].includes(event.target.name)
                || event.target.form?.id !== 'member-filter-form') return;
            loadMembers(membersUrl().toString(), 'push');
        });

        memberContent.addEventListener('click', function(event) {
            if (!(event.target instanceof Element)) return;
            const link = event.target.closest('nav[aria-label="Pagination Navigation"] a');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            loadMembers(link.href, true);
        });

        window.addEventListener('popstate', function() {
            loadMembers(window.location.href, false);
        });

        document.addEventListener('submit', function(event) {
            const form = event.target;
            if (event.defaultPrevented) return;
            if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-pending-action')) return;
            event.preventDefault();
            dashboardStatus.classList.add('hidden');
            dashboardError.classList.add('hidden');
            const button = form.querySelector('button[type="submit"]');
            const originalText = button?.textContent;
            if (button) {
                button.disabled = true;
                button.textContent = 'Saving...';
            }
            const requestBody = new FormData(form);
            form.querySelector('input[name="current_password"]')?.remove();
            delete form.dataset.adminPasswordConfirmed;

            fetch(form.action, {
                method: form.method.toUpperCase(),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: requestBody,
                credentials: 'same-origin',
            })
                .then(async function(response) {
                    if (response.status === 419) throw new Error('Your session expired. Refresh this page and try again.');
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.message || 'Reservation update failed.');
                    return data;
                })
                .then(function(data) {
                    const list = document.getElementById('pending-reservations-list');
                    const scrollTop = list?.scrollTop || 0;
                    form.closest('[data-reservation-card]')?.remove();
                    const updatedList = document.getElementById('pending-reservations-list');
                    if (updatedList) {
                        updatedList.scrollTop = scrollTop;
                        if (!updatedList.querySelector('[data-reservation-card]')) {
                            const emptyMessage = document.createElement('p');
                            emptyMessage.className = 'text-gray-500';
                            emptyMessage.textContent = data.pending_count === 0
                                ? 'No pending reservations at the moment.'
                                : 'No pending reservations on this page.';
                            updatedList.appendChild(emptyMessage);
                        }
                    }
                    document.getElementById('pending-reservations-total').textContent = data.pending_count;
                    document.getElementById('pending-reservations-label').textContent = `${data.pending_count} pending`;
                    document.getElementById('recent-decisions-total').textContent = data.recent_decision_count;
                    document.getElementById('audit-log-total').textContent = data.audit_log_count;

                    if (data.audit_log) {
                        const auditList = document.getElementById('audit-log-list');
                        const auditScrollTop = auditList.scrollTop;
                        auditList.querySelector('[data-empty-audit]')?.remove();
                        const entry = document.createElement('div');
                        entry.className = 'rounded-3xl border border-slate-200 bg-indigo-50/40 p-4';
                        const content = document.createElement('div');
                        content.className = 'flex items-start justify-between gap-4';
                        const details = document.createElement('div');
                        const timestamp = document.createElement('p');
                        timestamp.className = 'text-sm text-gray-500';
                        timestamp.textContent = data.audit_log.created_at;
                        const action = document.createElement('p');
                        action.className = 'mt-1 text-base font-semibold text-gray-900 capitalize';
                        action.textContent = data.audit_log.action;
                        const description = document.createElement('p');
                        description.className = 'mt-1 text-sm text-gray-700';
                        description.textContent = data.audit_log.details;
                        const actor = document.createElement('span');
                        actor.className = 'rounded-full bg-gray-200 px-3 py-1 text-xs font-semibold uppercase text-gray-700';
                        actor.textContent = data.audit_log.user;
                        details.append(timestamp, action, description);
                        content.append(details, actor);
                        entry.appendChild(content);
                        auditList.prepend(entry);
                        auditList.querySelectorAll(':scope > div').forEach(function(oldEntry, index) {
                            if (index >= 25) oldEntry.remove();
                        });
                        auditList.scrollTop = auditScrollTop;
                    }

                    showMessage(dashboardStatus, data.status || 'Reservation updated.');
                })
                .catch(function(error) {
                    showMessage(dashboardError, error.message);
                })
                .finally(function() {
                    if (button) {
                        button.disabled = false;
                        button.textContent = originalText;
                    }
                });
        });
    });
</script>
@endpush
