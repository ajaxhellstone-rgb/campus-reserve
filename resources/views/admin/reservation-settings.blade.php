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
                        <form method="POST" action="{{ route('admin.reservation-settings.update') }}" data-password-confirm class="space-y-6">
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
                            <input type="hidden" name="current_password">
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
                        <form method="POST" action="{{ route('admin.reservation-settings.maintenance.store') }}" data-password-confirm class="space-y-5">
                            @csrf
                            <input type="hidden" name="current_password">
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
                                    <div class="flex items-center gap-3">
                                        <button
                                            type="button"
                                            data-edit-maintenance
                                            data-edit-action="{{ route('admin.reservation-settings.maintenance.update', $block) }}"
                                            data-facility-id="{{ $block->facility_id }}"
                                            data-maintenance-date="{{ $block->maintenance_date->toDateString() }}"
                                            data-all-day="{{ $block->is_all_day ? '1' : '0' }}"
                                            data-start-time="{{ $block->start_time ? substr($block->start_time, 0, 5) : '' }}"
                                            data-end-time="{{ $block->end_time ? substr($block->end_time, 0, 5) : '' }}"
                                            data-reason="{{ $block->reason }}"
                                            class="rounded-xl border border-green-200 bg-white px-4 py-2 text-sm font-semibold text-green-800 hover:bg-green-50">
                                            Edit
                                        </button>
                                        <form method="POST" action="{{ route('admin.reservation-settings.maintenance.destroy', $block) }}" class="flex items-center gap-3" data-password-confirm>
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="current_password">
                                            <button type="submit" class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Remove</button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600">No upcoming maintenance blocks.</p>
                            @endforelse
                        </div>
                    </div>

                    <dialog id="maintenance-edit-dialog" class="w-full max-w-2xl rounded-3xl border border-slate-200 p-0 shadow-2xl backdrop:bg-slate-950/60">
                        <form id="maintenance-edit-form" method="POST" action="#" data-password-confirm class="space-y-5 p-6 sm:p-8">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="current_password">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h3 class="text-xl font-semibold text-slate-900">Edit scheduled maintenance</h3>
                                    <p class="mt-1 text-sm text-slate-600">Update the facility, date, time, or reason.</p>
                                </div>
                                <button id="maintenance-edit-close" type="button" class="rounded-full p-2 text-xl leading-none text-slate-500 hover:bg-slate-100" aria-label="Close maintenance editor">&times;</button>
                            </div>
                            <div class="grid gap-5 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Room or facility
                                    <select id="maintenance-edit-facility" name="facility_id" required class="mt-2 w-full rounded-xl border border-gray-300 bg-white px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                        @foreach($facilities as $facility)
                                            <option value="{{ $facility->id }}">{{ $facility->name }}{{ $facility->building ? ' — '.$facility->building : '' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block text-sm font-medium text-gray-700">
                                    Date
                                    <input id="maintenance-edit-date" name="maintenance_date" type="date" min="{{ now()->toDateString() }}" required class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                </label>
                            </div>
                            <div>
                                <input type="hidden" name="is_all_day" value="0">
                                <label class="inline-flex items-center gap-3 text-sm font-medium text-gray-700">
                                    <input id="maintenance-edit-all-day" name="is_all_day" type="checkbox" value="1" class="rounded border-gray-300 text-green-800 focus:ring-green-700">
                                    Unavailable all day
                                </label>
                            </div>
                            <div id="maintenance-edit-time-fields" class="grid gap-5 sm:grid-cols-2">
                                <label class="block text-sm font-medium text-gray-700">
                                    Unavailable from
                                    <input id="maintenance-edit-start" name="start_time" type="time" required class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                </label>
                                <label class="block text-sm font-medium text-gray-700">
                                    Unavailable until
                                    <input id="maintenance-edit-end" name="end_time" type="time" required class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                                </label>
                            </div>
                            <label class="block text-sm font-medium text-gray-700">
                                Reason
                                <input id="maintenance-edit-reason" name="reason" type="text" maxlength="255" required class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
                            </label>
                            <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
                                <button id="maintenance-edit-cancel" type="button" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                                <button type="submit" class="rounded-xl bg-green-800 px-6 py-3 text-sm font-semibold text-white hover:bg-green-900">Save changes</button>
                            </div>
                        </form>
                    </dialog>
                </section>
            </div>
        </main>
    </div>
</div>
<dialog id="password_confirmation_dialog" class="fixed inset-0 m-auto w-[calc(100%-2rem)] max-w-md rounded-2xl p-0 shadow-2xl backdrop:bg-slate-950/50">
    <form id="password_confirmation_form" class="space-y-5 p-6">
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Confirm password</h2>
            <p class="mt-1 text-sm text-gray-600">Enter your password to continue with this change.</p>
        </div>
        <div>
            <label for="password_confirmation_input" class="mb-2 block text-sm font-medium text-gray-700">Password</label>
            <input id="password_confirmation_input" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:border-green-700 focus:outline-none focus:ring-1 focus:ring-green-700">
        </div>
        <div class="flex justify-end gap-3">
            <button id="password_confirmation_cancel" type="button" class="rounded-full border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="submit" class="rounded-full bg-green-800 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-900">Confirm</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var passwordDialog = document.getElementById('password_confirmation_dialog');
        var passwordForm = document.getElementById('password_confirmation_form');
        var passwordInput = document.getElementById('password_confirmation_input');
        var cancelPasswordButton = document.getElementById('password_confirmation_cancel');
        var pendingForm = null;

        document.querySelectorAll('form[data-password-confirm]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (form.dataset.passwordConfirmed === 'true') {
                    delete form.dataset.passwordConfirmed;
                    return;
                }

                event.preventDefault();
                pendingForm = form;
                passwordInput.value = '';
                passwordDialog.showModal();
                passwordInput.focus();
            });
        });

        passwordForm.addEventListener('submit', function(event) {
            event.preventDefault();
            if (!pendingForm || !passwordForm.reportValidity()) return;

            pendingForm.querySelector('input[name="current_password"]').value = passwordInput.value;
            pendingForm.dataset.passwordConfirmed = 'true';
            passwordDialog.close();
            pendingForm.requestSubmit();
            passwordInput.value = '';
            pendingForm = null;
        });

        cancelPasswordButton.addEventListener('click', function() {
            passwordDialog.close();
        });

        passwordDialog.addEventListener('close', function() {
            passwordInput.value = '';
            pendingForm = null;
        });

        function bindMaintenanceTimeFields(allDayInput, timeFields, startInput, endInput) {
            if (!allDayInput || !timeFields || !startInput || !endInput) return;

            function updateTimeFields() {
                timeFields.classList.toggle('hidden', allDayInput.checked);
                startInput.disabled = allDayInput.checked;
                endInput.disabled = allDayInput.checked;
                startInput.required = !allDayInput.checked;
                endInput.required = !allDayInput.checked;
            }

            allDayInput.addEventListener('change', updateTimeFields);
            updateTimeFields();
        }

        bindMaintenanceTimeFields(
            document.getElementById('maintenance_all_day'),
            document.getElementById('maintenance_time_fields'),
            document.getElementById('maintenance_start_time'),
            document.getElementById('maintenance_end_time')
        );

        var editDialog = document.getElementById('maintenance-edit-dialog');
        var editAllDayInput = document.getElementById('maintenance-edit-all-day');
        bindMaintenanceTimeFields(
            editAllDayInput,
            document.getElementById('maintenance-edit-time-fields'),
            document.getElementById('maintenance-edit-start'),
            document.getElementById('maintenance-edit-end')
        );

        document.querySelectorAll('[data-edit-maintenance]').forEach(function(button) {
            button.addEventListener('click', function() {
                document.getElementById('maintenance-edit-form').action = button.dataset.editAction;
                document.getElementById('maintenance-edit-facility').value = button.dataset.facilityId;
                document.getElementById('maintenance-edit-date').value = button.dataset.maintenanceDate;
                editAllDayInput.checked = button.dataset.allDay === '1';
                document.getElementById('maintenance-edit-start').value = button.dataset.startTime;
                document.getElementById('maintenance-edit-end').value = button.dataset.endTime;
                document.getElementById('maintenance-edit-reason').value = button.dataset.reason;
                editAllDayInput.dispatchEvent(new Event('change'));
                editDialog.showModal();
            });
        });

        document.getElementById('maintenance-edit-close').addEventListener('click', function() {
            editDialog.close();
        });
        document.getElementById('maintenance-edit-cancel').addEventListener('click', function() {
            editDialog.close();
        });
    });
</script>
@endpush
