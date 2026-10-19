@extends('layouts.app')

@section('title', 'About Us')

@section('content')
<section class="relative overflow-hidden bg-gradient-to-br from-purple-50 via-slate-50 to-cyan-50 py-16 sm:py-20">
    <div aria-hidden="true" class="pointer-events-none absolute -left-24 top-10 h-80 w-80 rounded-full bg-indigo-200/60 blur-3xl"></div>
    <div aria-hidden="true" class="pointer-events-none absolute -right-20 top-48 h-96 w-96 rounded-full bg-pink-200/50 blur-3xl"></div>
    <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
        <div class="grid gap-10 lg:grid-cols-[1.2fr_0.8fr] items-center">
            <div>
                <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-700">About Campus Reserve</p>
                <h1 class="mb-6 text-4xl font-semibold leading-tight tracking-tight text-slate-950 sm:text-5xl">Smart campus booking made simple for students, staff, and administrators.</h1>
                <p class="text-lg leading-8 text-slate-600 mb-6">Campus Reserve is a secure reservation platform designed to bring campus spaces, classrooms, labs, and event venues together in one intuitive calendar. We help schools manage bookings efficiently, prevent conflicts, and keep everyone aligned with real-time availability.</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-700">Our mission</p>
                        <p class="text-slate-700 leading-7">Create a dependable campus scheduling experience that reduces manual work and keeps facilities running smoothly.</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-6 shadow-sm">
                        <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-pink-700">Our promise</p>
                        <p class="text-slate-700 leading-7">Deliver fast, secure access to room reservations while protecting student and staff data.</p>
                    </div>
                </div>
            </div>

            <div class="rounded-4xl border border-white/70 bg-gradient-to-br from-indigo-700 via-violet-700 to-purple-700 p-8 text-white shadow-2xl shadow-indigo-950/15">
                <div class="mb-8">
                    <p class="text-sm font-semibold uppercase tracking-[0.2em] text-indigo-200">Why customers love it</p>
                    <h2 class="text-3xl font-semibold mt-4 text-white">Built for organized campuses.</h2>
                </div>
                <div class="space-y-5">
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-sm backdrop-blur">
                        <p class="text-2xl font-semibold text-white">24/7 availability</p>
                        <p class="text-slate-300 mt-2">Book rooms at any hour with instant calendar visibility and no waiting.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-sm backdrop-blur">
                        <p class="text-2xl font-semibold text-white">Conflict-free scheduling</p>
                        <p class="text-slate-300 mt-2">Automatically prevent double bookings by seeing real-time availability across campus spaces.</p>
                    </div>
                    <div class="rounded-3xl border border-white/10 bg-white/10 p-5 shadow-sm backdrop-blur">
                        <p class="text-2xl font-semibold text-white">Secure sign-in</p>
                        <p class="text-slate-300 mt-2">Protect access with robust authentication and two-factor verification when it matters most.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-16 grid gap-8 lg:grid-cols-3">
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-8 shadow-sm transition hover:-translate-y-1 hover:border-indigo-200 hover:shadow-lg">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-indigo-700">Fast adoption</p>
                <p class="text-3xl font-semibold text-slate-900">Easy setup</p>
                <p class="mt-4 text-slate-600 leading-7">Add facilities, manage users, and start booking in minutes with a clean interface that feels natural for every campus role.</p>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-8 shadow-sm transition hover:-translate-y-1 hover:border-pink-200 hover:shadow-lg">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-pink-700">Reliable operations</p>
                <p class="text-3xl font-semibold text-slate-900">Fewer conflicts</p>
                <p class="mt-4 text-slate-600 leading-7">Keep schedules aligned and reduce manual coordination with automated reservation tracking and approval workflows.</p>
            </div>
            <div class="rounded-3xl border border-slate-200/80 bg-white/90 p-8 shadow-sm transition hover:-translate-y-1 hover:border-cyan-200 hover:shadow-lg">
                <p class="mb-3 text-sm font-semibold uppercase tracking-[0.2em] text-cyan-700">User-first design</p>
                <p class="text-3xl font-semibold text-slate-900">Clear visibility</p>
                <p class="mt-4 text-slate-600 leading-7">See every booking at a glance using a modern calendar, helpful reservations panel, and easy search tools.</p>
            </div>
        </div>
    </div>
</section>
@endsection
