@extends('layouts.app')

@section('title', 'Reserve a Facility')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:gap-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-medium text-slate-500">Campus Reserve</p>
                    <h1 class="text-3xl font-semibold text-slate-900">Reserve a Facility</h1>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button data-view="timeGridWeek" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">Week</button>
                    <button data-view="dayGridMonth" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">Month</button>
                    <button data-view="listWeek" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">List</button>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[320px_minmax(0,1fr)]">
                <aside class="space-y-6">
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Booking panel</h2>
                            <button id="todayBtn" type="button" class="rounded-2xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 transition">Today</button>
                        </div>
                        <div class="space-y-4">
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <label for="monthDropdown" class="text-sm font-medium text-slate-700">Month</label>
                                <select id="monthDropdown" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700">
                                    <option value="0">January</option>
                                    <option value="1">February</option>
                                    <option value="2">March</option>
                                    <option value="3">April</option>
                                    <option value="4">May</option>
                                    <option value="5">June</option>
                                    <option value="6">July</option>
                                    <option value="7">August</option>
                                    <option value="8">September</option>
                                    <option value="9">October</option>
                                    <option value="10">November</option>
                                    <option value="11">December</option>
                                </select>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-4">
                                <label for="yearDropdown" class="text-sm font-medium text-slate-700">Year</label>
                                <select id="yearDropdown" class="mt-2 w-full rounded-2xl border border-slate-200 bg-white px-3 py-3 text-sm text-slate-700"></select>
                            </div>
                            <div class="rounded-3xl bg-white p-4 border border-slate-200">
                                <h3 class="text-sm font-semibold text-slate-900 mb-3">Ready to reserve</h3>
                                <p class="text-sm text-slate-600 mb-4">Click a date or time slot on the calendar, then complete your booking in the popup form.</p>
                                <button id="openModalBtn" type="button" class="w-full rounded-2xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Reserve selected day</button>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3">Booking tips</h2>
                        <ul class="space-y-3 text-sm text-slate-600">
                            <li>Click a date or time slot to start a new reservation.</li>
                            <li>Use the left panel to choose month and year.</li>
                            <li>Reserve as early as possible to keep rooms available.</li>
                        </ul>
                    </div>
                </aside>

                <main class="rounded-3xl bg-white p-6 shadow-sm border border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Reservation calendar</p>
                            <p class="text-sm text-slate-500">Select a date or time slot to open the booking form.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button data-view="dayGridMonth" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">Month</button>
                            <button data-view="timeGridWeek" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">Week</button>
                            <button data-view="listWeek" class="rounded-2xl border border-slate-200 bg-slate-100 px-4 py-2 text-sm text-slate-700 hover:bg-slate-200 transition">List</button>
                        </div>
                    </div>
                    <div id="calendar" class="rounded-3xl border border-slate-200"></div>
                </main>
            </div>
        </div>
    </div>
</div>

<div id="reservationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/10 backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-200" aria-hidden="true">
    <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-8 shadow-2xl transform transition-transform duration-200">
        <h2 class="text-2xl font-semibold text-slate-900 mb-3">Reserve a facility on <span id="modalDate"></span></h2>
        <form id="reservationForm" action="/api/calendar/reserve" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="date" id="reservationDate" />
            <div>
                <label for="facilitySelectModal" class="block text-sm font-medium text-slate-700">Facility</label>
                <select id="facilitySelectModal" name="facility" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" required></select>
            </div>
            <div>
                <label for="purpose" class="block text-sm font-medium text-slate-700">Purpose</label>
                <input type="text" id="purpose" name="purpose" placeholder="e.g. club meeting" class="mt-2 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700" required />
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="startTime" class="block text-sm font-medium text-slate-700">Start time</label>
                    <input type="time" id="startTime" name="startTime" value="06:00" class="mt-2 w-full h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700" required />
                </div>
                <div>
                    <label for="endTime" class="block text-sm font-medium text-slate-700">End time</label>
                    <input type="time" id="endTime" name="endTime" value="07:00" class="mt-2 w-full h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700" required />
                </div>
            </div>
            <section class="space-y-3 rounded-2xl border border-slate-200 bg-slate-50 p-4" aria-labelledby="facility-items-heading">
                <div>
                    <h3 id="facility-items-heading" class="text-sm font-semibold text-slate-900">Items included with this facility</h3>
                    <p class="mt-1 text-xs text-slate-600">Select any items you need for this reservation and choose the quantity.</p>
                </div>
                <div id="facilityItemOptions" class="space-y-2" aria-live="polite">
                    <p class="text-sm text-slate-500">Choose a facility to see its items.</p>
                </div>
            </section>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <div id="reservationValidationMessage" class="min-h-5 text-sm font-semibold text-rose-600"></div>
                <div class="flex gap-3">
                    <button type="button" id="closeModalBtn" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Reserve</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="errorModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/10 backdrop-blur-sm p-4 min-h-screen">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <div class="text-center">
            <p id="errorLabel" class="text-sm font-semibold text-red-600">Error</p>
            <h2 id="errorTitle" class="mt-3 text-2xl font-semibold text-slate-900">Reservation failed</h2>
            <p id="errorMessage" class="mt-4 whitespace-pre-line text-sm text-slate-600">An error occurred.</p>
        </div>
        <div class="mt-8 flex justify-center gap-3">
            <button id="closeErrorBtn" type="button" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Close</button>
        </div>
    </div>
</div>

<div id="successModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/10 backdrop-blur-sm p-4 min-h-screen">
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
        <div class="text-center">
            <p class="text-sm font-semibold text-emerald-600">Success</p>
            <h2 class="mt-3 text-2xl font-semibold text-slate-900">Reservation is created and now waiting for approval!</h2>
            <p class="mt-4 text-sm text-slate-600">Your booking has been submitted. You can return to the main calendar to review the reservation.</p>
        </div>
        <div class="mt-8 flex justify-center gap-3">
            <button id="redirectToCalendarBtn" type="button" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 transition">Go to Calendar</button>
            <button id="closeSuccessBtn" type="button" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Close</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link href="{{ asset('fullcalendar/main.css') }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('fullcalendar/index.global.min.js') }}" crossorigin="anonymous"></script>
<script src="{{ asset('fullcalendar/locales-all.global.min.js') }}" crossorigin="anonymous"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var monthDropdown = document.getElementById('monthDropdown');
        var yearDropdown = document.getElementById('yearDropdown');
        var todayBtn = document.getElementById('todayBtn');
        var openModalBtn = document.getElementById('openModalBtn');
        var reservationModal = document.getElementById('reservationModal');
        var successModal = document.getElementById('successModal');
        var closeSuccessBtn = document.getElementById('closeSuccessBtn');
        var redirectToCalendarBtn = document.getElementById('redirectToCalendarBtn');
        var errorModal = document.getElementById('errorModal');
        var closeErrorBtn = document.getElementById('closeErrorBtn');
        var errorLabel = document.getElementById('errorLabel');
        var errorTitle = document.getElementById('errorTitle');
        var errorMessage = document.getElementById('errorMessage');
        var modalDate = document.getElementById('modalDate');
        var facilitySelectModal = document.getElementById('facilitySelectModal');
        var facilityItemOptions = document.getElementById('facilityItemOptions');
        var facilityItemsRequest = null;
        var facilityItemsRequestVersion = 0;
        var facilityItemSelections = new Map();
        var selectedDate = null;
        var viewButtons = document.querySelectorAll('[data-view]');

        function toggleModal(show) {
            if (show) {
                reservationModal.classList.remove('opacity-0', 'pointer-events-none');
                reservationModal.classList.add('opacity-100');
            } else {
                reservationModal.classList.add('opacity-0', 'pointer-events-none');
                reservationModal.classList.remove('opacity-100');
            }
        }

        function populateFacilities(selected) {
            fetch('/api/facilities', { credentials: 'include' })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Could not load available facilities.');
                    }
                    return response.json();
                })
                .then(function(facilities) {
                    facilitySelectModal.innerHTML = '';

                    facilities.forEach(function(fac) {
                        var option = document.createElement('option');
                        option.value = fac.id;
                        option.textContent = fac.name;
                        if (selected && fac.name === selected) option.selected = true;
                        facilitySelectModal.appendChild(option);
                    });
                    refreshFacilityItems();
                })
                .catch(function() {
                    facilityItemOptions.textContent = 'Could not load facilities. Please close and try again.';
                });
        }

        var calendar = new window.FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            selectable: true,
            editable: false,
            events: function(info, successCallback, failureCallback) {
                var query = new URLSearchParams({
                    start: info.startStr.slice(0, 10),
                    end: info.endStr.slice(0, 10)
                });

                fetch('/api/calendar/events?' + query, { credentials: 'include' })
                    .then(function(response) {
                        if (!response.ok) {
                            throw new Error('Calendar events could not be loaded.');
                        }
                        return response.json();
                    })
                    .then(successCallback)
                    .catch(function(error) {
                        console.error(error);
                        failureCallback(error);
                    });
            },
            dateClick: function(info) {
                selectedDate = info.dateStr.split('T')[0];
                modalDate.textContent = selectedDate;
                reservationForm.reset();
                resetFacilityItemSelection();
                if (reservationDateInput) {
                    reservationDateInput.value = selectedDate;
                }
                var clickedTime = info.allDay ? null : (info.dateStr.match(/T(\d{2}:\d{2})/) || [])[1];
                setDefaultReservationTimes(clickedTime);
                resetReservationValidation();
                populateFacilities();
                toggleModal(true);
            }
        });

        calendar.render();

        var today = new Date();
        var currentYear = today.getFullYear();
        
        for (var y = currentYear - 5; y <= currentYear + 5; y++) {
            var opt = document.createElement('option');
            opt.value = y;
            opt.textContent = y;
            if (y === currentYear) opt.selected = true;
            yearDropdown.appendChild(opt);
        }

        // Initialize dropdowns with today's date (not calendar.getDate() which might be week start)
        monthDropdown.value = today.getMonth();
        yearDropdown.value = today.getFullYear();

        function syncDropdowns() {
            // Always sync dropdowns based on calendar's current view date
            var viewDate = calendar.view.currentStart || calendar.getDate();
            if (viewDate) {
                monthDropdown.value = viewDate.getMonth();
                yearDropdown.value = viewDate.getFullYear();
            }
        }

        function syncCalendarToDropdowns() {
            var year = parseInt(yearDropdown.value, 10);
            var month = parseInt(monthDropdown.value, 10);
            calendar.gotoDate(new Date(year, month, 1));
        }

        calendar.on('datesSet', syncDropdowns);

        monthDropdown.addEventListener('change', function() {
            syncCalendarToDropdowns();
        });

        yearDropdown.addEventListener('change', function() {
            syncCalendarToDropdowns();
        });

        if (todayBtn) {
            todayBtn.addEventListener('click', function() {
                calendar.today();
            });
        }

        viewButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                calendar.changeView(button.getAttribute('data-view'));
            });
        });

        function toggleSuccessModal(show) {
            if (!successModal) return;
            if (show) {
                successModal.classList.remove('hidden');
                successModal.classList.add('flex');
            } else {
                successModal.classList.add('hidden');
                successModal.classList.remove('flex');
            }
        }

        function toggleErrorModal(show, title, message, label) {
            if (!errorModal) return;
            if (show) {
                errorLabel.textContent = label || 'Error';
                if (title) errorTitle.textContent = title;
                if (message) errorMessage.textContent = message;
                errorModal.classList.remove('hidden');
                errorModal.classList.add('flex');
            } else {
                errorModal.classList.add('hidden');
                errorModal.classList.remove('flex');
            }
        }

        if (redirectToCalendarBtn) {
            redirectToCalendarBtn.addEventListener('click', function() {
                window.location.href = '/calendar';
            });
        }

        if (closeSuccessBtn) {
            closeSuccessBtn.addEventListener('click', function() {
                toggleSuccessModal(false);
            });
        }

        if (closeErrorBtn) {
            closeErrorBtn.addEventListener('click', function() {
                toggleErrorModal(false);
            });
        }

        var reservationDateInput = document.getElementById('reservationDate');
        var reservationForm = document.getElementById('reservationForm');
        var reservationValidationMessage = document.getElementById('reservationValidationMessage');

        function setDefaultReservationTimes(startTime) {
            var startInput = document.getElementById('startTime');
            var endInput = document.getElementById('endTime');
            var selectedStart = startTime || '06:00';
            var parts = selectedStart.split(':').map(Number);
            var endHour = (parts[0] + 1) % 24;
            var endTime = String(endHour).padStart(2, '0') + ':' + String(parts[1]).padStart(2, '0');
            if (startInput) startInput.value = selectedStart;
            if (endInput) endInput.value = endTime;
        }

        function resetReservationValidation() {
            if (reservationValidationMessage) reservationValidationMessage.textContent = '';
        }

        function setReservationValidationError(message) {
            if (reservationValidationMessage) reservationValidationMessage.textContent = message;
        }

        function resetFacilityItemSelection() {
            facilityItemsRequestVersion++;
            if (facilityItemsRequest) {
                facilityItemsRequest.abort();
                facilityItemsRequest = null;
            }
            facilityItemSelections.clear();
            facilityItemOptions.textContent = 'Choose a facility to see its items.';
        }

        function renderFacilityItems(items) {
            facilityItemOptions.innerHTML = '';

            if (!items.length) {
                facilityItemOptions.textContent = 'No items are listed for this facility. You can still reserve the room.';
                return;
            }

            items.forEach(function(item) {
                var available = Number(item.available);
                var row = document.createElement('div');
                row.className = 'flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3 sm:flex-row sm:items-center sm:justify-between';

                var label = document.createElement('label');
                label.className = 'flex min-w-0 items-start gap-3';
                var checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-700';
                checkbox.dataset.facilityItemId = String(item.id);
                checkbox.checked = facilityItemSelections.has(String(item.id)) && available > 0;
                checkbox.disabled = available < 1;
                if (available < 1) {
                    facilityItemSelections.delete(String(item.id));
                }

                var description = document.createElement('span');
                description.className = 'min-w-0';
                var name = document.createElement('span');
                name.className = 'block text-sm font-medium text-slate-800';
                name.textContent = item.name;
                var stock = document.createElement('span');
                stock.className = 'block text-xs text-slate-500';
                stock.textContent = available + ' available for this time (' + item.stock + ' total)';
                description.append(name, stock);
                label.append(checkbox, description);

                var quantityLabel = document.createElement('label');
                quantityLabel.className = 'flex items-center gap-2 text-xs font-medium text-slate-600';
                quantityLabel.textContent = 'Quantity';
                var quantity = document.createElement('input');
                quantity.type = 'number';
                quantity.min = '1';
                quantity.max = String(available);
                quantity.value = Math.min(Number(facilityItemSelections.get(String(item.id)) || 1), Math.max(available, 1));
                quantity.disabled = !checkbox.checked;
                quantity.className = 'w-20 rounded-lg border border-slate-200 px-2 py-1.5 text-sm text-slate-800 disabled:bg-slate-100';
                quantity.dataset.facilityItemQuantity = String(item.id);
                quantityLabel.appendChild(quantity);
                row.append(label, quantityLabel);
                facilityItemOptions.appendChild(row);

                checkbox.addEventListener('change', function() {
                    quantity.disabled = !checkbox.checked;
                    if (checkbox.checked) {
                        facilityItemSelections.set(String(item.id), quantity.value);
                    } else {
                        facilityItemSelections.delete(String(item.id));
                    }
                });
                quantity.addEventListener('change', function() {
                    if (checkbox.checked) {
                        facilityItemSelections.set(String(item.id), quantity.value);
                    }
                });
            });
        }

        function refreshFacilityItems(preserveSelections) {
            var version = ++facilityItemsRequestVersion;
            if (preserveSelections !== false) {
                facilityItemOptions.querySelectorAll('[data-facility-item-id]:checked').forEach(function(checkbox) {
                    var quantityInput = facilityItemOptions.querySelector('[data-facility-item-quantity="' + checkbox.dataset.facilityItemId + '"]');
                    if (quantityInput) {
                        facilityItemSelections.set(checkbox.dataset.facilityItemId, quantityInput.value);
                    }
                });
            }
            if (facilityItemsRequest) {
                facilityItemsRequest.abort();
            }

            if (!facilitySelectModal.value) {
                facilityItemOptions.textContent = 'Choose a facility to see its items.';
                return;
            }

            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;
            if (!selectedDate || !startTime || !endTime || endTime <= startTime) {
                facilityItemOptions.textContent = 'Choose a valid date and time range to check item availability.';
                return;
            }

            facilityItemOptions.textContent = 'Checking item availability...';
            facilityItemsRequest = new AbortController();
            var query = new URLSearchParams({
                date: selectedDate,
                start_time: startTime,
                end_time: endTime
            });

            fetch('/api/facilities/' + encodeURIComponent(facilitySelectModal.value) + '/item-availability?' + query, {
                credentials: 'include',
                headers: { Accept: 'application/json' },
                signal: facilityItemsRequest.signal
            })
                .then(function(response) {
                    return response.json().then(function(data) {
                        if (!response.ok) {
                            throw new Error(data.message || 'Could not check item availability.');
                        }
                        return data;
                    });
                })
                .then(function(data) {
                    if (version === facilityItemsRequestVersion) {
                        renderFacilityItems(data.items);
                    }
                })
                .catch(function(error) {
                    if (error.name !== 'AbortError' && version === facilityItemsRequestVersion) {
                        facilityItemOptions.textContent = error.message || 'Could not check item availability. Please try again.';
                    }
                });
        }

        facilitySelectModal.addEventListener('change', function() {
            facilityItemSelections.clear();
            refreshFacilityItems(false);
        });
        ['startTime', 'endTime'].forEach(function(id) {
            document.getElementById(id).addEventListener('change', refreshFacilityItems);
        });

        openModalBtn.addEventListener('click', function() {
            selectedDate = calendar.getDate().toISOString().split('T')[0];
            modalDate.textContent = selectedDate;
            reservationForm.reset();
            resetFacilityItemSelection();
            if (reservationDateInput) {
                reservationDateInput.value = selectedDate;
            }
            resetReservationValidation();
            setDefaultReservationTimes();
            populateFacilities();
            toggleModal(true);
        });

        document.getElementById('closeModalBtn').addEventListener('click', function() {
            resetReservationValidation();
            toggleModal(false);
        });

        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (reservationDateInput) {
                reservationDateInput.value = selectedDate;
            }
            var facility = facilitySelectModal.value;
            var purpose = document.getElementById('purpose').value;
            var startTime = document.getElementById('startTime').value;
            var endTime = document.getElementById('endTime').value;
            var selectedItems = Array.from(facilityItemOptions.querySelectorAll('[data-facility-item-id]:checked')).map(function(checkbox) {
                var quantityInput = facilityItemOptions.querySelector('[data-facility-item-quantity="' + checkbox.dataset.facilityItemId + '"]');
                return {
                    facility_item_id: Number(checkbox.dataset.facilityItemId),
                    quantity: Number(quantityInput.value)
                };
            });

            resetReservationValidation();
            if (startTime && endTime && endTime <= startTime) {
                setReservationValidationError("End time can't be earlier than start");
                return;
            }
            if (selectedItems.some(function(item) { return !Number.isInteger(item.quantity) || item.quantity < 1; })) {
                setReservationValidationError('Enter a valid quantity for each selected item.');
                return;
            }

            var reservationPayload = {
                facility: facility,
                purpose: purpose,
                date: selectedDate,
                start_time: startTime,
                end_time: endTime
            };
            if (selectedItems.length) {
                reservationPayload.items = selectedItems;
            }
            fetch('/api/calendar/reserve', {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(reservationPayload)
            })
            .then(function(response) {
                return response.text().then(function(text) {
                    var data = {};
                    if (text) {
                        try {
                            data = JSON.parse(text);
                        } catch (parseError) {
                            if (response.ok) {
                                throw parseError;
                            }
                        }
                    }
                    if (!response.ok) {
                        var errorMsg = data.error || data.message || (data.errors ? Object.values(data.errors).flat()[0] : null) || 'Reservation failed';
                        throw { error: errorMsg, status: response.status, code: data.code };
                    }
                    return data;
                });
            })
            .then(function() {
                calendar.refetchEvents();
                toggleModal(false);
                document.getElementById('reservationForm').reset();
                resetReservationValidation();
                setDefaultReservationTimes();
                toggleSuccessModal(true);
            })
            .catch(function(err) {
                var title = 'Reservation failed';
                var message = 'An error occurred while processing your reservation.';
                var label = 'Error';
                
                if (err && err.code === 'schedule_occupied') {
                    label = 'Sorry!';
                    title = 'This schedule and room is already occupied!';
                    message = 'Please try another time or room!';
                } else if (err && err.error) {
                    if (err.status === 409) {
                        title = 'Reservation unavailable';
                        message = err.error;
                    } else {
                        message = err.error;
                    }
                } else if (err && err.message) {
                    message = err.message;
                }
                
                toggleErrorModal(true, title, message, label);
            });
        });
    });
</script>
@endpush
