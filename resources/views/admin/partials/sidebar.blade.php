@php
    $isDashboardPage = request()->routeIs('admin.dashboard');
    $dashboardUrl = route('admin.dashboard');
    $activeAdminPage = request()->routeIs('admin.facilities.*')
        ? 'facilities'
        : (request()->routeIs('admin.property-management.*')
            ? 'property'
            : (request()->routeIs('admin.reservation-settings*') ? 'reservation-settings' : 'overview'));
    $baseLinkClasses = 'inline-flex shrink-0 items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition md:w-full';
    $inactiveLinkClasses = 'font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900';
    $activeLinkClasses = 'bg-green-50 font-semibold text-green-900 hover:bg-green-100';
@endphp

<aside class="z-20 flex w-full shrink-0 flex-col border-b border-slate-200 bg-white px-4 py-4 md:sticky md:top-0 md:h-screen md:w-64 md:border-b-0 md:border-r md:px-5 md:py-6">
    <a href="{{ $dashboardUrl }}" class="flex items-center gap-3 px-2">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-800 text-sm font-bold text-white shadow-sm">CR</span>
        <span>
            <span class="block text-sm font-bold tracking-wide text-slate-900">CAMPUS RESERVE</span>
            <span class="mt-0.5 block text-xs text-slate-500">Administration</span>
        </span>
    </a>

    <div class="mt-6 hidden items-center gap-3 rounded-2xl bg-slate-50 p-3 md:flex">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-semibold text-green-900">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 1)) }}
        </span>
        <span class="min-w-0">
            <span class="block truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</span>
            <span class="mt-0.5 block text-xs text-slate-500">{{ auth()->user()->isSuperAdmin() ? 'Admin' : 'Staff' }}</span>
        </span>
    </div>

    <p class="mb-2 mt-7 hidden px-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400 md:block">Workspace</p>
    <nav aria-label="Admin dashboard navigation" class="mt-5 flex gap-2 overflow-x-auto md:mt-0 md:flex-col md:overflow-visible">
        <a href="{{ $isDashboardPage ? '#overview' : $dashboardUrl . '#overview' }}"
            @if($isDashboardPage) data-admin-section-link="overview" @endif
            @if($isDashboardPage && $activeAdminPage === 'overview') aria-current="page" @endif
            class="{{ $baseLinkClasses }} {{ $isDashboardPage && $activeAdminPage === 'overview' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.5v-9ZM9 21v-7.5h6V21"/></svg>
            Overview
        </a>
        <a href="{{ $isDashboardPage ? '#reservations' : $dashboardUrl . '#reservations' }}"
            @if($isDashboardPage) data-admin-section-link="reservations" @endif
            class="{{ $baseLinkClasses }} {{ $isDashboardPage && request()->get('section') === 'reservations' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2v4m8-4v4M4 9h16M5 4h14a1 1 0 0 1 1 1v15H4V5a1 1 0 0 1 1-1Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 14 2 2 4-4"/></svg>
            Reservations
        </a>
        <a href="{{ $isDashboardPage ? '#members' : $dashboardUrl . '#members' }}"
            @if($isDashboardPage) data-admin-section-link="members" @endif
            class="{{ $baseLinkClasses }} {{ $isDashboardPage && request()->get('section') === 'members' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2m16 0v-2a4 4 0 0 0-3-3.87M14 3.13a4 4 0 0 1 0 7.75M10 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z"/></svg>
            Members
        </a>
        <a href="{{ $isDashboardPage ? '#audit' : $dashboardUrl . '#audit' }}"
            @if($isDashboardPage) data-admin-section-link="audit" @endif
            class="{{ $baseLinkClasses }} {{ $isDashboardPage && request()->get('section') === 'audit' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h8m-8 4h8"/></svg>
            Audit activity
        </a>
        <a href="{{ route('admin.facilities.create') }}"
            @if($activeAdminPage === 'facilities') aria-current="page" @endif
            class="{{ $baseLinkClasses }} {{ $activeAdminPage === 'facilities' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 21h18M5 21V5l7-3v19m7 0V9l-7-4M8 8v.01M8 12v.01M8 16v.01m8-4v.01m0 4v.01"/></svg>
            Facilities
        </a>
        <a href="{{ route('admin.property-management.index') }}"
            @if($activeAdminPage === 'property') aria-current="page" @endif
            class="{{ $baseLinkClasses }} {{ $activeAdminPage === 'property' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m4.3 7.7 7.7 4.4 7.7-4.4M12 21v-8.9m-4-6.8 8 4.5"/></svg>
            Property items
        </a>
        <a href="{{ route('admin.reservation-settings') }}"
            @if($activeAdminPage === 'reservation-settings') aria-current="page" @endif
            class="{{ $baseLinkClasses }} {{ $activeAdminPage === 'reservation-settings' ? $activeLinkClasses : $inactiveLinkClasses }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 7v5l3 2"/></svg>
            Reservation settings
        </a>
    </nav>

    <div class="mt-auto hidden border-t border-slate-100 pt-5 md:block">
        <a href="/reserve" class="inline-flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-900">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14 3h7v7m0-7-9 9m6-2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h9"/></svg>
            Back to user portal
        </a>
    </div>
</aside>
