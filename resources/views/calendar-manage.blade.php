@extends('layouts.embedded')

@section('title', 'Reserve a Facility')

@section('content')
<div class="min-h-screen bg-[#f3f7f4]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:gap-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-green-800">Campus Reserve</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Reserve a Facility</h1>
                </div>
            </div>

            <div class="calendar-manage-layout grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]">
                <aside class="calendar-manage-sidebar order-1 space-y-4 lg:order-1">
                    <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-slate-900">Choose a date</h2>
                        </div>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <button id="miniCalendarPrev" type="button" aria-label="Previous month" class="rounded-lg px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">&lsaquo;</button>
                                <h3 id="miniCalendarTitle" class="text-sm font-semibold text-slate-800"></h3>
                                <button id="miniCalendarNext" type="button" aria-label="Next month" class="rounded-lg px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">&rsaquo;</button>
                            </div>
                            <div class="hidden">
                                <label for="monthDropdown" class="sr-only">Month</label>
                                <select id="monthDropdown">
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
                                <label for="yearDropdown" class="sr-only">Year</label>
                                <select id="yearDropdown"></select>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-white p-3">
                                <div class="mb-3 grid grid-cols-7 gap-1 text-center text-[10px] font-medium uppercase tracking-wide text-slate-400">
                                    <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                </div>
                                <div id="miniCalendarDays" class="grid grid-cols-7 gap-1 text-center"></div>
                            </div>
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                                <h3 class="text-sm font-semibold text-slate-900 mb-3">Ready to reserve</h3>
                                <p class="text-sm text-slate-600 mb-2">Select a date or time on the calendar to start your booking.</p>
                                <p class="mb-4 text-xs font-medium text-green-900">Reservations allowed {{ date('g:i A', strtotime($reservationHours['start_time'])) }}–{{ date('g:i A', strtotime($reservationHours['end_time'])) }}.</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="calendar-manage-main order-2 min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6 lg:order-2">
                    <div class="mb-4 flex flex-col gap-2 sm:max-w-sm">
                        <label for="calendarFacilityFilter" class="text-sm font-medium text-slate-700">Show reservations for</label>
                        <select id="calendarFacilityFilter" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            <option value="">All facilities</option>
                            @foreach($calendarFacilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-5 flex flex-col gap-4 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <button id="calendarPrevBtn" type="button" aria-label="Previous dates" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50">&lsaquo;</button>
                            <h2 id="calendarCurrentTitle" class="min-w-0 text-lg font-semibold text-slate-900">Reservation calendar</h2>
                            <button id="calendarNextBtn" type="button" aria-label="Next dates" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50">&rsaquo;</button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="hidden rounded-xl border border-slate-200 bg-slate-50 p-1 md:flex">
                                <button data-view="timeGridWeek" class="hidden rounded-lg bg-white px-3 py-1.5 text-sm font-medium text-green-900 shadow-sm md:inline-flex">Week</button>
                                <button data-view="dayGridMonth" class="hidden rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-white md:inline-flex">Month</button>
                                <button data-view="listWeek" class="hidden rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-white md:inline-flex">List</button>
                            </div>
                        </div>
                    </div>
                    <p class="mb-3 flex items-center gap-2 text-xs text-slate-500">
                        <span class="h-2.5 w-2.5 rounded-sm bg-green-100 ring-1 ring-green-200" aria-hidden="true"></span>
                        Approved reservations show the room and booked time.
                    </p>
                    <div id="calendar" class="rounded-2xl border border-slate-100 bg-white"></div>
                </main>
            </div>
        </div>
    </div>
</div>

<div id="reservationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/10 backdrop-blur-sm p-4 opacity-0 pointer-events-none transition-opacity duration-200" aria-hidden="true">
    <div class="max-h-[90vh] w-full max-w-2xl transform overflow-y-auto rounded-3xl border border-slate-200 bg-white p-8 shadow-2xl transition-transform duration-200">
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
                    <input type="time" id="startTime" name="startTime" value="{{ $reservationHours['start_time'] }}" min="{{ $reservationHours['start_time'] }}" max="{{ $reservationHours['end_time'] }}" class="mt-2 w-full h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700" required />
                </div>
                <div>
                    <label for="endTime" class="block text-sm font-medium text-slate-700">End time</label>
                    <input type="time" id="endTime" name="endTime" value="{{ $reservationHours['end_time'] }}" min="{{ $reservationHours['start_time'] }}" max="{{ $reservationHours['end_time'] }}" class="mt-2 w-full h-12 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700" required />
                </div>
            </div>
            <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">Property items are reserved separately from facilities on the Reserve Property tab.</p>
            <div class="flex flex-col gap-3 sm:flex-row sm:justify-between sm:items-center">
                <div id="reservationValidationMessage" class="min-h-5 text-sm font-semibold text-rose-600"></div>
                <div class="flex gap-3">
                    <button type="button" id="closeModalBtn" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="rounded-2xl bg-green-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Reserve</button>
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
            <button id="closeErrorBtn" type="button" class="rounded-2xl bg-green-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Close</button>
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
            <button id="redirectToCalendarBtn" type="button" class="rounded-2xl bg-green-800 px-5 py-3 text-sm font-semibold text-white transition hover:bg-green-900">Go to Calendar</button>
            <button id="closeSuccessBtn" type="button" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition">Close</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #calendar {
        min-height: 620px;
    }

    @media (max-width: 1023px) {
        #calendar {
            min-height: 480px;
        }

        .calendar-manage-layout {
            grid-template-columns: minmax(0, 1fr);
        }

    }

    @media (max-width: 767px) {
        #reservationModal > div {
            max-height: calc(100dvh - 2rem);
            padding: 1.25rem;
        }
    }

    @media (max-width: 767px) {
        .calendar-manage-layout {
            gap: 0;
        }

        .calendar-manage-main {
            border-radius: 1.25rem;
            padding: 0.875rem;
        }

        .calendar-manage-main > div:first-child {
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
        }

        .calendar-manage-main > div:first-child > div:first-child {
            gap: 0.5rem;
        }

        #calendar .fc-timegrid-slot {
            height: 2.75rem;
        }
    }

    #calendar {
        --fc-border-color: #e8eeea;
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: #f8fafc;
        --fc-today-bg-color: #f0f7f1;
        --fc-button-bg-color: #166534;
        --fc-button-border-color: #166534;
        --fc-button-hover-bg-color: #14532d;
        --fc-button-hover-border-color: #14532d;
        --fc-button-active-bg-color: #052e16;
        --fc-button-active-border-color: #052e16;
        --fc-event-border-color: #166534;
        --fc-event-bg-color: #166534;
        --fc-event-text-color: #fff;
    }

    #calendar .fc-toolbar-title {
        color: #1f2937;
        font-weight: 600;
    }

    #calendar .fc-col-header-cell-cushion,
    #calendar .fc-daygrid-day-number,
    #calendar .fc-timegrid-slot-label-cushion {
        color: #475569;
        text-decoration: none;
    }

    #calendar .fc-timegrid-now-indicator-line {
        border-color: #b45309;
    }

    #calendar .fc-scrollgrid {
        border: 0;
    }

    #calendar .fc-scrollgrid-section > td,
    #calendar .fc-scrollgrid-section > th {
        border: 0;
    }

    #calendar .fc-scrollgrid-section-header .fc-scroller {
        scrollbar-width: none;
    }

    #calendar .fc-scrollgrid-section-header .fc-scroller::-webkit-scrollbar {
        display: none;
    }

    #calendar .fc-col-header-cell {
        border: 0;
        padding: 0.35rem 0.25rem 0.75rem;
    }

    #calendar .fc-col-header-cell-cushion {
        display: block;
        min-height: 3.5rem;
        border: 1px solid #e6ece7;
        border-radius: 0.9rem;
        background: #f8faf8;
    }

    #calendar .fc-day-today .fc-col-header-cell-cushion,
    #calendar th.fc-day-today .fc-col-header-cell-cushion {
        border-color: #c8ddce;
        background: #edf5ef;
    }

    #calendar .fc-timegrid-col {
        background: #fff;
    }

    #calendar .fc-timegrid-col.fc-day-today {
        background: #f7faf7;
    }

    #calendar .fc-timegrid-slot {
        height: 3.25rem;
    }

    #calendar .fc-v-event {
        border: 0;
        border-left: 3px solid #5d8868;
        border-radius: 0.75rem;
        box-shadow: 0 2px 6px rgb(31 41 55 / 8%);
        padding: 0.15rem;
    }

    #calendar .fc-event-main {
        color: #26372c;
        padding: 0.3rem 0.4rem;
    }

    #calendar .fc-daygrid-event {
        border: 0;
        border-left: 3px solid #5d8868;
        border-radius: 0.6rem;
        padding: 0.15rem 0.25rem;
    }

    #calendar .fc-daygrid-event .fc-event-main {
        padding: 0.15rem 0.25rem;
    }

    #miniCalendarDays button {
        min-width: 0;
        border-radius: 9999px;
        padding: 0.45rem 0;
        font-size: 0.75rem;
        transition: background-color 150ms ease, color 150ms ease;
    }

    #miniCalendarDays button:hover {
        background: #edf5ef;
    }
</style>
@endpush

@push('scripts')
<script defer src="{{ asset('fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var monthDropdown = document.getElementById('monthDropdown');
        var yearDropdown = document.getElementById('yearDropdown');
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
        var selectedDate = null;
        var facilityFilter = document.getElementById('calendarFacilityFilter');
        var reservationOpenTime = @json($reservationHours['start_time']);
        var reservationCloseTime = @json($reservationHours['end_time']);
        var viewButtons = document.querySelectorAll('[data-view]');
        var calendarTitle = document.getElementById('calendarCurrentTitle');
        var previousCalendarButton = document.getElementById('calendarPrevBtn');
        var nextCalendarButton = document.getElementById('calendarNextBtn');
        var miniCalendarDays = document.getElementById('miniCalendarDays');
        var miniCalendarTitle = document.getElementById('miniCalendarTitle');
        var miniCalendarPreviousButton = document.getElementById('miniCalendarPrev');
        var miniCalendarNextButton = document.getElementById('miniCalendarNext');

        function updateActiveViewButton(viewType) {
            viewButtons.forEach(function(button) {
                var isActive = button.getAttribute('data-view') === viewType;
                button.classList.toggle('bg-white', isActive);
                button.classList.toggle('font-medium', isActive);
                button.classList.toggle('text-green-900', isActive);
                button.classList.toggle('shadow-sm', isActive);
                button.classList.toggle('text-slate-600', !isActive);
                button.setAttribute('aria-pressed', String(isActive));
            });
        }

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
                })
                .catch(function() {
                    toggleErrorModal(true, 'Facilities unavailable', 'Could not load available facilities. Please close and try again.', 'Error');
                });
        }

        var calendar = new window.FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            selectable: true,
            editable: false,
            allDaySlot: false,
            slotMinTime: reservationOpenTime + ':00',
            slotMaxTime: reservationCloseTime + ':00',
            dayHeaderContent: function(info) {
                var weekday = info.date.toLocaleDateString('en-US', { weekday: 'short' });
                var container = document.createElement('div');
                container.className = 'flex flex-col items-center gap-0.5 py-1';

                var weekdayLabel = document.createElement('span');
                weekdayLabel.className = 'text-[10px] font-medium uppercase tracking-wide text-slate-500';
                weekdayLabel.textContent = weekday;

                var dateLabel = document.createElement('span');
                dateLabel.className = 'text-sm font-semibold text-slate-800';
                dateLabel.textContent = String(info.date.getDate());

                container.append(weekdayLabel, dateLabel);

                return { domNodes: [container] };
            },
            events: function(info, successCallback, failureCallback) {
                var query = new URLSearchParams({
                    start: info.startStr.slice(0, 10),
                    end: info.endStr.slice(0, 10)
                });
                if (facilityFilter && facilityFilter.value) {
                    query.set('facility_id', facilityFilter.value);
                }

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
        window.addEventListener('message', function(event) {
            if (event.origin !== window.location.origin || event.data !== 'facility-calendar:resize') {
                return;
            }
            calendar.updateSize();
        });
        if (facilityFilter) {
            facilityFilter.addEventListener('change', function() {
                calendar.refetchEvents();
            });
        }
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
            if (calendarTitle && calendar.view) {
                calendarTitle.textContent = calendar.view.title;
            }
            if (miniCalendarTitle && viewDate) {
                miniCalendarTitle.textContent = viewDate.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }
            renderMiniCalendar(viewDate || calendar.getDate());
        }

        function renderMiniCalendar(date) {
            if (!miniCalendarDays) return;

            var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
            var startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            miniCalendarDays.replaceChildren();

            for (var dayIndex = 0; dayIndex < 42; dayIndex++) {
                var dayDate = new Date(startDate);
                dayDate.setDate(startDate.getDate() + dayIndex);

                var button = document.createElement('button');
                button.type = 'button';
                button.textContent = String(dayDate.getDate());
                button.dataset.calendarDate = [
                    dayDate.getFullYear(),
                    String(dayDate.getMonth() + 1).padStart(2, '0'),
                    String(dayDate.getDate()).padStart(2, '0')
                ].join('-');
                button.setAttribute('aria-label', dayDate.toLocaleDateString());
                button.className = 'text-slate-700';

                if (dayDate.toDateString() === today.toDateString()) {
                    button.className = 'bg-green-800 text-white hover:bg-green-900';
                } else if (dayDate.toDateString() === calendar.getDate().toDateString()) {
                    button.className = 'bg-green-100 font-semibold text-green-900';
                }
                if (dayDate.getMonth() !== date.getMonth()) {
                    button.classList.add('opacity-40');
                }
                miniCalendarDays.appendChild(button);
            }
        }

        function syncCalendarToDropdowns() {
            var year = parseInt(yearDropdown.value, 10);
            var month = parseInt(monthDropdown.value, 10);
            calendar.gotoDate(new Date(year, month, 1));
        }

        calendar.on('datesSet', function() {
            syncDropdowns();
            updateActiveViewButton(calendar.view.type);
        });
        syncDropdowns();
        updateActiveViewButton(calendar.view.type);

        monthDropdown.addEventListener('change', function() {
            syncCalendarToDropdowns();
        });

        yearDropdown.addEventListener('change', function() {
            syncCalendarToDropdowns();
        });
        miniCalendarDays.addEventListener('click', function(event) {
            var dayButton = event.target.closest('button[data-calendar-date]');
            if (dayButton) {
                calendar.gotoDate(dayButton.dataset.calendarDate);
            }
        });
        [miniCalendarPreviousButton, miniCalendarNextButton].forEach(function(button, index) {
            button.addEventListener('click', function() {
                var monthOffset = index === 0 ? -1 : 1;
                calendar.gotoDate(new Date(calendar.getDate().getFullYear(), calendar.getDate().getMonth() + monthOffset, 1));
            });
        });

        if (previousCalendarButton) {
            previousCalendarButton.addEventListener('click', function() {
                calendar.prev();
            });
        }

        if (nextCalendarButton) {
            nextCalendarButton.addEventListener('click', function() {
                calendar.next();
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
                window.top.location.href = '/calendar';
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
            var selectedStart = startTime || reservationOpenTime;
            var parts = selectedStart.split(':').map(Number);
            var endHour = (parts[0] + 1) % 24;
            var endTime = String(endHour).padStart(2, '0') + ':' + String(parts[1]).padStart(2, '0');
            if (endTime <= selectedStart || endTime > reservationCloseTime) {
                endTime = reservationCloseTime;
            }
            if (startInput) startInput.value = selectedStart;
            if (endInput) endInput.value = endTime;
        }

        function resetReservationValidation() {
            if (reservationValidationMessage) reservationValidationMessage.textContent = '';
        }

        function setReservationValidationError(message) {
            if (reservationValidationMessage) reservationValidationMessage.textContent = message;
        }

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
            resetReservationValidation();
            if (startTime && endTime && endTime <= startTime) {
                setReservationValidationError("End time can't be earlier than start");
                return;
            }
            if (startTime < reservationOpenTime || endTime > reservationCloseTime) {
                setReservationValidationError('Reservations are allowed from ' + reservationOpenTime + ' to ' + reservationCloseTime + '.');
                return;
            }
            var reservationPayload = {
                facility: facility,
                purpose: purpose,
                date: selectedDate,
                start_time: startTime,
                end_time: endTime
            };
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
