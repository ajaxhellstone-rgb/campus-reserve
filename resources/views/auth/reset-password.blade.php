@extends('layouts.app')

@section('title', 'Reset Password - Campus Reserve')

@section('content')
<main class="flex min-h-[calc(100vh-73px)] items-center justify-center bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 px-5 py-12 sm:px-6">
    <div class="w-full max-w-md rounded-[2rem] border border-white/80 bg-white/90 p-6 shadow-xl shadow-indigo-950/10 backdrop-blur sm:p-10">
        <div class="mb-6 flex justify-center">
            <div class="flex items-center rounded-xl border border-indigo-200 bg-white px-2 py-1 shadow-sm">
                <span class="text-xs font-medium tracking-wide">CAMPUS</span>
                <span class="ml-1 rounded-md bg-indigo-700 px-1 text-xs font-medium text-white">RESERVE</span>
            </div>
        </div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Reset password</h1>
        <p class="mt-2 text-sm text-slate-600">Choose a new password for your Campus Reserve account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Email</span>
                <input type="email" name="email" value="{{ old('email', $email) }}" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @error('email')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </label>
            <label class="block">
                <span class="text-sm font-medium text-slate-700">New password</span>
                <input type="password" name="password" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                <p class="mt-2 text-xs leading-5 text-slate-500">Use 5-30 characters for a secure password. Longer passwords are easier to keep safe.</p>
            </label>
            <label class="block">
                <span class="text-sm font-medium text-slate-700">Confirm new password</span>
                <input type="password" name="password_confirmation" required class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-indigo-500 focus:outline-none focus:ring-4 focus:ring-indigo-100">
                @error('password')<p class="mt-2 text-sm text-rose-600">{{ $message }}</p>@enderror
            </label>
            <button type="submit" class="w-full rounded-xl bg-gradient-to-r from-indigo-700 via-violet-700 to-amber-400 px-8 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-900/15 transition hover:-translate-y-0.5 hover:from-indigo-800 hover:via-violet-800 hover:to-amber-500 focus:outline-none focus:ring-4 focus:ring-indigo-200">Reset password</button>
        </form>
    </div>
</main>
@endsection
