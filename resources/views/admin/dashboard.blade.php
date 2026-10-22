@extends('layouts.app')

@section('title', 'Admin Dashboard - Campus Reserve')

@section('content')
<!-- Admin dashboard layout: pending approvals, audit logs, and user management sections. -->
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 py-6 sm:py-10">
    <div class="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8">
        <div class="relative mb-6 overflow-hidden rounded-3xl border border-white/80 bg-gradient-to-r from-indigo-100/90 via-pink-50 to-cyan-100/80 p-5 shadow-sm sm:mb-8 sm:p-9">
            <div aria-hidden="true" class="absolute -right-10 -top-20 h-56 w-56 rounded-full bg-white/50 blur-3xl"></div>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <p class="relative text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Campus Reserve</p>
                    <h1 class="relative mt-1 text-3xl font-semibold tracking-tight text-slate-950">Admin Dashboard</h1>
                    <p class="relative mt-2 text-slate-600">Approve or reject reservations, manage members, and review audit activity.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="{{ route('admin.facilities.create') }}" class="inline-flex items-center rounded-full bg-indigo-700 px-4 py-2 text-white transition-colors hover:bg-indigo-800">Facility Management</a>
                    <a href="{{ route('admin.property-management.index') }}" class="inline-flex items-center rounded-full bg-violet-700 px-4 py-2 text-white transition-colors hover:bg-violet-800">Property Management</a>
                    <a href="/reserve" class="inline-flex items-center rounded-full border border-indigo-200 bg-white/80 px-4 py-2 text-indigo-800 transition-colors hover:bg-white">Back to User Portal</a>
                </div>
            </div>
        </div>

        @if(session('status'))
            <div class="mb-6 bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded">{{ session('status') }}</div>
        @endif
        @if(session('error'))
            <div class="mb-6 bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded">{{ session('error') }}</div>
        @endif
        @if($errors->has('current_password'))
            <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ $errors->first('current_password') }}</div>
        @endif

        <div class="mb-6 grid grid-cols-2 gap-3 sm:mb-8 sm:gap-6 xl:grid-cols-4">
            <div class="rounded-3xl border border-indigo-100 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <p class="text-sm font-medium text-gray-500">Pending Reservations</p>
                <p id="pending-reservations-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $pendingReservations->total() }}</p>
            </div>
            <div class="rounded-3xl border border-pink-100 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <p class="text-sm font-medium text-gray-500">Recent Decisions</p>
                <p id="recent-decisions-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $recentReservations->count() }}</p>
            </div>
            <div class="rounded-3xl border border-cyan-100 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <p class="text-sm font-medium text-gray-500">Audit Log Entries</p>
                <p id="audit-log-total" class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $auditLogs->count() }}</p>
            </div>
            <div class="rounded-3xl border border-violet-100 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <p class="text-sm font-medium text-gray-500">Members</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 sm:mt-4 sm:text-3xl">{{ $users->total() ?? 0 }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
            <section class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6 xl:col-span-2">
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

            <section class="rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
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
                                            <p class="text-xs text-gray-500 mt-2">Reservation #{{ $log->reservation->id }} — {{ $log->reservation->facility->name }}</p>
                                        @endif
                                    </div>
                                    <span class="px-3 py-1 rounded-full bg-gray-200 text-xs font-semibold uppercase text-gray-700">{{ $log->user?->name ?? 'Admin' }}</span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </section>
        </div>

        <p id="member-management-status" class="mt-3 hidden text-sm text-emerald-700" role="status" aria-live="polite"></p>
        <p id="member-management-error" class="mt-3 hidden text-sm text-rose-700" role="alert"></p>
        <div id="member-management-content" data-endpoint="{{ route('admin.dashboard') }}" aria-busy="false">
            @include('admin.partials.members')
        </div>
    </div>
</div>
@include('admin.partials.password-confirmation')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const memberContent = document.getElementById('member-management-content');
        const errorMessage = document.getElementById('member-management-error');
        const memberStatus = document.getElementById('member-management-status');
        const dashboardStatus = document.getElementById('dashboard-action-status');
        const dashboardError = document.getElementById('dashboard-action-error');
        let activeRequest = null;
        let searchTimer = null;

        function showMessage(element, message) {
            element.textContent = message;
            element.classList.remove('hidden');
        }

        function setLoading(loading) {
            memberContent.setAttribute('aria-busy', String(loading));
            memberContent.classList.toggle('opacity-60', loading);
            memberContent.classList.toggle('pointer-events-none', loading);
        }

        function loadMembers(url, updateHistory) {
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
                    memberContent.innerHTML = html;
                    const updatedList = memberContent.querySelector('[aria-label="Scrollable member list"]');
                    if (updatedList) updatedList.scrollTop = previousScrollTop;
                    if (updateHistory) {
                        window.history.pushState({}, '', url);
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
                if (!pageUrl) url.searchParams.delete('page');
            }
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
            loadMembers(membersUrl().toString(), true);
        });

        memberContent.addEventListener('input', function(event) {
            if (!(event.target instanceof HTMLInputElement) || event.target.name !== 'search') return;
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(function() {
                loadMembers(membersUrl().toString(), true);
            }, 300);
        });

        memberContent.addEventListener('change', function(event) {
            if (!(event.target instanceof HTMLSelectElement) || event.target.name !== 'role') return;
            loadMembers(membersUrl().toString(), true);
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
