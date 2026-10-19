@extends('layouts.app')

@section('title', 'Forgot Password - Campus Reserve')

@section('content')
<main class="flex min-h-[calc(100vh-73px)] items-center justify-center bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 px-5 py-12 sm:px-6">
    <div class="w-full max-w-md rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-xl shadow-indigo-950/10 backdrop-blur sm:p-10">
        <div class="mb-6 flex justify-center">
            <div class="flex items-center rounded-xl border border-indigo-200 bg-white px-2 py-1 shadow-sm">
                <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                <span class="ml-1 rounded-md bg-indigo-700 px-1 text-xs font-medium text-white">RESERVE</span>
            </div>
        </div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Forgot password?</h1>
        <p class="mt-2 text-sm leading-6 text-slate-600">Enter your Campus Reserve email and we will send you a password reset link.</p>

        @if (session('status'))
            <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
            @csrf
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @error('email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </label>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-700 via-violet-700 to-amber-400 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-900/15 transition hover:-translate-y-0.5 hover:from-indigo-800 hover:via-violet-800 hover:to-amber-500 focus:outline-none focus:ring-4 focus:ring-indigo-200">Send reset link</button>
        </form>

        <p class="mt-6 text-center text-sm text-slate-600"><a href="{{ route('login') }}" class="font-semibold text-indigo-700 hover:text-violet-700 hover:underline">Back to login</a></p>
    </div>
</main>
@endsection
