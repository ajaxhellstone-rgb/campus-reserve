@extends('layouts.app')

@section('title', 'Reserve - Campus Reserve')

@push('styles')
<style>
    #reservationList {
        max-height: 26rem;
        overflow-y: scroll;
        padding-right: 0.5rem;
        scrollbar-color: #94a3b8 #f1f5f9;
        scrollbar-width: thin;
    }

    #reservationList::-webkit-scrollbar {
        width: 8px;
    }

    #reservationList::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 9999px;
    }

    #reservationList::-webkit-scrollbar-thumb {
        background: #94a3b8;
        border-radius: 9999px;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Welcome Section -->
        <!-- Dashboard greeting and summary for the current logged in user. -->
        <div class="relative mb-8 overflow-hidden rounded-3xl border border-white/80 bg-gradient-to-r from-indigo-100/90 via-pink-50 to-cyan-100/80 p-7 shadow-sm sm:p-9">
            <div aria-hidden="true" class="absolute -right-10 -top-20 h-56 w-56 rounded-full bg-white/50 blur-3xl"></div>
            <p class="relative text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Campus Reserve</p>
            <h1 class="relative mt-2 text-3xl font-semibold tracking-tight text-slate-950">Welcome back, {{ Auth::user()->name }}!</h1>
            <p class="relative mt-2 text-slate-600">Manage your reservations and explore available facilities.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900">
                {{ session('success') }}
            </div>
        @elseif(session('error'))
            <div class="mb-6 rounded-3xl border border-rose-200 bg-rose-50 p-4 text-rose-900">
                {{ session('error') }}
            </div>
        @endif
        <div id="property-booking-feedback" class="mb-6 hidden rounded-3xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-900" role="status"></div>

        <!-- Quick Stats -->
        <!-- Summary cards showing the user's reservation and facility counts. -->
        <div id="facility-reservation-statistics" data-reservation-statistics="facility" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="rounded-2xl bg-indigo-100 p-3">
                        <svg class="h-6 w-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Upcoming Reservations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $upcomingCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="rounded-2xl bg-emerald-100 p-3">
                        <svg class="h-6 w-6 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $completedCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="rounded-2xl bg-cyan-100 p-3">
                        <svg class="h-6 w-6 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Available Facilities</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $facilities->count() }}</p>
                    </div>
                </div>
            </div>

        </div>
        <div id="property-reservation-statistics" data-reservation-statistics="property" class="hidden grid grid-cols-1 gap-6 mb-8 sm:grid-cols-2" hidden>
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="rounded-2xl bg-indigo-100 p-3">
                        <svg class="h-6 w-6 text-indigo-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Borrowed Items</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $borrowedItemCount ?? 0 }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                <div class="flex items-center">
                    <div class="rounded-2xl bg-cyan-100 p-3">
                        <svg class="h-6 w-6 text-cyan-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Returned Items</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $returnedItemCount ?? 0 }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facility and property reservation tabs -->
        <div class="mb-8 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 shadow-sm shadow-indigo-950/5">
            <div class="flex border-b border-slate-200 px-6 pt-5" role="tablist" aria-label="Reservation type">
                <button id="facility-reservation-tab" type="button" role="tab" aria-selected="true" aria-controls="facility-reservation-panel" data-reservation-tab="facility" class="border-b-2 border-indigo-700 px-4 py-3 text-sm font-semibold text-indigo-800">
                    Reserve Facility
                </button>
                <button id="property-reservation-tab" type="button" role="tab" aria-selected="false" aria-controls="property-reservation-panel" data-reservation-tab="property" class="border-b-2 border-transparent px-4 py-3 text-sm font-semibold text-slate-500 hover:text-indigo-700">
                    Reserve Property
                </button>
            </div>

            <section id="facility-reservation-panel" role="tabpanel" aria-labelledby="facility-reservation-tab" class="p-6">
                <h2 class="mb-6 text-xl font-semibold text-gray-900">Available Facilities</h2>
                @if($facilities->isEmpty())
                    <div class="py-12 text-center text-gray-500">No facilities are available right now.</div>
                @else
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($facilities as $facility)
                            <div class="rounded-2xl border border-slate-200 bg-white p-1 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-md">
                                <div class="p-4">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $facility->name }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">Capacity: {{ $facility->capacity ?? 'N/A' }} people</p>
                                    <a href="/calendar/manage" class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800">
                                        Reserve Now
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section id="property-reservation-panel" role="tabpanel" aria-labelledby="property-reservation-tab" class="hidden p-6" hidden>
                <h2 class="mb-2 text-xl font-semibold text-gray-900">Available Room Items</h2>
                <p class="mb-6 text-sm text-gray-600">Choose a room to see its equipment and reserve item quantities for a date and time.</p>
                @if($facilities->isEmpty())
                    <div class="py-12 text-center text-gray-500">No facilities are available right now.</div>
                @else
                    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                        @foreach($facilities as $facility)
                            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-indigo-200 hover:shadow-md">
                                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $facility->name }}</h3>
                                        <p class="mt-1 text-sm text-gray-500">{{ $facility->building }}</p>
                                    </div>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700">
                                        {{ $facility->items->count() }} {{ \Illuminate\Support\Str::plural('item', $facility->items->count()) }}
                                    </span>
                                </div>
                                @if($facility->items->isEmpty())
                                    <p class="mt-4 rounded-xl bg-indigo-50/70 px-4 py-3 text-sm text-slate-600">No reservable items are listed for this room.</p>
                                @else
                                    <ul class="mt-4 divide-y divide-gray-100">
                                        @foreach($facility->items as $item)
                                            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                                                <span class="font-medium text-gray-700">{{ $item->name }}</span>
                                                <span class="text-gray-500">{{ $item->quantity }} in stock</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <button type="button"
                                        data-property-facility-id="{{ $facility->id }}"
                                        data-property-facility-name="{{ $facility->building }} — {{ $facility->name }}"
                                        data-property-items="{{ $facility->items->map(fn ($item) => ['id' => $item->id, 'name' => $item->name, 'quantity' => $item->quantity])->toJson() }}"
                                        class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-800">
                                        Reserve Items
                                    </button>
                                @endif
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div id="property-booking-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="property-booking-title">
            <div class="my-auto w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Property reservation</p>
                        <h2 id="property-booking-title" class="mt-1 text-2xl font-semibold text-slate-900">Reserve room items</h2>
                        <p id="property-booking-facility" class="mt-1 text-sm text-slate-600"></p>
                    </div>
                    <button type="button" id="property-booking-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Close reservation dialog">&times;</button>
                </div>

                <form id="property-booking-form" class="mt-6 space-y-5">
                    <input type="hidden" id="property-booking-facility-id">
                    <div>
                        <label for="property-booking-purpose" class="block text-sm font-medium text-slate-700">Purpose</label>
                        <input id="property-booking-purpose" type="text" maxlength="255" required placeholder="e.g. club meeting" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="property-booking-date" class="block text-sm font-medium text-slate-700">Date</label>
                            <input id="property-booking-date" type="date" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                        <div>
                            <label for="property-booking-start" class="block text-sm font-medium text-slate-700">Time to use</label>
                            <input id="property-booking-start" type="time" value="06:00" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                        <div>
                            <label for="property-booking-end" class="block text-sm font-medium text-slate-700">Time to return</label>
                            <input id="property-booking-end" type="time" value="07:00" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">Select items and quantities</h3>
                            <p class="text-xs text-slate-500">Available quantity updates for the selected time.</p>
                        </div>
                        <div id="property-booking-items" class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 p-3"></div>
                    </div>

                    <p id="property-booking-error" class="hidden whitespace-pre-line rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert"></p>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" id="property-booking-cancel" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" id="property-booking-submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Reserve Items</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- My Reservations -->
        <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm shadow-indigo-950/5">
            <h2 class="mb-4 text-xl font-semibold tracking-tight text-slate-950">My Reservations</h2>
            @if(isset($reservations) && $reservations->isNotEmpty())
                <form id="delete-selected-form" method="POST" action="{{ route('reservations.destroySelected') }}">
                    @csrf
                    <div id="reservationList" class="space-y-4">
                        @foreach($reservations as $reservation)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-200 hover:shadow-md">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $reservation->facility->name ?? 'Facility' }}</p>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $reservation->purpose }}</h3>
                                        <p class="text-sm text-gray-600 mt-2">
                                            {{ $reservation->reservation_date->format('M d, Y') }} ·
                                            @if($reservation->items->isNotEmpty())
                                                Use: {{ $reservation->start_time->format('h:i A') }} · Return: {{ $reservation->end_time->format('h:i A') }}
                                            @else
                                                {{ $reservation->start_time->format('h:i A') }} - {{ $reservation->end_time->format('h:i A') }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex flex-col items-start sm:items-end gap-3">
                                        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $reservation->statusBadge['class'] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $reservation->statusBadge['label'] ?? ucfirst($reservation->status) }}
                                        </span>
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                            <input type="checkbox" name="selected_reservations[]" value="{{ $reservation->id }}" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500" />
                                            Delete when selected
                                        </label>
                                    </div>
                                </div>
                                @if($reservation->notes)
                                    <p class="mt-4 text-sm text-gray-600">Notes: {{ $reservation->notes }}</p>
                                @endif
                                @if($reservation->items->isNotEmpty())
                                    <p class="mt-3 text-sm text-gray-600">
                                        Items requested:
                                        @foreach($reservation->items as $item)
                                            {{ $item->name }} × {{ $item->pivot->quantity }}@if(!$loop->last), @endif
                                        @endforeach
                                    </p>
                                @endif

                                <div class="mt-4 flex flex-wrap gap-3">
                                    @if(in_array($reservation->status, ['approved', 'rejected', 'pending']))
                                        <a href="{{ route('reservations.edit', $reservation) }}" class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 transition">Edit reservation</a>
                                    @endif
                                    @if($reservation->status === 'rejected' || $reservation->status === 'pending')
                                        <button type="button" class="inline-flex items-center rounded-full bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700 transition" onclick="openDeleteModal({{ $reservation->id }}, @json($reservation->facility->name ?? 'this reservation'))">Remove</button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6 flex justify-end">
                        <button type="button" onclick="openBulkDeleteModal()" class="inline-flex items-center rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700 transition">Delete selected</button>
                    </div>
                </form>
                @foreach($reservations as $reservation)
                    <form id="delete-reservation-{{ $reservation->id }}" method="POST" action="{{ route('reservations.destroy', $reservation) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endforeach

                <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/30 px-4 py-8 backdrop-blur-sm">
                    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
                        <h3 class="text-xl font-semibold text-gray-900">Confirm delete</h3>
                        <p class="mt-3 text-sm text-gray-600" id="delete-modal-message">Are you sure you want to remove this reservation?</p>
                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" class="rounded-full border border-gray-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-gray-50" onclick="closeDeleteModal()">No</button>
                            <button type="button" id="delete-modal-confirm" class="rounded-full bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700">Yes, delete</button>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <p>No reservations yet.</p>
                    <p class="text-sm">Start by reserving a facility above!</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div id="feedback-survey-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/30 px-4 py-8 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white p-6 shadow-2xl">
        <div class="flex items-start justify-between gap-3">
            <div>
                <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Quick feedback</p>
                <h3 class="mt-2 text-2xl font-semibold text-slate-900">How was your experience?</h3>
            </div>
        </div>

        <p class="mt-3 text-sm text-slate-600">We’d love to hear how Campus Reserve worked for you.</p>

        <div class="mt-5 flex justify-center gap-2" id="feedback-stars" aria-label="Rate your experience">
            <button type="button" class="feedback-star text-3xl text-slate-300 transition hover:scale-105" data-star="1" aria-label="1 star">★</button>
            <button type="button" class="feedback-star text-3xl text-slate-300 transition hover:scale-105" data-star="2" aria-label="2 stars">★</button>
            <button type="button" class="feedback-star text-3xl text-slate-300 transition hover:scale-105" data-star="3" aria-label="3 stars">★</button>
            <button type="button" class="feedback-star text-3xl text-slate-300 transition hover:scale-105" data-star="4" aria-label="4 stars">★</button>
            <button type="button" class="feedback-star text-3xl text-slate-300 transition hover:scale-105" data-star="5" aria-label="5 stars">★</button>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" id="feedback-no-thanks" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">No thanks</button>
            <button type="button" id="feedback-send" class="rounded-full bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Send feedback</button>
        </div>
    </div>
</div>
<script>
    (() => {
        const tabButtons = Array.from(document.querySelectorAll('[data-reservation-tab]'));
        const tabPanels = {
            facility: document.getElementById('facility-reservation-panel'),
            property: document.getElementById('property-reservation-panel'),
        };
        const tabStatistics = {
            facility: document.getElementById('facility-reservation-statistics'),
            property: document.getElementById('property-reservation-statistics'),
        };

        function selectReservationTab(tabName) {
            tabButtons.forEach((button) => {
                const selected = button.dataset.reservationTab === tabName;
                button.setAttribute('aria-selected', String(selected));
                button.classList.toggle('border-indigo-700', selected);
                button.classList.toggle('text-indigo-800', selected);
                button.classList.toggle('border-transparent', !selected);
                button.classList.toggle('text-slate-500', !selected);
                button.classList.toggle('hover:text-indigo-700', !selected);
            });

            Object.entries(tabPanels).forEach(([name, panel]) => {
                const selected = name === tabName;
                panel.hidden = !selected;
                panel.classList.toggle('hidden', !selected);
            });

            Object.entries(tabStatistics).forEach(([name, statistics]) => {
                const selected = name === tabName;
                statistics.hidden = !selected;
                statistics.classList.toggle('hidden', !selected);
            });
        }

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => selectReservationTab(button.dataset.reservationTab));
        });

        const requestedTab = new URLSearchParams(window.location.search).get('tab');
        if (requestedTab === 'property') {
            selectReservationTab('property');
        }

        const feedback = document.getElementById('property-booking-feedback');
        const savedFeedback = sessionStorage.getItem('propertyBookingFeedback');
        if (savedFeedback) {
            feedback.textContent = savedFeedback;
            feedback.classList.remove('hidden');
            sessionStorage.removeItem('propertyBookingFeedback');
        }

        const modal = document.getElementById('property-booking-modal');
        const form = document.getElementById('property-booking-form');
        const itemsContainer = document.getElementById('property-booking-items');
        const facilityIdInput = document.getElementById('property-booking-facility-id');
        const facilityLabel = document.getElementById('property-booking-facility');
        const dateInput = document.getElementById('property-booking-date');
        const startInput = document.getElementById('property-booking-start');
        const endInput = document.getElementById('property-booking-end');
        const submitButton = document.getElementById('property-booking-submit');
        const errorBox = document.getElementById('property-booking-error');
        let availabilityRequest = null;
        let availabilityVersion = 0;
        let modalItems = [];

        function setBookingError(message) {
            errorBox.textContent = message;
            errorBox.classList.toggle('hidden', !message);
        }

        function setModalOpen(open) {
            modal.classList.toggle('hidden', !open);
            modal.classList.toggle('flex', open);
            document.body.classList.toggle('overflow-hidden', open);
            if (!open && availabilityRequest) {
                availabilityRequest.abort();
                availabilityRequest = null;
            }
        }

        function renderItems(availability = modalItems) {
            const selectedQuantities = new Map(
                Array.from(itemsContainer.querySelectorAll('[data-item-id]'))
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => [
                        checkbox.dataset.itemId,
                        Number(itemsContainer.querySelector(`[data-quantity-for="${checkbox.dataset.itemId}"]`).value),
                    ])
            );
            itemsContainer.replaceChildren();

            if (!availability.length) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'px-2 py-4 text-sm text-slate-500';
                emptyMessage.textContent = 'No items are listed for this facility.';
                itemsContainer.appendChild(emptyMessage);
                return;
            }

            availability.forEach((item) => {
                const row = document.createElement('div');
                row.className = 'flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5';

                const itemInfo = document.createElement('div');
                itemInfo.className = 'flex min-w-0 items-center gap-3';

                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-500';
                checkbox.dataset.itemId = String(item.id);
                checkbox.disabled = item.available < 1;
                checkbox.checked = selectedQuantities.has(String(item.id)) && item.available > 0;

                const nameGroup = document.createElement('div');
                const name = document.createElement('p');
                name.className = 'text-sm font-medium text-slate-800';
                name.textContent = item.name;

                const available = document.createElement('p');
                available.className = 'text-xs text-slate-500';
                available.dataset.availableFor = String(item.id);
                available.textContent = `${item.available} available`;
                nameGroup.append(name, available);
                itemInfo.append(checkbox, nameGroup);

                const quantity = document.createElement('input');
                quantity.type = 'number';
                quantity.min = '1';
                quantity.max = String(item.available);
                const previousQuantity = selectedQuantities.get(String(item.id));
                quantity.value = String(previousQuantity ? Math.min(previousQuantity, item.available) : 1);
                quantity.disabled = !checkbox.checked;
                quantity.setAttribute('aria-label', `Quantity of ${item.name}`);
                quantity.className = 'w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 disabled:bg-slate-100';
                quantity.dataset.quantityFor = String(item.id);

                checkbox.addEventListener('change', () => {
                    quantity.disabled = !checkbox.checked || item.available < 1;
                });

                row.append(itemInfo, quantity);
                itemsContainer.appendChild(row);
            });
        }

        async function refreshAvailability() {
            availabilityVersion += 1;
            const version = availabilityVersion;
            if (availabilityRequest) {
                availabilityRequest.abort();
            }

            if (!dateInput.value || !startInput.value || !endInput.value || endInput.value <= startInput.value) {
                submitButton.disabled = true;
                setBookingError('Choose a valid date and time range to check item availability.');
                renderItems(modalItems.map((item) => ({ ...item, available: item.quantity })));
                return;
            }

            setBookingError('');
            submitButton.disabled = true;
            const controller = new AbortController();
            availabilityRequest = controller;
            const query = new URLSearchParams({
                date: dateInput.value,
                start_time: startInput.value,
                end_time: endInput.value,
            });

            try {
                const response = await fetch(`/api/facilities/${facilityIdInput.value}/item-availability?${query}`, {
                    credentials: 'include',
                    headers: { Accept: 'application/json' },
                    signal: controller.signal,
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Could not check item availability.');
                }
                if (version !== availabilityVersion) {
                    return;
                }

                renderItems(data.items);
                submitButton.disabled = false;
            } catch (error) {
                if (error.name !== 'AbortError' && version === availabilityVersion) {
                    setBookingError(error.message || 'Could not check item availability. Please try again.');
                }
            }
        }

        document.querySelectorAll('[data-property-facility-id]').forEach((button) => {
            button.addEventListener('click', () => {
                facilityIdInput.value = button.dataset.propertyFacilityId;
                facilityLabel.textContent = button.dataset.propertyFacilityName;
                modalItems = JSON.parse(button.dataset.propertyItems);
                form.reset();
                itemsContainer.replaceChildren();
                facilityIdInput.value = button.dataset.propertyFacilityId;
                const now = new Date();
                dateInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
                startInput.value = '06:00';
                endInput.value = '07:00';
                setBookingError('');
                renderItems(modalItems.map((item) => ({ ...item, available: item.quantity })));
                setModalOpen(true);
                refreshAvailability();
                document.getElementById('property-booking-purpose').focus();
            });
        });

        [dateInput, startInput, endInput].forEach((input) => {
            input.addEventListener('change', refreshAvailability);
        });

        document.getElementById('property-booking-close').addEventListener('click', () => setModalOpen(false));
        document.getElementById('property-booking-cancel').addEventListener('click', () => setModalOpen(false));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                setModalOpen(false);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                setModalOpen(false);
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setBookingError('');

            if (endInput.value <= startInput.value) {
                setBookingError('End time must be later than start time.');
                return;
            }

            const selectedItems = Array.from(itemsContainer.querySelectorAll('[data-item-id]:checked')).map((checkbox) => {
                const quantityInput = itemsContainer.querySelector(`[data-quantity-for="${checkbox.dataset.itemId}"]`);
                return {
                    facility_item_id: Number(checkbox.dataset.itemId),
                    quantity: Number(quantityInput.value),
                };
            });

            if (!selectedItems.length) {
                setBookingError('Select at least one item to reserve.');
                return;
            }

            if (selectedItems.some((item) => !Number.isInteger(item.quantity) || item.quantity < 1)) {
                setBookingError('Enter a quantity of at least one for each selected item.');
                return;
            }

            submitButton.disabled = true;
            submitButton.textContent = 'Submitting...';

            try {
                const response = await fetch('/api/calendar/reserve', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        facility: facilityIdInput.value,
                        purpose: document.getElementById('property-booking-purpose').value,
                        date: dateInput.value,
                        start_time: startInput.value,
                        end_time: endInput.value,
                        items: selectedItems,
                    }),
                });
                const data = await response.json();
                if (!response.ok) {
                    const validationError = data.errors ? Object.values(data.errors).flat()[0] : null;
                    const error = new Error(data.error || validationError || data.message || 'Reservation could not be submitted.');
                    error.code = data.code;
                    throw error;
                }

                sessionStorage.setItem('propertyBookingFeedback', 'Property reservation submitted and is pending approval.');
                const url = new URL(window.location.href);
                url.searchParams.set('tab', 'property');
                window.location.assign(url);
            } catch (error) {
                setBookingError(error.code === 'schedule_occupied'
                    ? 'Sorry!\nThis schedule and room is already occupied!\nPlease try another time or room!'
                    : error.message || 'Reservation could not be submitted. Please try again.');
            } finally {
                submitButton.disabled = false;
                submitButton.textContent = 'Reserve Items';
            }
        });
    })();

    let deleteReservationFormId = null;
    let deleteReservationMode = null;

    function openDeleteModal(reservationId, facilityName) {
        deleteReservationMode = 'single';
        deleteReservationFormId = reservationId;
        const modal = document.getElementById('delete-modal');
        const message = document.getElementById('delete-modal-message');
        message.textContent = `Are you sure you want to remove the reservation for ${facilityName}? This action cannot be undone.`;
        modal.classList.remove('hidden');
    }

    function openBulkDeleteModal() {
        deleteReservationMode = 'bulk';
        deleteReservationFormId = null;
        const modal = document.getElementById('delete-modal');
        const message = document.getElementById('delete-modal-message');
        message.textContent = 'Are you sure you want to delete the selected reservations? This action cannot be undone.';
        modal.classList.remove('hidden');
    }

    function closeDeleteModal() {
        deleteReservationMode = null;
        deleteReservationFormId = null;
        document.getElementById('delete-modal').classList.add('hidden');
    }

    document.getElementById('delete-modal-confirm')?.addEventListener('click', function () {
        if (deleteReservationMode === 'single' && deleteReservationFormId) {
            const form = document.getElementById(`delete-reservation-${deleteReservationFormId}`);
            if (form) {
                form.submit();
            }
        }

        if (deleteReservationMode === 'bulk') {
            const bulkForm = document.getElementById('delete-selected-form');
            if (bulkForm) {
                bulkForm.submit();
            }
        }
    });

    (function () {
        const storageKey = 'campusreserve_feedback_survey';
        const now = Date.now();
        const lastDismissed = Number(localStorage.getItem(`${storageKey}_dismissed_until`) || 0);
        const wasSubmitted = localStorage.getItem(`${storageKey}_submitted`) === '1';

        if (lastDismissed > now || wasSubmitted) {
            return;
        }

        const hasReservation = @json($reservations->isNotEmpty());
        const randomChance = Math.random() < 0.18;

        if (!hasReservation && !randomChance) {
            return;
        }

        const modal = document.getElementById('feedback-survey-modal');
        if (!modal) {
            return;
        }

        let selectedRating = 0;
        const stars = Array.from(document.querySelectorAll('.feedback-star'));

        stars.forEach((star) => {
            star.addEventListener('click', function () {
                selectedRating = Number(this.dataset.star);
                stars.forEach((item) => {
                    const itemValue = Number(item.dataset.star);
                    item.classList.toggle('text-yellow-400', itemValue <= selectedRating);
                    item.classList.toggle('text-slate-300', itemValue > selectedRating);
                });
            });
        });

        const dismissSurvey = (isSubmit = false) => {
            const suppressUntil = Date.now() + (1000 * 60 * 60 * 24 * 30);
            localStorage.setItem(`${storageKey}_dismissed_until`, String(suppressUntil));
            if (isSubmit) {
                localStorage.setItem(`${storageKey}_submitted`, '1');
            }
            modal.classList.add('hidden');
        };

        document.getElementById('feedback-no-thanks')?.addEventListener('click', () => dismissSurvey(false));
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                dismissSurvey(false);
            }
        });
        document.getElementById('feedback-send')?.addEventListener('click', () => {
            if (!selectedRating) {
                selectedRating = 5;
            }
            dismissSurvey(true);
        });

        setTimeout(() => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }, 1200);
    })();
</script>
@endsection
