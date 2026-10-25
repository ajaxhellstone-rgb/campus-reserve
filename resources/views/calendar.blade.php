@extends('layouts.app')

@section('title', 'Reserve Calendar - Campus Reserve')

@section('content')
<!-- Calendar page showing reservation events and interaction controls for event details. -->
<div class="min-h-screen bg-[#f3f7f4]">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col gap-4 md:gap-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-green-800">Campus Reserve</p>
                    <h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950">Calendar</h1>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="relative w-full sm:w-80">
                        <label for="eventSearch" class="sr-only">Search events</label>
                        <input id="eventSearch" type="search" placeholder="Search events, rooms, details..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 px-4 text-sm text-slate-700 shadow-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100" autocomplete="off" />
                        <div id="searchSuggestions" class="absolute left-0 right-0 mt-2 hidden max-h-72 overflow-auto rounded-2xl border border-slate-200 bg-white shadow-lg z-20"></div>
                    </div>
                </div>
            </div>
            <div class="calendar-page-layout grid gap-4 lg:grid-cols-[260px_minmax(0,1fr)]" data-calendar-grid-layout>
                <div class="calendar-sidebar space-y-6">
                    <aside class="calendar-mini rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="rounded-2xl border border-slate-100 bg-white p-3 text-slate-900">
                            <div class="mb-4 flex items-center justify-between gap-2 text-sm text-slate-600">
                                <button id="miniCalendarPrev" type="button" aria-label="Previous month" class="rounded-lg px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">&lsaquo;</button>
                                <h3 id="miniCalendarTitle" class="text-sm font-semibold text-slate-800"></h3>
                                <button id="miniCalendarNext" type="button" aria-label="Next month" class="rounded-lg px-2 py-1 text-lg leading-none text-slate-500 hover:bg-slate-100">&rsaquo;</button>
                                <div class="hidden">
                                    <label class="sr-only" for="miniCalendarMonth">Calendar month</label>
                                    <select id="miniCalendarMonth" class="min-w-0 rounded-lg border-0 bg-white py-1 pl-1 pr-5 text-sm font-medium text-slate-800 focus:ring-1 focus:ring-green-700"></select>
                                    <label class="sr-only" for="miniCalendarYear">Calendar year</label>
                                    <select id="miniCalendarYear" class="rounded-lg border-0 bg-white py-1 pl-1 pr-5 text-sm font-medium text-slate-800 focus:ring-1 focus:ring-green-700"></select>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-2 text-center text-[11px] uppercase text-slate-500 mb-3">
                                <span>Su</span>
                                <span>Mo</span>
                                <span>Tu</span>
                                <span>We</span>
                                <span>Th</span>
                                <span>Fr</span>
                                <span>Sa</span>
                            </div>
                            <div id="miniCalendarDays" class="grid grid-cols-7 gap-1.5 text-sm text-slate-700">
                                <!-- Filled by JavaScript -->
                            </div>
                        </div>
                    </aside>
                    <aside class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-sm font-medium text-slate-500">Pending</p>
                                <h2 class="text-xl font-semibold text-slate-900">Pending reservations</h2>
                            </div>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Waiting approval</span>
                        </div>
                        <div id="pendingReservationsList" class="space-y-4">
                            <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">
                                Pending reservation requests will appear here.
                            </div>
                        </div>
                    </aside>
                </div>
                <main class="calendar-main min-w-0 rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
                    <div class="mb-4 flex flex-col gap-2 sm:max-w-sm">
                        <label for="calendarFacilityFilter" class="text-sm font-medium text-slate-700">Show reservations for</label>
                        <select id="calendarFacilityFilter" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm focus:border-green-700 focus:outline-none focus:ring-2 focus:ring-green-100">
                            <option value="">All facilities</option>
                            @foreach($calendarFacilities as $facility)
                                <option value="{{ $facility->id }}">{{ $facility->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="calendar-mobile-date-bar mb-4 hidden items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3">
                        <button id="calendarMobilePrevBtn" type="button" aria-label="Previous date" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700">&lsaquo;</button>
                        <span id="calendarMobileDateTitle" class="text-sm font-semibold text-slate-900"></span>
                        <button id="calendarMobileNextBtn" type="button" aria-label="Next date" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-slate-700">&rsaquo;</button>
                    </div>
                    <a href="{{ route('reserve', ['open_facility_calendar' => 1]) }}" class="calendar-mobile-reserve mb-4 hidden rounded-xl bg-green-800 px-4 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-green-900">Reserve</a>
                    <div class="mb-5 flex flex-col gap-4 border-b border-slate-100 pb-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="calendar-desktop-date-nav flex items-center gap-3">
                            <button id="calendarPrevBtn" type="button" aria-label="Previous dates" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50">&lsaquo;</button>
                            <h2 id="calendarCurrentTitle" class="min-w-0 text-lg font-semibold text-slate-900">Calendar</h2>
                            <button id="calendarNextBtn" type="button" aria-label="Next dates" class="rounded-full border border-slate-200 bg-white px-3 py-2 text-slate-600 transition hover:bg-slate-50">&rsaquo;</button>
                        </div>
                        <div class="calendar-desktop-view-controls flex flex-wrap items-center gap-2">
                            <div class="hidden rounded-xl border border-slate-200 bg-slate-50 p-1 md:flex">
                                <button data-view="timeGridWeek" class="hidden rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-white md:inline-flex">Week</button>
                                <button data-view="dayGridMonth" class="hidden rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-white md:inline-flex">Month</button>
                                <button data-view="listWeek" class="hidden rounded-lg px-3 py-1.5 text-sm text-slate-600 transition hover:bg-white md:inline-flex">List</button>
                            </div>
                            <a href="{{ route('reserve', ['open_facility_calendar' => 1]) }}" class="inline-flex items-center justify-center rounded-xl bg-green-800 px-4 py-2 text-sm font-semibold text-white transition hover:bg-green-900">Reserve</a>
                        </div>
                    </div>

                    <p class="mb-3 flex items-center gap-2 text-xs text-slate-500">
                        <span class="h-2.5 w-2.5 rounded-sm bg-green-100 ring-1 ring-green-200" aria-hidden="true"></span>
                        Approved reservations show the room and booked time.
                    </p>
                    <div id="calendar" class="w-full rounded-2xl border border-slate-100 bg-white"></div>
                </main>
            </div>
        </div>
    </div>
</div>

<div id="eventInfoModal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-slate-900/30 p-3 backdrop-blur-sm sm:p-4">
    <div class="my-auto w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-5 shadow-2xl sm:p-8">
        <div class="flex items-start justify-between gap-4 mb-6">
            <div>
                <p class="text-sm text-slate-500">Reservation details</p>
                <h2 id="eventInfoTitle" class="text-2xl font-semibold text-slate-900 mt-2">Reservation</h2>
            </div>
            <button id="closeEventInfoBtn" type="button" class="rounded-full bg-slate-100 p-3 text-slate-600 hover:bg-slate-200">×</button>
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-700">Room</p>
                <p id="eventInfoRoom" class="text-slate-900">—</p>
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-700">Status</p>
                <p id="eventInfoStatus" class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800">Waiting Approval</p>
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-700">Date</p>
                <p id="eventInfoDate" class="text-slate-900">—</p>
            </div>
            <div class="space-y-2">
                <p class="text-sm font-semibold text-slate-700">Time</p>
                <p id="eventInfoTime" class="text-slate-900">—</p>
            </div>
            <div class="sm:col-span-2 space-y-2">
                <p class="text-sm font-semibold text-slate-700">Purpose</p>
                <p id="eventInfoPurpose" class="text-slate-900">—</p>
            </div>
        </div>
        <div class="mt-8 flex flex-wrap items-center gap-3">
            <a id="eventInfoEditBtn" href="#" class="hidden items-center justify-center rounded-full border border-slate-200 bg-slate-100 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-200 transition">Edit reservation</a>
            <form id="eventInfoDeleteForm" method="POST" action="#" class="hidden" onsubmit="return confirm('Remove this reservation? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center justify-center rounded-full bg-rose-600 px-5 py-3 text-sm font-semibold text-white hover:bg-rose-700 transition">Remove</button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    #calendar {
        min-height: 640px;
    }

    @media (max-width: 767px) {
        .calendar-page-layout {
            display: flex;
            flex-direction: column;
        }

        .calendar-main {
            order: 1;
        }

        .calendar-sidebar {
            order: 2;
        }

        .calendar-mini {
            display: none;
        }

        #pendingReservationsList {
            max-height: 16rem;
            overflow-y: auto;
        }

        #calendar .fc-toolbar {
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }

        #calendar .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        #calendar .fc-button {
            padding: 0.35rem 0.5rem;
            font-size: 0.8rem;
        }

        .calendar-main {
            padding: 0.875rem;
        }

        .calendar-desktop-date-nav,
        .calendar-desktop-view-controls,
        .calendar-main > p {
            display: none;
        }

        .calendar-mobile-date-bar {
            display: flex;
        }

        .calendar-mobile-reserve {
            display: block;
        }

        #calendar {
            min-height: 480px;
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

    #pendingReservationsList {
        max-height: 18rem;
        overflow-y: auto;
        padding-right: 0.5rem;
        scrollbar-color: #d6a629 #f8f7ef;
        scrollbar-width: thin;
    }

    #pendingReservationsList::-webkit-scrollbar {
        width: 8px;
    }

    #pendingReservationsList::-webkit-scrollbar-track {
        background: #f8f7ef;
        border-radius: 9999px;
    }

    #pendingReservationsList::-webkit-scrollbar-thumb {
        background: #fbbf24;
        border-radius: 9999px;
    }
</style>
@endpush

@push('scripts')
<!-- FullCalendar scripts and page event handling logic for loading events and opening modals. -->
<script defer src="{{ asset('fullcalendar/index.global.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var searchInput = document.getElementById('eventSearch');
        var facilityFilter = document.getElementById('calendarFacilityFilter');
        var pendingReservationsList = document.getElementById('pendingReservationsList');
        var viewButtons = document.querySelectorAll('[data-view]');
        var eventInfoModal = document.getElementById('eventInfoModal');
        var eventInfoTitle = document.getElementById('eventInfoTitle');
        var eventInfoRoom = document.getElementById('eventInfoRoom');
        var eventInfoStatus = document.getElementById('eventInfoStatus');
        var eventInfoDate = document.getElementById('eventInfoDate');
        var eventInfoTime = document.getElementById('eventInfoTime');
        var eventInfoPurpose = document.getElementById('eventInfoPurpose');
        var eventInfoEditBtn = document.getElementById('eventInfoEditBtn');
        var eventInfoDeleteForm = document.getElementById('eventInfoDeleteForm');
        var eventInfoClose = document.getElementById('closeEventInfoBtn');
        var currentSearch = '';
        var cachedEvents = [];
            var suggestionsEl = document.getElementById('searchSuggestions');
        var reservationOpenTime = @json($reservationHours['start_time']);
        var reservationCloseTime = @json($reservationHours['end_time']);
        var minimumCalendarYear = new Date().getFullYear() - 1;
        var calendarTitle = document.getElementById('calendarCurrentTitle');
        var previousCalendarButton = document.getElementById('calendarPrevBtn');
        var nextCalendarButton = document.getElementById('calendarNextBtn');
        var mobilePreviousButton = document.getElementById('calendarMobilePrevBtn');
        var mobileNextButton = document.getElementById('calendarMobileNextBtn');
        var mobileDateTitle = document.getElementById('calendarMobileDateTitle');
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

            function closeEventInfoModal() {
                if (eventInfoModal) {
                    eventInfoModal.classList.add('hidden');
                    eventInfoModal.classList.remove('flex');
                }
            }

            function renderSearchSuggestions(filteredEvents) {
                if (!suggestionsEl) return;
                suggestionsEl.replaceChildren();
                if (!filteredEvents.length || !currentSearch) {
                    suggestionsEl.classList.add('hidden');
                    return;
                }

                filteredEvents.forEach(function(item) {
                    var date = new Date(item.start);
                    var time = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    var button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'w-full text-left px-4 py-3 hover:bg-slate-100 transition-colors';
                    button.dataset.eventId = item.id;

                    var title = document.createElement('div');
                    title.className = 'font-semibold text-slate-900';
                    title.textContent = item.title;

                    var details = document.createElement('div');
                    details.className = 'text-xs text-slate-500';
                    details.textContent = (item.extendedProps.room || '') + (
                        item.extendedProps.purpose ? ' • ' + item.extendedProps.purpose : ''
                    );

                    var schedule = document.createElement('div');
                    schedule.className = 'mt-1 text-xs text-slate-500';
                    schedule.textContent = date.toLocaleDateString() + ' • ' + time;

                    button.append(title, details, schedule);
                    suggestionsEl.appendChild(button);
                });
                suggestionsEl.classList.remove('hidden');
            }

            function getSearchMatches(query) {
                if (!query) return [];
                query = query.toLowerCase();

                return cachedEvents.filter(function(event) {
                    var title = (event.title || '').toLowerCase();
                    var room = (event.extendedProps.room || '').toLowerCase();
                    var purpose = (event.extendedProps.purpose || '').toLowerCase();
                    return title.includes(query) || room.includes(query) || purpose.includes(query);
                }).slice(0, 6);
            }

            function openEventFromSuggestion(eventId) {
                var event = calendar.getEventById(eventId);
                var sourceEvent = cachedEvents.find(function(item) {
                    return String(item.id) === String(eventId);
                });

                if (!sourceEvent) return;
                if (sourceEvent.start) {
                    calendar.gotoDate(new Date(sourceEvent.start));
                }

                if (event) {
                    event.setProp('backgroundColor', event.backgroundColor || event.color);
                }

                // Show details modal for the selected suggestion
                var props = sourceEvent.extendedProps || {};
                var date = new Date(sourceEvent.start);
                var startTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                var endTime = sourceEvent.end ? new Date(sourceEvent.end).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                var status = props.status || 'pending';
                var statusLabel = props.is_maintenance ? 'Unavailable' : status === 'approved' ? 'Approved' : status === 'rejected' ? 'Rejected' : 'Waiting Approval';
                var statusClasses = props.is_maintenance ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-rose-100 text-rose-700' :
                                    status === 'approved' ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-emerald-100 text-emerald-700' :
                                    status === 'rejected' ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-red-100 text-red-700' :
                                    'inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800';

                eventInfoTitle.textContent = props.room || sourceEvent.title || 'Reservation';
                eventInfoRoom.textContent = props.room || sourceEvent.title || '—';
                eventInfoStatus.textContent = statusLabel;
                eventInfoStatus.className = statusClasses;
                eventInfoDate.textContent = props.date || date.toLocaleDateString();
                eventInfoTime.textContent = startTime + ' - ' + endTime;
                eventInfoPurpose.textContent = props.purpose || (
                    props.is_owner ? '—' : 'Details are only visible to the reservation owner.'
                );
                if (eventInfoEditBtn) {
                    eventInfoEditBtn.href = '/reserve/reservations/' + sourceEvent.id + '/edit';
                    if (props.is_owner && ['approved', 'rejected', 'pending'].includes(props.status)) {
                        eventInfoEditBtn.classList.remove('hidden');
                    } else {
                        eventInfoEditBtn.classList.add('hidden');
                    }
                }
                if (eventInfoDeleteForm) {
                    eventInfoDeleteForm.action = '/reserve/reservations/' + sourceEvent.id;
                    if (props.is_owner && (status === 'rejected' || status === 'pending')) {
                        eventInfoDeleteForm.classList.remove('hidden');
                    } else {
                        eventInfoDeleteForm.classList.add('hidden');
                    }
                }
                eventInfoModal.classList.remove('hidden');
                eventInfoModal.classList.add('flex');
            }

        var calendar = new window.FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            headerToolbar: false,
            navLinks: true,
            selectable: false,
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
            datesSet: function(info) {
                if (calendarTitle) {
                    calendarTitle.textContent = info.view.title;
                }
                if (mobileDateTitle) {
                    mobileDateTitle.textContent = info.view.title;
                }
                updateActiveViewButton(info.view.type);
                if (calendar.getDate().getFullYear() < minimumCalendarYear) {
                    calendar.gotoDate(new Date(minimumCalendarYear, 0, 1));
                    return;
                }

                updateMiniCalendar();
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
                    .then(function(events) {
                        cachedEvents = events.map(function(item) {
                            return {
                                id: item.id,
                                title: item.title,
                                start: item.start,
                                end: item.end,
                                extendedProps: item.extendedProps || {},
                                color: item.color || null
                            };
                        });
                        successCallback(events);
                    })
                    .catch(function(err) {
                        console.error(err);
                        failureCallback(err);
                    });
            },
            eventClick: function(info) {
                var event = info.event;
                var props = event.extendedProps || {};
                var startTime = event.start ? event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                var endTime = event.end ? event.end.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) : '';
                var status = props.status || 'pending';
                var statusLabel = props.is_maintenance ? 'Unavailable' : status === 'approved' ? 'Approved' : status === 'rejected' ? 'Rejected' : 'Waiting Approval';
                var statusClasses = props.is_maintenance ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-rose-100 text-rose-700' :
                                    status === 'approved' ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-emerald-100 text-emerald-700' :
                                    status === 'rejected' ? 'inline-flex rounded-full px-3 py-1 text-sm font-semibold bg-red-100 text-red-700' :
                                    'inline-flex rounded-full bg-amber-100 px-3 py-1 text-sm font-semibold text-amber-800';

                eventInfoTitle.textContent = props.room || event.title || 'Reservation';
                eventInfoRoom.textContent = props.room || event.title || '—';
                eventInfoStatus.textContent = statusLabel;
                eventInfoStatus.className = statusClasses;
                eventInfoDate.textContent = props.date || (event.start ? event.start.toLocaleDateString() : '—');
                eventInfoTime.textContent = startTime + ' - ' + endTime;
                eventInfoPurpose.textContent = props.purpose || (
                    props.is_owner ? '—' : 'Details are only visible to the reservation owner.'
                );
                if (eventInfoEditBtn) {
                    eventInfoEditBtn.href = '/reserve/reservations/' + event.id + '/edit';
                    if (props.is_owner && ['approved', 'rejected', 'pending'].includes(props.status)) {
                        eventInfoEditBtn.classList.remove('hidden');
                    } else {
                        eventInfoEditBtn.classList.add('hidden');
                    }
                }
                if (eventInfoDeleteForm) {
                    eventInfoDeleteForm.action = '/reserve/reservations/' + event.id;
                    if (props.is_owner && (status === 'rejected' || status === 'pending')) {
                        eventInfoDeleteForm.classList.remove('hidden');
                    } else {
                        eventInfoDeleteForm.classList.add('hidden');
                    }
                }
                eventInfoModal.classList.remove('hidden');
                eventInfoModal.classList.add('flex');
            }
        });

        calendar.render();
        if (facilityFilter) {
        facilityFilter.addEventListener('change', function() {
            calendar.refetchEvents();
        });
        }
        fetchPendingReservations();

        function fetchPendingReservations() {
            if (!pendingReservationsList) return;
            fetch('/api/calendar/pending', { credentials: 'include' })
                .then(function(response) { return response.json(); })
                .then(function(pending) {
                    if (!pending.length) {
                        pendingReservationsList.innerHTML = '<div class="rounded-3xl bg-slate-50 p-4 text-sm text-slate-500">No pending reservations at the moment.</div>';
                        return;
                    }

                    pendingReservationsList.innerHTML = pending.map(function(item) {
                        return '<div class="rounded-3xl bg-slate-50 p-4">'
                            + '<div class="flex items-center justify-between">'
                            + '<div>'
                            + '<p class="text-sm font-semibold text-slate-900">' + item.facility + '</p>'
                            + '<p class="text-sm text-slate-500">' + item.date + ' • ' + item.start_time + ' - ' + item.end_time + '</p>'
                            + '</div>'
                            + '<span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">Pending</span>'
                            + '</div>'
                            + '<p class="mt-3 text-sm text-slate-600">' + (item.purpose || 'No purpose provided.') + '</p>'
                            + '</div>';
                    }).join('');
                })
                .catch(function() {
                    if (pendingReservationsList) {
                        pendingReservationsList.innerHTML = '<div class="rounded-3xl bg-slate-50 p-4 text-sm text-slate-500">Unable to load pending reservations.</div>';
                    }
                });
        }

        // Mini calendar update function
        function updateMiniCalendar() {
            var currentDate = calendar.getDate();
            var miniMonth = document.getElementById('miniCalendarMonth');
            var miniYear = document.getElementById('miniCalendarYear');
            var miniView = document.getElementById('miniCalendarView');
            var miniDays = document.getElementById('miniCalendarDays');

            if (!miniMonth || !miniYear || !miniDays) return;

            var monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                            'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (miniMonth.options.length === 0) {
                monthNames.forEach(function(monthName, monthIndex) {
                    var option = document.createElement('option');
                    option.value = String(monthIndex);
                    option.textContent = monthName;
                    miniMonth.appendChild(option);
                });
            }

            var selectedYear = currentDate.getFullYear();
            if (miniYear.options.length === 0) {
                var lastYear = Math.max(new Date().getFullYear() + 20, selectedYear);
                for (var year = minimumCalendarYear; year <= lastYear; year++) {
                    var yearOption = document.createElement('option');
                    yearOption.value = String(year);
                    yearOption.textContent = String(year);
                    miniYear.appendChild(yearOption);
                }
            }
            miniMonth.value = String(currentDate.getMonth());
            miniYear.value = String(selectedYear);
            if (miniCalendarTitle) {
                miniCalendarTitle.textContent = currentDate.toLocaleDateString('en-US', {
                    month: 'long',
                    year: 'numeric'
                });
            }

            // Get first day of month and number of days
            var firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            var lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            var startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            // Clear and populate days
            miniDays.innerHTML = '';
            var today = new Date();
            today.setHours(0, 0, 0, 0);

            for (var i = 0; i < 42; i++) {
                var dayDate = new Date(startDate);
                dayDate.setDate(dayDate.getDate() + i);
                var dayButton = document.createElement('button');
                dayButton.type = 'button';
                dayButton.textContent = dayDate.getDate();
                dayButton.dataset.calendarDate = [
                    dayDate.getFullYear(),
                    String(dayDate.getMonth() + 1).padStart(2, '0'),
                    String(dayDate.getDate()).padStart(2, '0')
                ].join('-');
                dayButton.setAttribute('aria-label', dayDate.toLocaleDateString());
                dayButton.className = 'text-slate-700';

                // Highlight today
                if (dayDate.toDateString() === today.toDateString()) {
                    dayButton.className = 'bg-green-800 text-white hover:bg-green-900';
                } else if (dayDate.toDateString() === currentDate.toDateString()) {
                    dayButton.className = 'bg-green-100 font-semibold text-green-900';
                }

                // Dim days outside current month
                if (dayDate.getMonth() !== currentDate.getMonth()) {
                    dayButton.classList.add('opacity-40');
                }

                miniDays.appendChild(dayButton);
            }

        }

        updateMiniCalendar();
        document.getElementById('miniCalendarDays').addEventListener('click', function(event) {
            var dayButton = event.target.closest('button[data-calendar-date]');
            if (dayButton) {
                calendar.gotoDate(dayButton.dataset.calendarDate);
            }
        });

        var miniMonth = document.getElementById('miniCalendarMonth');
        var miniYear = document.getElementById('miniCalendarYear');
        function navigateCalendarFromSelectors(monthSelect, yearSelect) {
            var month = Number(monthSelect.value);
            var year = Number(yearSelect.value);

            if (Number.isInteger(month) && month >= 0 && month <= 11
                && Number.isInteger(year) && year >= minimumCalendarYear) {
                calendar.gotoDate(new Date(year, month, 1));
            }
        }

        function navigateFromMiniCalendar() {
            navigateCalendarFromSelectors(miniMonth, miniYear);
        }

        miniMonth.addEventListener('change', navigateFromMiniCalendar);
        miniYear.addEventListener('change', navigateFromMiniCalendar);
        [miniCalendarPreviousButton, miniCalendarNextButton].forEach(function(button, index) {
            button.addEventListener('click', function() {
                var monthOffset = index === 0 ? -1 : 1;
                calendar.gotoDate(new Date(calendar.getDate().getFullYear(), calendar.getDate().getMonth() + monthOffset, 1));
            });
        });

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentSearch = this.value.trim();
                var matches = getSearchMatches(currentSearch);
                renderSearchSuggestions(matches);
            });

            searchInput.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    currentSearch = '';
                    this.value = '';
                    renderSearchSuggestions([]);
                }
            });

            searchInput.addEventListener('blur', function() {
                setTimeout(function() {
                    renderSearchSuggestions([]);
                }, 150);
            });
        }

        if (suggestionsEl) {
            suggestionsEl.addEventListener('click', function(event) {
                var button = event.target.closest('button[data-event-id]');
                if (!button) return;
                var eventId = button.getAttribute('data-event-id');
                openEventFromSuggestion(eventId);
                renderSearchSuggestions([]);
                searchInput.value = '';
                currentSearch = '';
            });
        }

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

        if (mobilePreviousButton) {
            mobilePreviousButton.addEventListener('click', function() {
                calendar.prev();
            });
        }

        if (mobileNextButton) {
            mobileNextButton.addEventListener('click', function() {
                calendar.next();
            });
        }

        viewButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                var viewName = button.getAttribute('data-view');
                if (viewName) {
                    calendar.changeView(viewName);
                    updateMiniCalendar();
                }
            });
        });

        if (eventInfoClose) {
            eventInfoClose.addEventListener('click', closeEventInfoModal);
        }

        if (eventInfoModal) {
            eventInfoModal.addEventListener('click', function(event) {
                if (event.target === eventInfoModal) {
                    closeEventInfoModal();
                }
            });
        }
    });
</script>
@endpush
