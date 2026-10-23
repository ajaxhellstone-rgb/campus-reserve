@extends('layouts.app')

@section('title', 'Edit Reservation')

@section('content')
<div class="min-h-screen bg-slate-50 py-16">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm rounded-3xl border border-slate-200 p-8">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <p class="text-sm uppercase tracking-[0.3em] text-slate-500">Edit reservation</p>
                    <h1 class="text-3xl font-semibold text-slate-900 mt-3">Update your booking request</h1>
                </div>
                <div class="text-right">
                    <p class="text-sm text-slate-500">Current status</p>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $reservation->statusBadge['class'] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ $reservation->statusBadge['label'] ?? ucfirst($reservation->status) }}
                    </span>
                </div>
            </div>

            @if(session('error'))
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                    {{ session('error') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-4 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-rose-700">
                    <p class="font-semibold">Please fix the following errors:</p>
                    <ul class="mt-2 list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('reservations.update', $reservation) }}">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6">
                    @if($reservation->items->isNotEmpty())
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-sm font-medium text-slate-700">Independent property reservation</p>
                            <p class="mt-1 text-sm text-slate-600">
                                Items:
                                @foreach($reservation->items as $item)
                                    {{ $item->name }} × {{ $item->pivot->quantity }}@if(!$loop->last), @endif
                                @endforeach
                            </p>
                        </div>
                    @else
                        <div>
                            <label for="facility_id" class="block text-sm font-medium text-slate-700 mb-2">Facility</label>
                            <select id="facility_id" name="facility_id" required class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-slate-400 focus:outline-none">
                                @foreach($facilities as $facility)
                                    <option value="{{ $facility->id }}" {{ $reservation->facility_id === $facility->id ? 'selected' : '' }}>{{ $facility->name }} @if($facility->building) - {{ $facility->building }}@endif</option>
                                @endforeach
                            </select>
                            @error('facility_id')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div>
                            <label for="reservation_date" class="block text-sm font-medium text-slate-700 mb-2">Date</label>
                            <input id="reservation_date" name="reservation_date" type="date" max="{{ $maximumReservationDate }}" value="{{ old('reservation_date', $reservation->reservation_date->toDateString()) }}" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-slate-400 focus:outline-none" />
                            @error('reservation_date')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="start_time" class="block text-sm font-medium text-slate-700 mb-2">Start time</label>
                            <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $reservation->start_time->format('H:i')) }}" class="w-full h-12 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700 focus:border-slate-400 focus:outline-none" />
                            @error('start_time')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="end_time" class="block text-sm font-medium text-slate-700 mb-2">End time</label>
                            <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $reservation->end_time->format('H:i')) }}" class="w-full h-12 rounded-3xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700 focus:border-slate-400 focus:outline-none" />
                            @error('end_time')
                                <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label for="purpose" class="block text-sm font-medium text-slate-700 mb-2">Purpose</label>
                        <input id="purpose" name="purpose" type="text" value="{{ old('purpose', $reservation->purpose) }}" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-slate-400 focus:outline-none" />
                        @error('purpose')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-slate-700 mb-2">Notes (optional)</label>
                        <textarea id="notes" name="notes" rows="4" class="w-full rounded-3xl border border-slate-200 bg-slate-50 px-4 py-3 text-slate-700 focus:border-slate-400 focus:outline-none">{{ old('notes', $reservation->notes) }}</textarea>
                        @error('notes')
                            <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-3 sm:items-center">
                    <a href="{{ route('reserve') }}" class="inline-flex items-center justify-center rounded-3xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Cancel</a>
                    <button type="submit" class="inline-flex items-center justify-center rounded-3xl bg-sky-700 px-6 py-3 text-sm font-semibold text-white hover:bg-sky-600 transition">Save and resend for approval</button>
                    <form method="POST" action="{{ route('reservations.destroy', $reservation) }}" onsubmit="return confirm('Delete this reservation?');" class="w-full">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-3xl border border-rose-200 bg-white px-6 py-3 text-sm font-semibold text-rose-700 hover:bg-rose-50 transition">Delete reservation</button>
                    </form>
                </div>
            </form>

            <p class="mt-4 text-sm text-slate-500">Editing an approved reservation will revert it to a pending approval request.</p>
        </div>
    </div>
</div>
@endsection
