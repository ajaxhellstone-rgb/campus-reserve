@extends('layouts.app')

@section('title', '2FA Verification - Campus Reserve')

@section('content')
<!-- Standalone 2FA verification page for the one-time code validation step. -->
<main class="flex min-h-[calc(100vh-73px)] bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50">
    <div class="flex w-full flex-col items-center justify-center px-5 py-10 sm:px-8 md:w-1/2 md:py-12">
        <div class="w-full max-w-md rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-xl shadow-indigo-950/10 backdrop-blur sm:p-10">
            <div class="flex justify-center mb-6">
                <div class="flex items-center rounded-xl border border-indigo-200 bg-white px-2 py-1 shadow-sm">
                    <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                    <span class="ml-1 rounded-md bg-indigo-700 px-1 text-xs font-medium text-white">RESERVE</span>
                </div>
            </div>
            
            <h1 class="mb-8 text-center text-3xl font-bold tracking-tight text-slate-900">
                VERIFY YOUR LOGIN
            </h1>
            
            <div class="text-center mb-8">
                @if($method === 'authenticator')
                    <p class="mb-2 text-slate-600">
                        @if($provider === 'google')
                            Google sign-in succeeded. Enter the current code from your Campus Reserve authenticator app.
                        @else
                            Enter the current code from your authenticator app.
                        @endif
                    </p>
                @else
                    <p class="mb-2 text-slate-600">
                        @if($provider === 'google')
                            Google sign-in succeeded. We sent a 6-digit verification code to your email.
                        @else
                            We sent a 6-digit verification code to your email.
                        @endif
                    </p>
                @endif
            </div>
            
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            <p class="mb-6 rounded-xl bg-indigo-50 px-4 py-3 text-center text-sm font-medium text-indigo-700">
                You have {{ $attemptsRemaining }} attempts remaining.
            </p>
            
            <form method="POST" action="{{ route('2fa.verify') }}" class="space-y-6">
                @csrf
                
                <div class="space-y-2">
                    <label for="otp" class="block text-sm font-medium text-slate-700">Verification Code</label>
                    <input
                        id="otp"
                        name="otp"
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        autocomplete="one-time-code"
                        required
                        class="w-full rounded-2xl border-2 border-indigo-100 bg-indigo-50 px-4 py-4 text-center text-2xl font-bold tracking-[0.4em] text-indigo-950 transition focus:border-indigo-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-indigo-100"
                        placeholder="000000"
                    >
                    @error('otp')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="flex flex-col sm:flex-row gap-3">
                    <button
                        type="submit"
                        class="flex-1 rounded-xl bg-gradient-to-r from-indigo-700 via-violet-700 to-amber-400 px-8 py-3 font-semibold text-white shadow-lg shadow-indigo-900/15 transition hover:-translate-y-0.5 hover:from-indigo-800 hover:via-violet-800 hover:to-amber-500"
                    >
                        Verify
                    </button>
                    <button
                        type="button"
                        onclick="document.querySelector('input[name=otp]').value = ''"
                        class="flex-1 rounded-xl border border-indigo-100 bg-indigo-50 px-8 py-3 font-semibold text-indigo-900 transition hover:bg-indigo-100"
                    >
                        Clear
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                @if($method !== 'authenticator')
                    <form method="POST" action="{{ route('2fa.resend') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-sm font-semibold text-indigo-700 hover:text-violet-700 hover:underline">
                            Resend Code
                        </button>
                    </form>
                    <p class="mt-2 text-xs text-slate-500">
                        Didn't receive it? Check your spam folder.
                    </p>
                @endif
                <p class="mt-4">
                    <a href="/login" class="text-sm font-medium text-indigo-700 hover:text-violet-700 hover:underline">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
    
    <div aria-hidden="true" class="relative hidden min-h-[calc(100vh-73px)] w-1/2 overflow-hidden bg-gradient-to-br from-indigo-700 via-violet-600 to-amber-400 md:block">
        <div class="absolute -right-32 -top-28 h-[32rem] w-[32rem] rounded-full bg-pink-300/35 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-32 h-[34rem] w-[34rem] rounded-full bg-cyan-200/35 blur-3xl"></div>
        <div class="absolute inset-0 opacity-20" style="background-image: radial-gradient(#fff 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="absolute inset-x-12 top-1/2 h-px -translate-y-1/2 bg-gradient-to-r from-transparent via-white/60 to-transparent"></div>
        <div class="absolute left-1/4 top-1/3 h-24 w-24 rotate-12 rounded-[2rem] border border-white/30 bg-white/10 backdrop-blur-sm"></div>
        <div class="absolute bottom-1/3 right-1/4 h-36 w-36 rounded-full border border-white/30 bg-white/10 backdrop-blur-sm"></div>
    </div>
</main>

@endsection
