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
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Welcome Section -->
        <!-- Dashboard greeting and summary for the current logged in user. -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back, {{ Auth::user()->name }}!</h1>
            <p class="text-gray-600 mt-2">Manage your reservations and explore available facilities.</p>
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

        <!-- Quick Stats -->
        <!-- Summary cards showing the user's reservation and facility counts. -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Upcoming Reservations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $upcomingCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Completed</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $completedCount ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 bg-indigo-100 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

        <!-- Available Facilities -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Available Facilities</h2>
            @if($facilities->isEmpty())
                <div class="text-center py-12 text-gray-500">
                    No facilities are available right now.
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($facilities as $facility)
                        <div class="border border-gray-200 rounded-xl hover:shadow-md transition-shadow">
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-900 text-lg">{{ $facility->name }}</h3>
                                <p class="text-sm text-gray-600 mt-1">Capacity: {{ $facility->capacity ?? 'N/A' }} people</p>
                                <a href="/calendar/manage" class="mt-4 inline-flex w-full items-center justify-center rounded-lg bg-black px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 transition-colors">
                                    Reserve Now
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- My Reservations -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">My Reservations</h2>
            @if(isset($reservations) && $reservations->isNotEmpty())
                <form id="delete-selected-form" method="POST" action="{{ route('reservations.destroySelected') }}">
                    @csrf
                    <div id="reservationList" class="space-y-4">
                        @foreach($reservations as $reservation)
                            <div class="rounded-2xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500">{{ $reservation->facility->name ?? 'Facility' }}</p>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $reservation->purpose }}</h3>
                                        <p class="text-sm text-gray-600 mt-2">{{ $reservation->reservation_date->format('M d, Y') }} · {{ $reservation->start_time->format('h:i A') }} - {{ $reservation->end_time->format('h:i A') }}</p>
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

                <div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-8">
                    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
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

<div id="feedback-survey-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-8">
    <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-xl">
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
