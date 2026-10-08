<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Campus Reserve')</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" crossorigin="anonymous">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js" defer></script>
    @stack('styles')
</head>
<body class="min-h-screen bg-white text-slate-900 transition-colors duration-200">
    <!-- Application header and global navigation bar -->
    <header class="flex items-center justify-between px-6 py-4 bg-white border-b border-gray-200 transition-colors duration-200">
        <a href="/" class="flex items-center">
            <div class="flex items-center border border-black px-2 py-1">
                <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                <span class="bg-black text-white text-xs font-medium px-1 ml-1">RESERVE</span>
            </div>
        </a>

        <nav class="hidden md:flex items-center gap-8">
            <!-- Main site navigation links. The active route is highlighted dynamically. -->
            <a href="/" class="text-sm font-medium hover:text-gray-600 transition-colors {{ request()->is('/') ? 'text-black' : 'text-gray-700' }}">HOME</a>
            <a href="/reserve" class="text-sm font-medium hover:text-gray-600 transition-colors {{ request()->is('reserve') ? 'text-black' : 'text-gray-700' }}">RESERVE</a>
            <a href="/calendar" class="text-sm font-medium hover:text-gray-600 transition-colors {{ request()->is('calendar') ? 'text-black' : 'text-gray-700' }}">CALENDAR</a>
            <a href="/about" class="text-sm font-medium hover:text-gray-600 transition-colors {{ request()->is('about') ? 'text-black' : 'text-gray-700' }}">ABOUT US</a>
            <a href="/contact" class="text-sm font-medium hover:text-gray-600 transition-colors {{ request()->is('contact') ? 'text-black' : 'text-gray-700' }}">CONTACT</a>
        </nav>

        <div class="flex items-center gap-3">
            @auth
                <a id="notificationLink" href="{{ route('notifications.index') }}" class="relative text-gray-700 hover:text-gray-900">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(auth()->user()->unreadNotifications->count())
                        <span id="notificationBadge" class="absolute -top-1 -right-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1.5 text-[10px] font-semibold text-white">
                            {{ auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
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
    </header>
    
    <main>
        @yield('content')
    </main>

    @auth
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var audioContext = null;
                var lastCount = null;
                var lastNotificationId = null;
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
                    if (document.hidden) return;

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
                            var notificationId = summary.latest_id || null;

                            if (lastCount !== null
                                && (count > lastCount
                                    || (count === lastCount && count > 0 && notificationId !== lastNotificationId))) {
                                playNotificationSound();
                            }

                            lastCount = count;
                            lastNotificationId = notificationId;

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
                        });
                }

                updateNotificationStatus();
                window.setInterval(updateNotificationStatus, 15000);
            });
        </script>
    @endauth

    @if(auth()->check() && !request()->routeIs('profile.show', 'security.compromised', 'login', 'register', 'password.*', '2fa.*', 'admin.*'))
        <div id="chatbot-widget" class="fixed inset-0 z-50 pointer-events-none" style="position: fixed; inset: 0; z-index: 9999; pointer-events: none;">
            <div id="chatbot-backdrop" class="invisible pointer-events-none absolute inset-0 bg-slate-950/20 opacity-0 transition-opacity duration-300" aria-hidden="true"></div>
            <button id="chatbot-toggle" type="button" class="pointer-events-auto fixed bottom-5 right-5 flex h-14 w-14 items-center justify-center rounded-full bg-black text-white shadow-xl transition hover:bg-gray-800" style="position: fixed; right: 20px; bottom: 20px; z-index: 10001; display: flex; width: 56px; height: 56px; border-radius: 9999px; background: #000; color: #fff; pointer-events: auto; cursor: pointer;" aria-label="Open reservation assistant" title="Open reservation assistant">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h8m-8 4h5m7-2a8 8 0 01-8 8 8.4 8.4 0 01-3.7-.85L4 20l.85-3.3A8 8 0 1112 20" />
                </svg>
            </button>
            <section id="chatbot-panel" class="invisible pointer-events-none fixed right-0 top-0 flex h-full translate-x-full flex-col border-l border-slate-200 bg-white opacity-0 shadow-2xl transition duration-300 ease-out" style="position: fixed; top: 0; right: 0; bottom: 0; z-index: 10000; display: flex; width: min(440px, 100vw); height: 100%;" aria-label="Reservation assistant">
                <div class="flex items-center justify-between border-b border-slate-800 bg-slate-950 px-5 py-4 text-white" style="display: flex; align-items: center; justify-content: space-between; min-height: 78px; padding: 16px 20px; background: #0f172a; color: #fff;">
                    <div>
                        <div class="flex items-center gap-3" style="display: flex; align-items: center; gap: 12px;">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-white/10 text-sm font-bold" style="display: flex; flex: 0 0 36px; width: 36px; height: 36px; align-items: center; justify-content: center; border-radius: 12px; background: rgba(255,255,255,.12); color: #fff; font-size: 13px; font-weight: 700;">CR</span>
                            <div>
                                <h2 class="text-sm font-semibold" style="margin: 0; color: #fff; font-size: 14px; font-weight: 700;">Reservation assistant</h2>
                                <p class="text-xs text-slate-300" style="margin: 3px 0 0; color: #cbd5e1; font-size: 12px;">Rooms, schedules, and reservations</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="chatbot-new" type="button" class="text-xs font-semibold text-slate-300 hover:text-white" aria-label="Start a new chat">New chat</button>
                        <button id="chatbot-close" type="button" class="rounded-lg px-2 py-1 text-xl leading-none text-slate-300 hover:bg-white/10 hover:text-white" style="padding: 4px 8px; border: 0; border-radius: 8px; background: transparent; color: #cbd5e1; font-size: 22px; line-height: 1; cursor: pointer;" aria-label="Close reservation assistant">&times;</button>
                    </div>
                </div>
                <div id="chatbot-messages" class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50 p-5 text-sm" style="min-height: 0; flex: 1 1 auto; overflow-y: auto; scrollbar-width: thin;">
                    <div class="flex gap-3">
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[10px] font-bold text-white">CR</span>
                        <p class="w-fit max-w-[90%] rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm" style="width: fit-content; max-width: 90%;">Hi! Ask me about room availability, schedules, or reservations.</p>
                    </div>
                </div>
                <form id="chatbot-form" class="pointer-events-auto border-t border-slate-200 bg-white p-4" style="pointer-events: auto; position: relative; z-index: 10002;">
                    @csrf
                    <div class="flex items-center gap-2 rounded-2xl border border-slate-300 bg-slate-50 p-1.5 focus-within:border-slate-900 focus-within:bg-white">
                        <label for="chatbot-input" class="sr-only">Ask the reservation assistant</label>
                        <input id="chatbot-input" type="text" class="pointer-events-auto min-w-0 flex-1 border-0 bg-transparent px-3 py-2 text-sm text-slate-900 outline-none" style="pointer-events: auto; position: relative; z-index: 10003; cursor: text;" placeholder="Ask about availability..." autocomplete="off">
                        <button type="submit" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">Send</button>
                    </div>
                    <p class="mt-2 px-1 text-[11px] text-slate-400">Check availability, request a reservation, or view your bookings.</p>
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
                const csrfToken = form.querySelector('input[name="_token"]').value;

                input.addEventListener('contextmenu', function() {
                    window.setTimeout(function() {
                        input.focus();
                    }, 0);
                });

                function resetVisibleChat() {
                    messages.innerHTML = `
                        <div class="flex gap-3">
                            <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-slate-900 text-[10px] font-bold text-white">CR</span>
                            <p class="w-fit max-w-[90%] rounded-2xl rounded-tl-sm border border-slate-200 bg-white px-3 py-2 text-slate-700 shadow-sm">Hi! Ask me about room availability, schedules, or reservations.</p>
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
                    if (!message) return;

                    const escapedMessage = message.replace(/[&<>]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[character]));
                    messages.insertAdjacentHTML('beforeend', `<p style="width: fit-content; max-width: 90%; margin-left: auto;" class="rounded-2xl bg-black px-3 py-2 text-white">${escapedMessage}</p>`);
                    input.value = '';
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
                        reply.className = 'rounded-2xl bg-slate-100 px-3 py-2 text-slate-700';
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
                            action.className = 'block rounded-xl bg-emerald-700 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-800';
                            action.style.width = 'fit-content';
                            messages.appendChild(action);
                        }
                        messages.scrollTop = messages.scrollHeight;
                    } catch (error) {
                        const reply = document.createElement('p');
                        reply.className = 'w-fit max-w-[90%] rounded-2xl bg-red-50 px-3 py-2 text-red-700';
                        reply.style.width = 'fit-content';
                        reply.style.maxWidth = '90%';
                        reply.textContent = 'The reservation assistant is temporarily unavailable.';
                        messages.appendChild(reply);
                    }
                });
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
