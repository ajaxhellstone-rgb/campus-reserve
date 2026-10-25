<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Campus Reserve')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-slate-900 transition-colors duration-200">
    <!-- Application header and global navigation bar -->
    <header class="relative z-40 flex flex-wrap items-center justify-between gap-x-4 gap-y-3 border-b border-gray-200 bg-white px-4 py-3 transition-colors duration-200 sm:px-6 sm:py-4">
        <a href="/" class="flex items-center">
            <div class="flex items-center rounded-xl border border-amber-300 bg-white px-2 py-1 shadow-sm">
                <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                <span class="ml-1 rounded-md bg-green-800 px-1 text-xs font-medium text-white">RESERVE</span>
            </div>
        </a>

        <nav class="hidden items-center gap-8 md:flex">
            <!-- Main site navigation links. The active route is highlighted dynamically. -->
            <a href="/" @if(request()->path() === '/') aria-current="page" @endif class="relative text-sm font-medium transition-colors hover:text-green-900 {{ request()->path() === '/' ? 'text-green-900 after:absolute after:-bottom-2 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-green-900' : 'text-gray-700' }}">HOME</a>
            <a href="/reserve" @if(request()->is('reserve')) aria-current="page" @endif class="relative text-sm font-medium transition-colors hover:text-green-900 {{ request()->is('reserve') ? 'text-green-900 after:absolute after:-bottom-2 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-green-900' : 'text-gray-700' }}">RESERVE</a>
            <a href="/calendar" @if(request()->is('calendar')) aria-current="page" @endif class="relative text-sm font-medium transition-colors hover:text-green-900 {{ request()->is('calendar') ? 'text-green-900 after:absolute after:-bottom-2 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-green-900' : 'text-gray-700' }}">CALENDAR</a>
            <a href="/about" @if(request()->is('about')) aria-current="page" @endif class="relative text-sm font-medium transition-colors hover:text-green-900 {{ request()->is('about') ? 'text-green-900 after:absolute after:-bottom-2 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-green-900' : 'text-gray-700' }}">ABOUT US</a>
            <a href="/contact" @if(request()->is('contact')) aria-current="page" @endif class="relative text-sm font-medium transition-colors hover:text-green-900 {{ request()->is('contact') ? 'text-green-900 after:absolute after:-bottom-2 after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-green-900' : 'text-gray-700' }}">CONTACT US</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            @auth
                <a id="notificationLink" href="{{ route('notifications.index') }}" class="relative text-gray-700 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </a>
                <a href="/profile" class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900">
                    <img
                        src="{{ auth()->user()->profile_picture ? asset('storage/' . auth()->user()->profile_picture) : asset('images/guest-avatar.svg') }}"
                        onerror="this.onerror=null;this.src='{{ asset('images/guest-avatar.svg') }}';"
                        alt="Profile Picture"
                        class="w-8 h-8 rounded-full object-cover"
                    >
                    Profile
                </a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-gray-700 hover:text-gray-900">Admin</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-1.5 text-sm font-medium bg-black text-white rounded-full hover:bg-gray-800 transition-colors">
                        Log Out
                    </button>
                </form>
            @else
                <a href="/signup" class="px-4 py-1.5 text-sm font-medium bg-black text-white rounded-full hover:bg-gray-800 transition-colors">Sign Up</a>
                <a href="/login" class="px-4 py-1.5 text-sm font-medium bg-black text-white rounded-full hover:bg-gray-800 transition-colors">Log In</a>
            @endauth
        </div>
        <button
            id="mobile-menu-toggle"
            type="button"
            class="relative z-[60] inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-green-700 md:hidden"
            aria-expanded="false"
            aria-controls="mobile-navigation"
            aria-label="Open navigation menu"
        >
            <svg id="mobile-menu-open-icon" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg id="mobile-menu-close-icon" class="hidden h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"></path>
            </svg>
        </button>

        <button id="mobile-navigation-backdrop" type="button" class="pointer-events-none fixed inset-x-0 bottom-0 z-40 bg-slate-950/20 opacity-0 backdrop-blur-sm transition-opacity duration-300 md:hidden" aria-label="Close navigation menu" aria-hidden="true" tabindex="-1"></button>
        <nav id="mobile-navigation" class="pointer-events-none fixed bottom-0 left-0 z-50 flex w-[min(84vw,20rem)] -translate-x-full flex-col gap-1 overflow-y-auto border-r border-slate-200 bg-white px-3 py-4 shadow-2xl transition-transform duration-300 ease-out md:hidden" aria-label="Mobile navigation" aria-hidden="true" inert>
            <a href="/" @if(request()->path() === '/') aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors hover:bg-green-50 hover:text-green-900 {{ request()->path() === '/' ? 'bg-green-50 text-green-900 underline decoration-2 underline-offset-4' : 'text-slate-700' }}">Home</a>
            <a href="/reserve" @if(request()->is('reserve')) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors hover:bg-green-50 hover:text-green-900 {{ request()->is('reserve') ? 'bg-green-50 text-green-900 underline decoration-2 underline-offset-4' : 'text-slate-700' }}">Reserve</a>
            <a href="/calendar" @if(request()->is('calendar')) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors hover:bg-green-50 hover:text-green-900 {{ request()->is('calendar') ? 'bg-green-50 text-green-900 underline decoration-2 underline-offset-4' : 'text-slate-700' }}">Calendar</a>
            <a href="/about" @if(request()->is('about')) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors hover:bg-green-50 hover:text-green-900 {{ request()->is('about') ? 'bg-green-50 text-green-900 underline decoration-2 underline-offset-4' : 'text-slate-700' }}">About us</a>
            <a href="/contact" @if(request()->is('contact')) aria-current="page" @endif class="rounded-xl px-4 py-3 text-sm font-semibold transition-colors hover:bg-green-50 hover:text-green-900 {{ request()->is('contact') ? 'bg-green-50 text-green-900 underline decoration-2 underline-offset-4' : 'text-slate-700' }}">Contact us</a>
            @auth
                <a href="{{ route('notifications.index') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-green-50">Notifications</a>
                <a href="/profile" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-green-50">Profile settings</a>
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-green-50">Staff dashboard</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="px-4 pb-2 pt-1">
                    @csrf
                    <button type="submit" class="w-full rounded-xl bg-green-800 px-4 py-3 text-left text-sm font-semibold text-white hover:bg-green-900">Log out</button>
                </form>
            @else
                <div class="flex gap-3 px-4 pb-2 pt-1">
                    <a href="/signup" class="flex-1 rounded-xl bg-green-800 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-green-900">Sign up</a>
                    <a href="/login" class="flex-1 rounded-xl border border-green-800 px-4 py-3 text-center text-sm font-semibold text-green-900 hover:bg-green-50">Log in</a>
                </div>
            @endauth
        </nav>
    </header>
    
    <main>
        @yield('content')
    </main>

    @auth
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var audioContext = null;
                var lastCount = null;
                var lastNotificationTimestamp = null;
                var notificationCheckInProgress = false;
                var notificationLink = document.getElementById('notificationLink');
                var notificationEndpoint = @json(route('notifications.unreadSummary'));

                function unlockNotificationSound() {
                    var AudioContextConstructor = window.AudioContext || window.webkitAudioContext;
                    if (!AudioContextConstructor || audioContext) return;

                    audioContext = new AudioContextConstructor();
                    if (audioContext.state === 'suspended') {
                        audioContext.resume().catch(function(error) {
                            console.warn('Notification sound could not be enabled.', error);
                        });
                    }
                }

                function playNotificationSound() {
                    if (!audioContext || audioContext.state !== 'running') return;

                    var oscillator = audioContext.createOscillator();
                    var gain = audioContext.createGain();
                    var now = audioContext.currentTime;
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, now);
                    oscillator.frequency.setValueAtTime(660, now + 0.12);
                    gain.gain.setValueAtTime(0.0001, now);
                    gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02);
                    gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.3);
                    oscillator.connect(gain);
                    gain.connect(audioContext.destination);
                    oscillator.start(now);
                    oscillator.stop(now + 0.3);
                }

                document.addEventListener('pointerdown', unlockNotificationSound, { once: true });
                document.addEventListener('keydown', unlockNotificationSound, { once: true });

                function updateNotificationStatus() {
                    if (document.hidden || notificationCheckInProgress) return;

                    notificationCheckInProgress = true;
                    fetch(notificationEndpoint, {
                        headers: { 'Accept': 'application/json' },
                        credentials: 'same-origin'
                    })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Notification status request failed: ' + response.status);
                            }
                            return response.json();
                        })
                        .then(function(summary) {
                            var count = Number(summary.count) || 0;
                            var notificationTimestamp = summary.latest_at || null;

                            if (lastCount !== null
                                && (count > lastCount
                                    || (count === lastCount && count > 0 && notificationTimestamp !== lastNotificationTimestamp))) {
                                playNotificationSound();
                            }

                            lastCount = count;
                            lastNotificationTimestamp = notificationTimestamp;

                            var badge = document.getElementById('notificationBadge');
                            if (count > 0) {
                                if (!badge && notificationLink) {
                                    badge = document.createElement('span');
                                    badge.id = 'notificationBadge';
                                    badge.className = 'absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[10px] font-semibold text-white';
                                    notificationLink.appendChild(badge);
                                }
                                if (badge) badge.textContent = String(count);
                            } else if (badge) {
                                badge.remove();
                            }
                        })
                        .catch(function(error) {
                            console.error('Could not check for new notifications.', error);
                        })
                        .finally(function() {
                            notificationCheckInProgress = false;
                        });
                }

                updateNotificationStatus();
                window.setInterval(updateNotificationStatus, 30000);
                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) updateNotificationStatus();
                });
            });
        </script>
    @endauth

    @if(auth()->check() && !request()->routeIs('profile.show', 'security.compromised', 'login', 'register', 'password.*', '2fa.*', 'admin.*'))
        <div id="chatbot-widget" class="fixed inset-0 z-50 pointer-events-none" style="position: fixed; inset: 0; z-index: 9999; pointer-events: none;">
            <div id="chatbot-backdrop" class="invisible pointer-events-none absolute inset-0 bg-slate-950/20 opacity-0 transition-opacity duration-300" aria-hidden="true"></div>
            <button id="chatbot-toggle" type="button" class="pointer-events-auto fixed bottom-5 right-5 flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-green-800 via-green-700 to-amber-400 text-white shadow-xl shadow-green-900/25 transition hover:-translate-y-0.5 hover:shadow-2xl" style="position: fixed; right: 20px; bottom: 20px; z-index: 10001; display: flex; width: 56px; height: 56px; border-radius: 9999px; background: linear-gradient(135deg, #166534, #15803d 58%, #facc15); color: #fff; pointer-events: auto; cursor: pointer;" aria-label="Open Arcee" title="Open Arcee">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m7-2a8 8 0 01-8 8 8.4 8.4 0 01-3.7-.85L4 20l.85-3.3A8 8 0 1112 20" />
                </svg>
            </button>
            <section id="chatbot-panel" class="invisible pointer-events-none fixed right-0 top-0 flex h-full translate-x-full flex-col border-l border-indigo-100 bg-white opacity-0 shadow-2xl shadow-indigo-950/20 transition duration-300 ease-out sm:my-3 sm:mr-3 sm:h-[calc(100%-1.5rem)] sm:rounded-3xl sm:border" style="position: fixed; top: 0; right: 0; bottom: 0; z-index: 10000; display: flex; width: min(440px, 100vw); height: 100%;" aria-label="Arcee chatbot">
                <div class="flex items-center justify-between border-b border-white/15 bg-gradient-to-r from-green-900 via-green-800 to-amber-400 px-5 py-4 text-white sm:rounded-t-3xl" style="display: flex; align-items: center; justify-content: space-between; min-height: 78px; padding: 16px 20px; background: linear-gradient(110deg, #14532d, #166534 58%, #facc15); color: #fff;">
                    <div>
                        <div class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-white/30 bg-white/15 text-sm font-bold shadow-inner" style="display: flex; flex: 0 0 40px; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 16px; border: 1px solid rgba(255,255,255,.3); background: rgba(255,255,255,.15); color: #fff; font-size: 13px; font-weight: 700;">AC</span>
                            <div>
                                <h2 class="text-sm font-semibold" style="margin: 0; color: #fff; font-size: 14px; font-weight: 700;">Arcee</h2>
                                <p class="text-xs text-slate-300" style="margin: 3px 0 0; color: #cbd5e1; font-size: 12px;">Rooms, items, schedules, and reservations</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="chatbot-new" type="button" class="rounded-full border border-white/25 bg-white/10 px-3 py-2 text-xs font-semibold text-white transition hover:bg-white/20" aria-label="Start a new chat">New chat</button>
                        <button id="chatbot-close" type="button" class="rounded-full px-2 py-1 text-xl leading-none text-white/80 transition hover:bg-white/15 hover:text-white" style="padding: 4px 8px; border: 0; border-radius: 9999px; background: transparent; color: rgba(255,255,255,.8); font-size: 22px; line-height: 1; cursor: pointer;" aria-label="Close Arcee">&times;</button>
                    </div>
                </div>
                <div id="chatbot-messages" class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-gradient-to-br from-purple-50/80 via-slate-50 to-cyan-50/70 p-5 text-sm" style="min-height: 0; flex: 1 1 auto; overflow-y: auto; scrollbar-width: thin;" aria-live="polite">
                    <div class="flex gap-3">
                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-700 text-[10px] font-bold text-white shadow-sm">AC</span>
                        <p class="w-fit max-w-[90%] rounded-2xl rounded-tl-sm border border-indigo-100 bg-white/95 px-3.5 py-2.5 text-slate-700 shadow-sm" style="width: fit-content; max-width: 90%;">Hi! Ask me about room and item availability, property reservations, schedules, or booking status.</p>
                    </div>
                </div>
                <form id="chatbot-form" class="pointer-events-auto border-t border-indigo-100 bg-white/95 p-4 sm:rounded-b-3xl" style="pointer-events: auto; position: relative; z-index: 10002;">
                    @csrf
                    <div class="flex items-center gap-2 rounded-2xl border border-slate-300 bg-white p-1.5 shadow-sm transition focus-within:border-indigo-400 focus-within:ring-2 focus-within:ring-indigo-100">
                        <label for="chatbot-input" class="sr-only">Ask Arcee</label>
                        <input id="chatbot-input" type="text" class="pointer-events-auto min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400" style="pointer-events: auto; position: relative; z-index: 10003; cursor: text;" placeholder="Ask about availability..." autocomplete="off">
                        <button id="chatbot-send" type="submit" class="rounded-xl bg-indigo-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-800 disabled:cursor-wait disabled:opacity-60">Send</button>
                    </div>
                    <p class="mt-2 px-1 text-[11px] text-slate-500">Check room or item availability, request a reservation, or view booking status.</p>
                </form>
            </section>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toggle = document.getElementById('chatbot-toggle');
                const close = document.getElementById('chatbot-close');
                const newChat = document.getElementById('chatbot-new');
                const panel = document.getElementById('chatbot-panel');
                const backdrop = document.getElementById('chatbot-backdrop');
                const form = document.getElementById('chatbot-form');
                const input = document.getElementById('chatbot-input');
                const messages = document.getElementById('chatbot-messages');
                const sendButton = document.getElementById('chatbot-send');
                const csrfToken = form.querySelector('input[name="_token"]').value;
                let responsePending = false;

                input.addEventListener('contextmenu', function() {
                    window.setTimeout(function() {
                        input.focus();
                    }, 0);
                });

                function resetVisibleChat() {
                    messages.innerHTML = `
                        <div class="flex gap-3">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-indigo-700 text-[10px] font-bold text-white shadow-sm">AC</span>
                            <p class="w-fit max-w-[90%] rounded-2xl rounded-tl-sm border border-indigo-100 bg-white/95 px-3.5 py-2.5 text-slate-700 shadow-sm">Hi! Ask me about room and item availability, property reservations, schedules, or booking status.</p>
                        </div>
                    `;
                    input.value = '';
                }

                function resetServerChat(keepalive = false) {
                    return fetch('{{ route('chatbot.reset') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                        },
                        body: JSON.stringify({}),
                        credentials: 'same-origin',
                        keepalive,
                    });
                }

                function setChatbotButtonVisibility(isOpen) {
                    if (!toggle) return;

                    const shouldHide = Boolean(isOpen);
                    toggle.style.opacity = shouldHide ? '0' : '1';
                    toggle.style.visibility = shouldHide ? 'hidden' : 'visible';
                    toggle.style.pointerEvents = shouldHide ? 'none' : 'auto';
                    toggle.setAttribute('aria-hidden', shouldHide ? 'true' : 'false');
                }

                toggle?.addEventListener('click', function() {
                    const isClosed = panel.classList.contains('invisible');

                    if (isClosed) {
                        setChatbotButtonVisibility(true);
                    } else {
                        setChatbotButtonVisibility(false);
                    }

                    panel.classList.toggle('invisible', !isClosed);
                    panel.classList.toggle('pointer-events-none', !isClosed);
                    panel.classList.toggle('translate-x-full', !isClosed);
                    panel.classList.toggle('opacity-0', !isClosed);
                    panel.style.transform = isClosed ? 'translateX(0)' : 'translateX(100%)';
                    backdrop.classList.toggle('invisible', !isClosed);
                    backdrop.classList.toggle('pointer-events-none', !isClosed);
                    backdrop.classList.toggle('opacity-0', !isClosed);
                    document.getElementById('chatbot-widget').classList.toggle('pointer-events-none', !isClosed);
                    panel.style.pointerEvents = isClosed ? 'auto' : 'none';
                    if (isClosed) input.focus();
                });
                close?.addEventListener('click', function() {
                    resetServerChat().catch(() => {});
                    resetVisibleChat();
                    panel.classList.add('invisible', 'pointer-events-none', 'translate-x-full', 'opacity-0');
                    panel.style.transform = 'translateX(100%)';
                    backdrop.classList.add('invisible', 'pointer-events-none', 'opacity-0');
                    document.getElementById('chatbot-widget').classList.add('pointer-events-none');
                    panel.style.pointerEvents = 'none';
                    setChatbotButtonVisibility(false);
                });
                backdrop?.addEventListener('click', function() { close.click(); });

                newChat?.addEventListener('click', async function() {
                    try {
                        await resetServerChat();
                    } catch (error) {
                        // The visible chat is still reset; the next message will establish fresh state.
                    }

                    resetVisibleChat();
                    input.focus();
                });

                window.addEventListener('pagehide', function() {
                    resetServerChat(true).catch(() => {});
                });

                form?.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    const message = input.value.trim();
                    if (!message || responsePending) return;
                    responsePending = true;

                    const escapedMessage = message.replace(/[&<>]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[character]));
                    messages.insertAdjacentHTML('beforeend', `<p style="width: fit-content; max-width: 90%; margin-left: auto;" class="rounded-2xl rounded-tr-sm bg-indigo-700 px-3.5 py-2.5 text-white shadow-sm">${escapedMessage}</p>`);
                    input.value = '';
                    sendButton.disabled = true;
                    sendButton.textContent = 'Checking...';
                    const pendingReply = document.createElement('p');
                    pendingReply.className = 'w-fit rounded-2xl rounded-tl-sm border border-indigo-100 bg-white/95 px-3.5 py-2.5 text-slate-500 shadow-sm';
                    pendingReply.textContent = 'Checking the latest room and reservation details...';
                    pendingReply.setAttribute('role', 'status');
                    messages.appendChild(pendingReply);
                    messages.scrollTop = messages.scrollHeight;

                    try {
                        const response = await fetch('{{ route('chatbot.respond') }}', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                            },
                            body: JSON.stringify({ message }),
                            credentials: 'same-origin',
                        });
                        const data = await response.json();
                        const reply = document.createElement('p');
                        reply.className = 'rounded-2xl rounded-tl-sm border border-indigo-100 bg-white/95 px-3.5 py-2.5 text-slate-700 shadow-sm';
                        reply.style.width = 'fit-content';
                        reply.style.maxWidth = '90%';
                        reply.textContent = data.reply || 'I could not understand that request.';
                        messages.appendChild(reply);
                        if (data.moderated && data.user_message) {
                            const userBubble = messages.lastElementChild?.previousElementSibling;
                            if (userBubble) {
                                userBubble.textContent = data.user_message;
                            }
                        }
                        if (data.action_url) {
                            const action = document.createElement('a');
                            action.href = data.action_url;
                            action.textContent = data.action_label || 'Continue';
                            action.className = 'block rounded-xl bg-gradient-to-r from-indigo-700 to-violet-700 px-4 py-2.5 text-xs font-semibold text-white shadow-sm transition hover:from-indigo-800 hover:to-violet-800';
                            action.style.width = 'fit-content';
                            messages.appendChild(action);
                        }
                        messages.scrollTop = messages.scrollHeight;
                    } catch (error) {
                        const reply = document.createElement('p');
                        reply.className = 'w-fit max-w-[90%] rounded-2xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-rose-700 shadow-sm';
                        reply.style.width = 'fit-content';
                        reply.style.maxWidth = '90%';
                        reply.textContent = 'Arcee is temporarily unavailable.';
                        messages.appendChild(reply);
                    } finally {
                        pendingReply.remove();
                        responsePending = false;
                        sendButton.disabled = false;
                        sendButton.textContent = 'Send';
                    }
                });
            });
        </script>
    @endif
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggle = document.getElementById('mobile-menu-toggle');
            const navigation = document.getElementById('mobile-navigation');
            const backdrop = document.getElementById('mobile-navigation-backdrop');
            const openIcon = document.getElementById('mobile-menu-open-icon');
            const closeIcon = document.getElementById('mobile-menu-close-icon');
            const header = toggle.closest('header');

            function updateNavigationPosition() {
                document.documentElement.style.setProperty('--mobile-header-height', `${header.offsetHeight}px`);
                navigation.style.top = `${header.offsetHeight}px`;
                backdrop.style.top = `${header.offsetHeight}px`;
            }

            function setMenuOpen(open) {
                updateNavigationPosition();
                toggle.setAttribute('aria-expanded', String(open));
                toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
                navigation.setAttribute('aria-hidden', String(!open));
                navigation.inert = !open;
                navigation.classList.toggle('pointer-events-none', !open);
                navigation.classList.toggle('pointer-events-auto', open);
                navigation.classList.toggle('translate-x-0', open);
                navigation.classList.toggle('-translate-x-full', !open);
                backdrop.setAttribute('aria-hidden', String(!open));
                backdrop.classList.toggle('pointer-events-none', !open);
                backdrop.classList.toggle('pointer-events-auto', open);
                backdrop.classList.toggle('opacity-100', open);
                backdrop.classList.toggle('opacity-0', !open);
                openIcon.classList.toggle('hidden', open);
                closeIcon.classList.toggle('hidden', !open);
            }

            updateNavigationPosition();
            window.addEventListener('resize', updateNavigationPosition);

            toggle.addEventListener('click', function() {
                setMenuOpen(toggle.getAttribute('aria-expanded') !== 'true');
            });

            backdrop.addEventListener('click', function() {
                setMenuOpen(false);
            });

            navigation.addEventListener('click', function(event) {
                if (event.target.closest('a')) setMenuOpen(false);
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') setMenuOpen(false);
            });

            window.matchMedia('(min-width: 768px)').addEventListener('change', function(event) {
                if (event.matches) setMenuOpen(false);
            });
        });
    </script>
</body>
</html>
