@extends('layouts.app')

@section('title', 'Reservation Settings - Campus Reserve')

@section('content')
<div class="min-h-screen bg-[#f5f3f7]">
    <div class="flex min-h-screen flex-col md:flex-row">
        @include('admin.partials.sidebar')
        <main class="min-w-0 flex-1 px-3 py-5 sm:px-6 sm:py-8 xl:px-10">
            <div class="mx-auto max-w-5xl">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Reservation Settings</h1>
                    <p class="mt-2 text-gray-600">Set the daily time window when facility and property reservations are allowed.</p>
                </div>

                @if(session('status'))
                    <div class="mb-6 rounded-xl border border-green-300 bg-green-100 px-4 py-3 text-green-800" role="status">{{ session('status') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ session('error') }}</div>
                @endif
                @unless($settingsStorageAvailable)
                    <div class="mb-6 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-amber-900" role="alert">
                        Saving these settings is not available until the reservation settings database migration has been applied.
                    </div>
                @endunless
                @if($errors->has('current_password'))
                    <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">{{ $errors->first('current_password') }}</div>
                @endif
                @if($errors->has('facility_id') || $errors->has('maintenance_date') || $errors->has('is_all_day') || $errors->has('start_time') || $errors->has('end_time') || $errors->has('reason'))
                    <div class="mb-6 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-red-800" role="alert">
                        Please check the maintenance details below.
                    </div>
                @endif

                <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Allowed reservation hours</h2>
                        <p class="mt-1 text-sm text-gray-600">Reservations must start at or after opening and finish by closing. This applies to new requests, edits, and approvals.</p>
                    </div>

                    @if($settingsStorageAvailable)
                        <form method="POST" action="{{ route('admin.reservation-settings.update') }}" data-confirm-message="Save the reservation hours?" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="start_time" class="mb-2 block text-sm font-medium text-gray-700">Opening time</label>
                                    <input id="start_time" name="start_time" type="time" value="{{ old('start_time', $reservationHours['start_time']) }}" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                    @error('start_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="end_time" class="mb-2 block text-sm font-medium text-gray-700">Closing time</label>
                                    <input id="end_time" name="end_time" type="time" value="{{ old('end_time', $reservationHours['end_time']) }}" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                    @error('end_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label for="current_password" class="mb-2 block text-sm font-medium text-gray-700">Confirm password</label>
                                <div class="relative w-full sm:max-w-md">
                                    <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                </div>
                                @error('current_password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="flex justify-end border-t border-gray-100 pt-5">
                                <button type="submit" class="rounded-full bg-green-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Save reservation hours</button>
                            </div>
                        </form>
                    @else
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">The current default reservation window is midnight to 11:59 PM. You can edit it after the database migration is applied.</p>
                    @endif
                </section>

                <section class="mt-6 rounded-3xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Facility maintenance</h2>
                        <p class="mt-1 text-sm text-gray-600">Mark a room unavailable for a day or specific time. Overlapping pending and approved reservations will be rejected and their owners notified.</p>
                    </div>

                    @unless($maintenanceStorageAvailable)
                        <p class="mb-6 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                            Maintenance scheduling is unavailable until its database migration has been applied.
                        </p>
                    @endunless

                    @if($maintenanceStorageAvailable)
                        <form method="POST" action="{{ route('admin.reservation-settings.maintenance.store') }}" class="space-y-5">
                            @csrf
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="maintenance_facility_id" class="mb-2 block text-sm font-medium text-gray-700">Room or facility</label>
                                    <select id="maintenance_facility_id" name="facility_id" required class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                        <option value="">Select a facility</option>
                                        @foreach($facilities as $facility)
                                            <option value="{{ $facility->id }}" @selected(old('facility_id') == $facility->id)>{{ $facility->name }}{{ $facility->building ? ' — '.$facility->building : '' }}</option>
                                        @endforeach
                                    </select>
                                    @error('facility_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="maintenance_date" class="mb-2 block text-sm font-medium text-gray-700">Date</label>
                                    <input id="maintenance_date" name="maintenance_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('maintenance_date') }}" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                    @error('maintenance_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <input type="hidden" name="is_all_day" value="0">
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700">
                                    <input id="maintenance_all_day" name="is_all_day" type="checkbox" value="1" @checked(old('is_all_day')) class="rounded border-gray-300 text-green-800 focus:ring-green-700">
                                    Unavailable all day
                                </label>
                                @error('is_all_day')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div id="maintenance_time_fields" class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label for="maintenance_start_time" class="mb-2 block text-sm font-medium text-gray-700">Unavailable from</label>
                                    <input id="maintenance_start_time" name="start_time" type="time" value="{{ old('start_time', $reservationHours['start_time']) }}" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                    @error('start_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <label for="maintenance_end_time" class="mb-2 block text-sm font-medium text-gray-700">Unavailable until</label>
                                    <input id="maintenance_end_time" name="end_time" type="time" value="{{ old('end_time', $reservationHours['end_time']) }}" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                    @error('end_time')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div>
                                <label for="maintenance_reason" class="mb-2 block text-sm font-medium text-gray-700">Reason</label>
                                <input id="maintenance_reason" name="reason" type="text" value="{{ old('reason') }}" maxlength="255" required placeholder="e.g. Electrical maintenance" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                @error('reason')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="maintenance_current_password" class="mb-2 block text-sm font-medium text-gray-700">Confirm password</label>
                                <div class="relative w-full sm:max-w-md">
                                    <input id="maintenance_current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                </div>
                            </div>
                            <div class="flex justify-end border-t border-gray-100 pt-5">
                                <button type="submit" class="rounded-full bg-green-800 px-6 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Block availability</button>
                            </div>
                        </form>
                    @endif

                    <div class="mt-8 border-t border-gray-100 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900">Scheduled maintenance</h3>
                        <div class="mt-4 space-y-3">
                            @forelse($maintenanceBlocks as $block)
                                <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $block->facility->name }} — {{ $block->maintenance_date->format('M j, Y') }}</p>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $block->is_all_day ? 'All day' : date('g:i A', strtotime($block->start_time)).'–'.date('g:i A', strtotime($block->end_time)) }}
                                            · {{ $block->reason }}
                                        </p>
                                    </div>
                                    <form method="POST" action="{{ route('admin.reservation-settings.maintenance.destroy', $block) }}" class="flex items-center gap-3" data-confirm-message="Remove this maintenance block?">
                                        @csrf
                                        @method('DELETE')
                                        <label class="sr-only" for="maintenance_delete_password_{{ $block->id }}">Confirm password</label>
                                        <input id="maintenance_delete_password_{{ $block->id }}" name="current_password" type="password" required autocomplete="current-password" placeholder="Confirm password" class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm sm:w-44">
                                        <button type="submit" class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                                    </form>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">No upcoming maintenance blocks.</p>
                            @endforelse
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var allDayInput = document.getElementById('maintenance_all_day');
        var timeFields = document.getElementById('maintenance_time_fields');
        var startInput = document.getElementById('maintenance_start_time');
        var endInput = document.getElementById('maintenance_end_time');

        if (!allDayInput || !timeFields || !startInput || !endInput) return;

        function updateMaintenanceTimeFields() {
            timeFields.classList.toggle('hidden', allDayInput.checked);
            startInput.disabled = allDayInput.checked;
            endInput.disabled = allDayInput.checked;
            startInput.required = !allDayInput.checked;
            endInput.required = !allDayInput.checked;
        }

        allDayInput.addEventListener('change', updateMaintenanceTimeFields);
        updateMaintenanceTimeFields();
    });
</script>
@endpush
