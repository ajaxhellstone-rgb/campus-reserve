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

    .reservation-options-scroll {
        max-height: 32rem;
        overflow-y: auto;
        padding-right: 0.5rem;
        scrollbar-color: #fbbf24 #fffbeb;
        scrollbar-width: thin;
    }

    .reservation-options-scroll::-webkit-scrollbar {
        width: 8px;
    }

    .reservation-options-scroll::-webkit-scrollbar-track {
        background: #fffbeb;
        border-radius: 9999px;
    }

    .reservation-options-scroll::-webkit-scrollbar-thumb {
        background: #fbbf24;
        border-radius: 9999px;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50">
    <div class="mx-auto max-w-7xl px-3 py-6 sm:px-6 sm:py-10 lg:px-8 lg:py-12">
        <!-- Welcome Section -->
        <!-- Dashboard greeting and summary for the current logged in user. -->
        <div class="relative mb-8 overflow-hidden rounded-3xl border border-white/80 bg-gradient-to-r from-indigo-100/90 via-pink-50 to-cyan-100/80 p-7 shadow-sm sm:p-9">
            <div aria-hidden="true" class="absolute -right-10 -top-20 h-56 w-56 rounded-full bg-white/50 blur-3xl"></div>
            <p class="relative text-sm font-semibold uppercase tracking-[0.16em] text-indigo-700">Campus Reserve</p>
            <h1 class="relative mt-2 text-3xl font-semibold tracking-tight text-slate-950">Welcome, {{ Auth::user()->name }}!</h1>
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
        <div id="facility-reservation-statistics" data-reservation-statistics="facility" class="mb-6 grid grid-cols-2 gap-3 sm:mb-8 sm:gap-6 md:grid-cols-3">
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:p-6">
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

            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:p-6">
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

            <div class="col-span-2 rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:col-span-1 sm:p-6">
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
        <div id="property-reservation-statistics" data-reservation-statistics="property" class="mb-6 grid grid-cols-2 gap-3 sm:mb-8 sm:gap-6">
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:p-6">
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
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-sm sm:p-6">
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

        <div class="mb-8 grid grid-cols-1 gap-5 lg:grid-cols-2">
            <section id="facility-reservation-panel" class="min-w-0 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-semibold text-gray-900">Available Facilities</h2>
                    <button type="button" id="facility-reserve-now" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2">
                        Reserve Now
                    </button>
                </div>
                <div class="mb-5">
                    <label class="mt-4 block">
                        <span class="sr-only">Search available facilities</span>
                        <input id="available-facilities-search" type="search" placeholder="Search rooms or buildings" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" autocomplete="off">
                    </label>
                </div>
                @if($facilities->isEmpty())
                    <div id="available-facilities-empty" class="py-12 text-center text-gray-500">No facilities are available right now.</div>
                @else
                    <div id="available-facilities-list" class="reservation-options-scroll space-y-3" tabindex="0" aria-label="Available facilities">
                        @foreach($facilities as $facility)
                            <div data-available-facility-card data-search="{{ strtolower($facility->name . ' ' . ($facility->building ?? '') . ' ' . $facility->typeLabel . ' ' . ($facility->capacity ?? '')) }}" class="flex items-center justify-between gap-4 rounded-full border border-slate-100 bg-white px-5 py-4 shadow-sm transition hover:border-indigo-100 hover:shadow-md">
                                <div class="min-w-0">
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $facility->name }}</h3>
                                    @if($facility->building)
                                        <p class="mt-1 text-sm text-gray-500">{{ $facility->building }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right text-sm text-gray-600">
                                    <p>{{ $facility->typeLabel }}</p>
                                    <p>Capacity: {{ $facility->capacity ?? 'N/A' }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p id="available-facilities-no-results" class="hidden py-8 text-center text-sm text-slate-500" role="status">No rooms match your search.</p>
                @endif
            </section>

            <section id="property-reservation-panel" class="min-w-0 overflow-hidden rounded-3xl border border-slate-200/80 bg-white/95 p-4 shadow-sm shadow-indigo-950/5 sm:p-6">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <h2 class="text-xl font-semibold text-gray-900">Available Property Items</h2>
                    <button type="button" id="property-reserve-now" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50" disabled>
                        Reserve Now
                    </button>
                </div>
                <label class="mb-4 block">
                    <span class="sr-only">Search available property items</span>
                    <input id="available-properties-search" type="search" placeholder="Search property items" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" autocomplete="off">
                </label>
                <div id="available-properties-list"
                    data-items-url="{{ route('api.facilities.property-items') }}"
                    class="reservation-options-scroll grid grid-cols-1 gap-5 lg:grid-cols-2"
                    tabindex="0"
                    aria-label="Available property items"
                    aria-live="polite">
                    <p class="py-8 text-center text-sm text-slate-500">Loading property items...</p>
                </div>
            </section>
        </div>

        <div id="facility-calendar-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-slate-950/40 p-2 backdrop-blur-sm sm:p-4" role="dialog" aria-modal="true" aria-labelledby="facility-calendar-modal-title">
            <div class="flex h-[99dvh] max-h-[99dvh] w-full max-w-[96rem] flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white p-3 shadow-2xl sm:p-5">
                <div class="mb-3 flex shrink-0 items-center justify-between gap-3">
                    <h2 id="facility-calendar-modal-title" class="text-lg font-semibold text-slate-900 sm:text-xl">Reserve a facility</h2>
                    <button type="button" id="facility-calendar-close" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-2xl leading-none text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-600" aria-label="Close facility reservation calendar">&times;</button>
                </div>
                <iframe
                    id="facility-calendar-frame"
                    data-src="{{ route('calendar.manage', ['embedded' => 1]) }}"
                    title="Facility reservation calendar"
                    class="min-h-0 w-full flex-1 rounded-2xl border border-slate-100 bg-[#f3f7f4]"
                    loading="lazy">
                </iframe>
            </div>
        </div>

        <div id="property-booking-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/40 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="property-booking-title">
            <div class="my-auto w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-medium text-slate-500">Property reservation</p>
                        <h2 id="property-booking-title" class="mt-1 text-2xl font-semibold text-slate-900">Reserve property items</h2>
                        <p id="property-booking-facility" class="mt-1 text-sm text-slate-600">Property inventory is independent of rooms and facilities.</p>
                    </div>
                    <button type="button" id="property-booking-close" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-900" aria-label="Close reservation dialog">&times;</button>
                </div>

                <form id="property-booking-form" class="mt-6 space-y-5">
                    <div>
                        <label for="property-booking-purpose" class="block text-sm font-medium text-slate-700">Purpose</label>
                        <input id="property-booking-purpose" type="text" maxlength="255" required placeholder="e.g. club meeting" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                    </div>
                    <div>
                        <label for="property-booking-item-select" class="block text-sm font-medium text-slate-700">Property item</label>
                        <select id="property-booking-item-select" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500"></select>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label for="property-booking-date" class="block text-sm font-medium text-slate-700">Date</label>
                            <input id="property-booking-date" type="date" max="{{ $maximumReservationDate }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                        <div>
                            <label for="property-booking-start" class="block text-sm font-medium text-slate-700">Time to use</label>
                            <input id="property-booking-start" type="time" value="{{ $reservationHours['start_time'] }}" min="{{ $reservationHours['start_time'] }}" max="{{ $reservationHours['end_time'] }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                        <div>
                            <label for="property-booking-end" class="block text-sm font-medium text-slate-700">Time to return</label>
                            <input id="property-booking-end" type="time" value="{{ $reservationHours['end_time'] }}" min="{{ $reservationHours['start_time'] }}" max="{{ $reservationHours['end_time'] }}" required class="mt-2 h-12 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-700 focus:border-slate-500 focus:outline-none focus:ring-1 focus:ring-slate-500">
                        </div>
                    </div>
                    <p class="text-xs font-medium text-green-900">
                        Reservations allowed {{ date('g:i A', strtotime($reservationHours['start_time'])) }}–{{ date('g:i A', strtotime($reservationHours['end_time'])) }}.
                    </p>

                    <div>
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-sm font-semibold text-slate-900">Selected item and quantity</h3>
                            <p class="text-xs text-slate-500">Available quantity updates for the selected time.</p>
                        </div>
                        <div id="property-booking-items" class="mt-3 max-h-56 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 p-3"></div>
                    </div>

                    <p id="property-booking-error" class="hidden whitespace-pre-line rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert"></p>
                    <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                        <button type="button" id="property-booking-cancel" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Cancel</button>
                        <button type="submit" id="property-booking-submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">Reserve Item</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- My Reservations -->
        <div class="rounded-3xl border border-slate-200/80 bg-white/95 p-6 shadow-sm shadow-indigo-950/5">
            <h2 class="mb-4 text-xl font-semibold tracking-tight text-slate-950">My Reservations</h2>
            <form method="GET" action="{{ route('reserve') }}" class="mb-5 flex flex-col gap-3 sm:flex-row">
                <input type="hidden" name="tab" value="{{ request('tab') }}">
                <label class="block min-w-0 flex-1">
                    <span class="sr-only">Search my reservations</span>
                    <input type="search" name="reservation_search" value="{{ $reservationSearch ?? '' }}" placeholder="Search by room, purpose, status, or item" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-100" autocomplete="off">
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl bg-indigo-700 px-5 py-3 text-sm font-semibold text-white transition hover:bg-indigo-800">Search</button>
                    @if(!empty($reservationSearch))
                        <a href="{{ route('reserve', request('tab') ? ['tab' => request('tab')] : []) }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
                    @endif
                </div>
            </form>
            @if(isset($reservations) && $reservations->isNotEmpty())
                <form id="delete-selected-form" method="POST" action="{{ route('reservations.destroySelected') }}">
                    @csrf
                    <div id="reservationList" class="space-y-4">
                        @foreach($reservations as $reservation)
                            <div class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-indigo-200 hover:shadow-md">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $reservation->items->isNotEmpty() ? 'Property items' : ($reservation->facility->name ?? 'Facility') }}</p>
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

                <div class="mt-6">
                    {{ $reservations->links() }}
                </div>

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
                    @if(!empty($reservationSearch))
                        <p>No reservations match “{{ $reservationSearch }}”.</p>
                        <p class="text-sm">Try a different room, purpose, status, or item name.</p>
                    @else
                        <p>No reservations yet.</p>
                        <p class="text-sm">Start by reserving a facility above!</p>
                    @endif
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
        const reservationOpenTime = @json($reservationHours['start_time']);
        const reservationCloseTime = @json($reservationHours['end_time']);
        const propertiesList = document.getElementById('available-properties-list');
        const facilityCalendarModal = document.getElementById('facility-calendar-modal');
        const facilityCalendarFrame = document.getElementById('facility-calendar-frame');
        const facilityCalendarOpenButton = document.getElementById('facility-reserve-now');
        const facilityCalendarCloseButton = document.getElementById('facility-calendar-close');
        const propertyReserveButton = document.getElementById('property-reserve-now');
        const facilitiesSearch = document.getElementById('available-facilities-search');
        const propertiesSearch = document.getElementById('available-properties-search');
        const facilityCards = Array.from(document.querySelectorAll('[data-available-facility-card]'));
        const facilityNoResults = document.getElementById('available-facilities-no-results');
        let propertyItems = [];
        let propertyItemsLoaded = false;
        let propertyItemsRequest = null;

        function openFacilityCalendar() {
            facilityCalendarModal.classList.remove('hidden');
            facilityCalendarModal.classList.add('flex');
            facilityCalendarModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');

            if (!facilityCalendarFrame.hasAttribute('src')) {
                facilityCalendarFrame.src = facilityCalendarFrame.dataset.src;
            } else {
                facilityCalendarFrame.contentWindow?.postMessage('facility-calendar:resize', window.location.origin);
            }
            facilityCalendarCloseButton.focus();
        }

        function closeFacilityCalendar() {
            facilityCalendarModal.classList.add('hidden');
            facilityCalendarModal.classList.remove('flex');
            facilityCalendarModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
            facilityCalendarOpenButton.focus();
        }

        facilityCalendarOpenButton.addEventListener('click', openFacilityCalendar);
        facilityCalendarCloseButton.addEventListener('click', closeFacilityCalendar);
        facilityCalendarModal.addEventListener('click', (event) => {
            if (event.target === facilityCalendarModal) {
                closeFacilityCalendar();
            }
        });
        facilityCalendarFrame.addEventListener('load', () => {
            if (!facilityCalendarModal.classList.contains('hidden')) {
                facilityCalendarFrame.contentWindow?.postMessage('facility-calendar:resize', window.location.origin);
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !facilityCalendarModal.classList.contains('hidden')) {
                closeFacilityCalendar();
            }
        });

        if (new URLSearchParams(window.location.search).has('open_facility_calendar')) {
            openFacilityCalendar();
        }

        function filterFacilities() {
            if (!facilitiesSearch) return;
            const query = facilitiesSearch.value.trim().toLowerCase();
            let visibleCount = 0;

            facilityCards.forEach((card) => {
                const matches = (card.dataset.search || '').includes(query);
                card.classList.toggle('hidden', !matches);
                if (matches) visibleCount += 1;
            });

            facilityNoResults?.classList.toggle('hidden', visibleCount > 0 || facilityCards.length === 0);
        }

        function filterPropertyItems() {
            if (!propertiesSearch || !propertyItemsLoaded) return;
            const query = propertiesSearch.value.trim().toLowerCase();
            let visibleCount = 0;

            propertiesList.querySelectorAll('[data-property-item-card]').forEach((card) => {
                const matches = (card.dataset.itemSearch || '').includes(query);
                card.classList.toggle('hidden', !matches);
                if (matches) visibleCount += 1;
            });

            const noResults = propertiesList.querySelector('[data-property-no-results]');
            if (noResults) noResults.classList.toggle('hidden', visibleCount > 0);
        }

        facilitiesSearch?.addEventListener('input', filterFacilities);
        propertiesSearch?.addEventListener('input', filterPropertyItems);

        async function loadPropertyItems() {
            if (propertyItemsRequest) {
                return propertyItemsRequest;
            }

            propertiesList.replaceChildren();
            const loadingMessage = document.createElement('p');
            loadingMessage.className = 'py-8 text-center text-sm text-slate-500';
            loadingMessage.textContent = 'Loading property items...';
            propertiesList.appendChild(loadingMessage);

            propertyItemsRequest = fetch(propertiesList.dataset.itemsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Property items could not be loaded.');
                    }
                    return response.json();
                })
                .then((items) => {
                    propertyItems = items;
                    propertyItemsLoaded = true;
                    propertyReserveButton.disabled = items.length === 0;
                    propertiesList.replaceChildren();

                    if (!items.length) {
                        const emptyMessage = document.createElement('p');
                        emptyMessage.className = 'col-span-full py-12 text-center text-gray-500';
                        emptyMessage.textContent = 'No property items are available right now.';
                        propertiesList.appendChild(emptyMessage);
                        return;
                    }

                    const noResultsMessage = document.createElement('p');
                    noResultsMessage.dataset.propertyNoResults = 'true';
                    noResultsMessage.className = 'col-span-full hidden py-8 text-center text-sm text-slate-500';
                    noResultsMessage.textContent = 'No property items match your search.';

                    items.forEach((item) => {
                        const card = document.createElement('article');
                        card.dataset.propertyItemCard = 'true';
                        card.dataset.itemSearch = item.name.toLowerCase();
                        card.className = 'rounded-3xl border border-slate-200 bg-white p-5 shadow-sm';

                        const name = document.createElement('h3');
                        name.className = 'text-lg font-semibold text-gray-900';
                        name.textContent = item.name;

                        const quantity = document.createElement('p');
                        quantity.className = 'mt-2 text-sm text-gray-500';
                        quantity.textContent = `${item.quantity} in stock`;
                        card.append(name, quantity);
                        propertiesList.appendChild(card);
                    });
                    propertiesList.appendChild(noResultsMessage);
                    filterPropertyItems();
                })
                .catch((error) => {
                    propertiesList.replaceChildren();
                    const errorMessage = document.createElement('p');
                    errorMessage.className = 'col-span-full py-8 text-center text-sm text-rose-700';
                    errorMessage.textContent = error.message || 'Property items could not be loaded.';
                    propertiesList.appendChild(errorMessage);

                    const retryButton = document.createElement('button');
                    retryButton.type = 'button';
                    retryButton.className = 'col-span-full mx-auto rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50';
                    retryButton.textContent = 'Try again';
                    retryButton.addEventListener('click', () => {
                        propertyItemsRequest = null;
                        loadPropertyItems();
                    });
                    propertiesList.appendChild(retryButton);
                })
                .finally(() => {
                    propertyItemsRequest = null;
                });

            return propertyItemsRequest;
        }

        loadPropertyItems();

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
        const itemSelect = document.getElementById('property-booking-item-select');
        const facilityLabel = document.getElementById('property-booking-facility');
        const dateInput = document.getElementById('property-booking-date');
        const startInput = document.getElementById('property-booking-start');
        const endInput = document.getElementById('property-booking-end');
        const submitButton = document.getElementById('property-booking-submit');
        const errorBox = document.getElementById('property-booking-error');
        let availabilityRequest = null;
        let availabilityVersion = 0;
        let modalItems = [];
        let preferredItemId = null;

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
            const previousQuantity = itemsContainer.querySelector(`[data-quantity-for="${preferredItemId}"]`)?.value;
            itemsContainer.replaceChildren();

            const selectedItem = availability.find((item) => String(item.id) === preferredItemId);
            if (!selectedItem) {
                const emptyMessage = document.createElement('p');
                emptyMessage.className = 'px-2 py-4 text-sm text-slate-500';
                emptyMessage.textContent = 'This property item is no longer available.';
                itemsContainer.appendChild(emptyMessage);
                return;
            }

            const row = document.createElement('div');
            row.className = 'flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2.5';

            const nameGroup = document.createElement('div');
            const name = document.createElement('p');
            name.className = 'text-sm font-medium text-slate-800';
            name.textContent = selectedItem.name;

            const available = document.createElement('p');
            available.className = 'text-xs text-slate-500';
            available.dataset.availableFor = String(selectedItem.id);
            available.textContent = `${selectedItem.available} available`;
            nameGroup.append(name, available);

            const quantity = document.createElement('input');
            quantity.type = 'number';
            quantity.min = '1';
            quantity.max = String(selectedItem.available);
            quantity.value = String(previousQuantity
                ? Math.min(Number(previousQuantity), selectedItem.available)
                : 1);
            quantity.disabled = selectedItem.available < 1;
            quantity.setAttribute('aria-label', `Quantity of ${selectedItem.name}`);
            quantity.className = 'w-24 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 disabled:bg-slate-100';
            quantity.dataset.quantityFor = String(selectedItem.id);

            row.append(nameGroup, quantity);
            itemsContainer.appendChild(row);
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
                const response = await fetch(`/api/property-items/availability?${query}`, {
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
                submitButton.disabled = !data.items.some((item) =>
                    String(item.id) === preferredItemId && item.available > 0
                );
            } catch (error) {
                if (error.name !== 'AbortError' && version === availabilityVersion) {
                    setBookingError(error.message || 'Could not check item availability. Please try again.');
                }
            }
        }

        function openPropertyBooking() {
            if (!propertyItemsLoaded || propertyItems.length === 0) {
                return;
            }

            const firstAvailableItem = propertyItems.find((item) => item.quantity > 0) || propertyItems[0];
            preferredItemId = String(firstAvailableItem.id);
            facilityLabel.textContent = 'Property inventory is independent of rooms and facilities.';
            modalItems = propertyItems;
            form.reset();
            itemsContainer.replaceChildren();
            itemSelect.replaceChildren();
            propertyItems.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = `${item.name} (${item.quantity} in stock)`;
                itemSelect.appendChild(option);
            });
            itemSelect.value = preferredItemId;
            const now = new Date();
            dateInput.value = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            startInput.value = reservationOpenTime;
            const [openHour, openMinute] = reservationOpenTime.split(':').map(Number);
            const defaultEnd = `${String(openHour + 1).padStart(2, '0')}:${String(openMinute).padStart(2, '0')}`;
            endInput.value = defaultEnd > reservationCloseTime ? reservationCloseTime : defaultEnd;
            setBookingError('');
            renderItems(modalItems.map((item) => ({ ...item, available: item.quantity })));
            setModalOpen(true);
            refreshAvailability();
            itemSelect.focus();
        }

        propertyReserveButton.addEventListener('click', openPropertyBooking);

        itemSelect.addEventListener('change', () => {
            preferredItemId = itemSelect.value;
            setBookingError('');
            renderItems(modalItems.map((item) => ({ ...item, available: item.quantity })));
            refreshAvailability();
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

            const quantityInput = itemsContainer.querySelector(`[data-quantity-for="${preferredItemId}"]`);
            if (!preferredItemId || !quantityInput || quantityInput.disabled) {
                setBookingError('This item is currently unavailable for the selected time.');
                return;
            }

            const quantity = Number(quantityInput.value);
            if (!Number.isInteger(quantity) || quantity < 1 || quantity > Number(quantityInput.max)) {
                setBookingError(`Enter a quantity from 1 to ${quantityInput.max}.`);
                return;
            }
            const selectedItems = [{
                facility_item_id: Number(preferredItemId),
                quantity,
            }];

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
                submitButton.textContent = 'Reserve Item';
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
