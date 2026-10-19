@extends('layouts.app')

@section('title', 'Admin Login - Campus Reserve')

@section('content')
<!-- Hidden admin login page with optional 2FA modal flow for admin authentication. -->
<main class="flex min-h-[calc(100vh-73px)] items-stretch">
    <div class="w-full md:w-1/2 flex flex-col items-center justify-center px-8 py-12">
        <div class="w-full max-w-md">
            <div class="flex justify-center mb-6">
                <div class="flex items-center border border-black px-2 py-1">
                    <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                    <span class="bg-black text-white text-xs font-medium px-1 ml-1">ADMIN</span>
                </div>
            </div>

            <h1 class="text-3xl font-bold text-center text-gray-900 mb-8">Admin Portal</h1>

            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @if (!session('show_2fa_modal'))
            <form method="POST" action="{{ route('admin.login') }}" class="space-y-6" id="adminLoginForm">
                @csrf
                <div class="space-y-2">
                    <label for="email" class="block text-sm text-gray-700">Email</label>
                    <input id="email" name="email" type="email" required class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent" value="{{ old('email') }}">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label for="password" class="block text-sm text-gray-700">Password</label>
                    <input id="password" name="password" type="password" required class="w-full px-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-black focus:border-transparent">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember_device" class="rounded border-gray-300 text-black focus:ring-black">
                        <span class="ml-2 text-sm text-gray-600">Remember this device for 30 days</span>
                    </label>
                </div>

                <div class="flex justify-center pt-4">
                    <button type="submit" class="px-8 py-2 bg-black text-white rounded-md hover:bg-gray-800 transition-colors w-full">Admin Sign In</button>
                </div>
            </form>
            @endif

            @if (session('show_2fa_modal') && session('pending_2fa_user_id'))
                <div id="2faModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm flex items-center justify-center z-50 p-4 animate-in fade-in zoom-in duration-200">
                    <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-8 max-w-sm w-full border border-gray-200 animate-in slide-in-from-bottom-4 duration-300">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">Verify Your Login</h2>
                            <button onclick="close2faModal()" class="text-gray-500 hover:text-gray-900 text-xl p-1 rounded-full hover:bg-gray-200 transition-all">&times;</button>
                        </div>
                        <p class="text-gray-700 mb-2 text-lg font-medium">An OTP has been generated for your account.</p>
                        <p class="text-sm text-gray-500 mb-2">Enter the 6-digit code from the server log.</p>
                        <p class="text-xs text-gray-500 mb-4">This code expires in 30 seconds and is logged for development.</p>
                        <div class="text-center mb-8">
                            <div class="text-lg font-mono bg-gray-100 rounded-lg p-4 mb-2">{{ session('pending_2fa_email') }}</div>
                            <p class="text-xs text-gray-500">Time remaining: <span id="otpTimer">30s</span></p>
                        </div>
                        <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-6">
                            @csrf
                            <div>
                                <input id="verify_otp" name="otp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" required class="w-full px-6 py-4 rounded-2xl border-2 border-gray-200 focus:border-black focus:outline-none text-center text-2xl font-bold tracking-widest uppercase bg-gray-50 hover:bg-white transition-all" placeholder="000000">
                                @error('otp')
                                    <p class="text-red-500 text-xs mt-2 text-center">{{ $message }}</p>
                                @enderror
                            </div>
                            <div class="flex gap-3 pt-2">
                                <button type="button" onclick="clearOtp()" class="flex-1 px-6 py-3 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all font-medium border border-gray-200">Clear</button>
                                <button type="submit" class="flex-1 px-6 py-3 bg-black text-white rounded-xl hover:bg-gray-900 transition-all font-bold shadow-lg hover:shadow-xl">Verify</button>
                            </div>
                        </form>
                        <div class="flex items-center justify-center gap-2 mt-6 pt-6 border-t border-gray-200">
                            <form id="adminResendOtpForm" method="POST" action="{{ route('2fa.resend') }}" class="inline-flex items-center">
                                @csrf
                                <button id="adminResendOtpButton" type="submit" class="text-sm text-blue-600 hover:text-blue-700 font-medium hover:underline transition-colors">Resend Code</button>
                            </form>
                            <span class="text-xs text-gray-500">| The code is logged for development and expires quickly.</span>
                        </div>
                        <div id="adminOtpStatusMessage" class="text-center text-sm text-green-700 mt-3"></div>
                        </div>
                        <button onclick="location.href='{{ route('login') }}'" class="w-full mt-6 py-2 px-4 bg-gray-100 text-gray-700 rounded-xl hover:bg-gray-200 transition-all font-medium text-sm">← Back to User Login</button>
                    </div>
                </div>
            @endif

            <div class="mt-6 text-center">
                <a href="/login" class="text-sm text-gray-600 hover:text-gray-900 hover:underline">Return to normal user login</a>
            </div>
        </div>
    </div>

    <div class="hidden md:block w-1/2 relative overflow-hidden h-full min-h-[calc(100vh-73px)]">
        <div class="absolute inset-0" style="background: linear-gradient(135deg, #14532d 0%, #15803d 55%, #facc15 100%);"></div>
    </div>
</main>

<script>
var timeLeft = {{ session('time_left', 30) }};
var timerInterval = null;
const timerEl = document.getElementById('otpTimer');

function startTimer() {
    // Clear any existing timer
    if (timerInterval) {
        clearInterval(timerInterval);
    }

    if (timerEl && timeLeft > 0) {
        timerEl.innerHTML = timeLeft + 's';
        
        timerInterval = setInterval(() => {
            timeLeft--;
            if (timeLeft > 0) {
                timerEl.textContent = timeLeft + 's';
            } else {
                clearInterval(timerInterval);
                timerEl.innerHTML = '<span class="text-red-500">Expired — click Resend Code</span>';
            }
        }, 1000);
    } else if (timerEl && timeLeft <= 0) {
        timerEl.innerHTML = '<span class="text-red-500">Expired — click Resend Code</span>';
    }
}

// Initialize timer on page load
if (timerEl) {
    startTimer();
}

function close2faModal() {
    const modal = document.getElementById('2faModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function clearOtp() {
    const otp = document.getElementById('verify_otp');
    if (otp) otp.value = '';
}

async function handleAdminResendOtp(event) {
    event.preventDefault();
    const form = document.getElementById('adminResendOtpForm');
    const status = document.getElementById('adminOtpStatusMessage');
    const timerEl = document.getElementById('otpTimer');

    if (! form || ! status || ! timerEl) {
        return;
    }

    status.textContent = 'Resending code...';
    status.classList.remove('text-red-700');
    status.classList.add('text-green-700');

    const token = form.querySelector('input[name="_token"]').value;

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token,
            },
            body: JSON.stringify({}),
            credentials: 'same-origin',
        });

        const data = await response.json();
        if (! response.ok) {
            status.classList.remove('text-green-700');
            status.classList.add('text-red-700');
            status.textContent = data.error || 'Unable to resend code. Try again.';
            return;
        }

        status.classList.remove('text-red-700');
        status.classList.add('text-green-700');
        status.textContent = data.status || 'A new code has been generated and logged.';
        
        // Reset timer with new time
        timeLeft = data.time_left || 30;
        startTimer();
    } catch (error) {
        status.classList.remove('text-green-700');
        status.classList.add('text-red-700');
        status.textContent = 'Unable to resend code. Try again.';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('adminResendOtpForm');
    if (form) {
        form.addEventListener('submit', handleAdminResendOtp);
    }
});
</script>
@endsection
